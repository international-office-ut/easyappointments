<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

namespace EA\Engine\Search;

/**
 * Fuzzy Filter
 *
 * Filters record lists against a search key in a forgiving way: the key is split into tokens,
 * each token may match any of the defined fields as a substring or within a small edit distance
 * (so minor typos and transposed characters still match), accented characters are folded to
 * their ASCII equivalent and every token just has to land somewhere in the searchable fields,
 * which makes multi-word keys work even when the words appear across different fields.
 *
 * Records with a lower score are better matches, so a filtered list is ordered by relevance.
 */
class Fuzzy {
    /**
     * Upper bound of records fetched for a fuzzy filter operation.
     */
    public const MAX_CANDIDATES = 5000;

    /**
     * Memoized word distance results of the current filter call.
     *
     * @var array
     */
    protected static $word_distance_cache = [];

    /**
     * Lowercase letters that get folded to their plain ASCII equivalent.
     *
     * @var array
     */
    protected static $ascii_fold = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ā' => 'a', 'æ' => 'ae',
        'ç' => 'c',
        'ď' => 'd', 'đ' => 'd',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i',
        'ł' => 'l', 'ľ' => 'l',
        'ñ' => 'n', 'ň' => 'n',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
        'ŕ' => 'r',
        'ß' => 'ss', 'ś' => 's', 'š' => 's',
        'ť' => 't', 'þ' => 'th',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ū' => 'u',
        'ŵ' => 'w',
        'ý' => 'y', 'ÿ' => 'y', 'ŷ' => 'y',
        'ž' => 'z', 'ź' => 'z', 'ż' => 'z',
    ];

    /**
     * Filter the records that match the key, ordered by relevance.
     *
     * @param array $records The records to be filtered (sequential array of associative records).
     * @param array $fields The record fields to search in.
     * @param string $key The search key as typed by the user.
     * @param int|NULL $limit Optionally cut the result to the given number of records.
     *
     * @return array Returns the matching records, only when a key is provided the list is
     * ordered by relevance, otherwise the original record order is preserved.
     */
    public static function filter(array $records, array $fields, string $key, ?int $limit = NULL): array
    {
        $tokens = self::tokens($key);

        if (empty($tokens))
        {
            return $limit === NULL ? $records : array_slice($records, 0, $limit);
        }

        $scored_records = [];

        foreach ($records as $index => $record)
        {
            $text_fields = self::prepare($record, $fields);

            if ($text_fields === NULL)
            {
                continue;
            }

            $score = 0.0;

            $matched = TRUE;

            foreach ($tokens as $token)
            {
                $token_score = self::score_token($text_fields, $token);

                if ($token_score === NULL)
                {
                    $matched = FALSE;

                    break;
                }

                $score += $token_score;
            }

            if ( ! $matched)
            {
                continue;
            }

            // Favor records that contain the whole key inside one field, even when it spans
            // token boundaries that otherwise only match loosely.
            if (count($tokens) > 1)
            {
                foreach ($text_fields as $text_field)
                {
                    if (strpos($text_field['text'], implode(' ', $tokens)) !== FALSE)
                    {
                        $score -= 2.0;

                        break;
                    }
                }
            }

            $scored_records[] = ['index' => $index, 'score' => $score, 'record' => $record];
        }

        usort($scored_records, static function (array $a, array $b): int {
            // The index comparison keeps the original record order for equally ranked records.
            return $a['score'] <=> $b['score'] ?: $a['index'] <=> $b['index'];
        });

        $records = array_column($scored_records, 'record');

        return $limit === NULL ? $records : array_slice($records, 0, $limit);
    }

    /**
     * Split a key into lowercase ASCII search tokens.
     *
     * Punctuation is treated as separator and accented characters are folded to their ASCII
     * equivalent, so that e.g. "André" and "Andre" produce the same tokens.
     *
     * @param string $text Text containing the key.
     *
     * @return array Returns the search tokens (empty array when the text only has separators).
     */
    public static function tokens(string $text): array
    {
        $text = static::fold($text);

        $raw_tokens = preg_split('/[^\p{L}\p{N}]+/u', $text, 0, PREG_SPLIT_NO_EMPTY);

        $tokens = [];

        foreach ($raw_tokens as $raw_token)
        {
            if ($raw_token !== '')
            {
                $tokens[] = $raw_token;
            }
        }

        return $tokens;
    }

    /**
     * Lowercase a string and fold accented characters to their ASCII equivalent.
     */
    protected static function fold(string $text): string
    {
        return strtr(mb_strtolower(trim($text), 'UTF-8'), static::$ascii_fold);
    }

    /**
     * Prepare the searchable fields of a record.
     *
     * @param array $record The record to be prepared.
     * @param array $fields The record fields to search in.
     *
     * @return array|NULL Returns the prepared fields or NULL when no field has a value. Each
     * prepared field contains the folded full text ("text") and the folded words ("words").
     */
    protected static function prepare(array $record, array $fields): ?array
    {
        $text_fields = [];

        foreach ($fields as $field)
        {
            if ( ! array_key_exists($field, $record))
            {
                continue;
            }

            $value = $record[$field];

            if ( ! is_scalar($value))
            {
                continue;
            }

            $text = static::fold((string) $value);

            if ($text === '')
            {
                continue;
            }

            $text_fields[] = [
                'text' => $text,
                'words' => preg_split('/[^\p{L}\p{N}]+/u', $text, 0, PREG_SPLIT_NO_EMPTY),
            ];
        }

        return $text_fields ?: NULL;
    }

    /**
     * Score a token against the prepared fields of a record.
     *
     * @param array $text_fields The prepared fields of the record.
     * @param string $token The lowercase ASCII token to match.
     *
     * @return float|NULL Returns the quality score (lower is better) or NULL when the token
     * does not match any of the fields.
     */
    protected static function score_token(array $text_fields, string $token): ?float
    {
        $best_score = PHP_INT_MAX;

        foreach ($text_fields as $text_field)
        {
            // Exact substring of the full field text (covers e.g. names, emails and numbers).
            $position = strpos($text_field['text'], $token);

            if ($position !== FALSE)
            {
                // Matches at the start of a word rank better than matches in the middle.
                $starts_word = $position === 0
                    || strpos(' ' . $text_field['text'], ' ' . $token) !== FALSE;

                $best_score = min($best_score, $starts_word ? 1.0 : 2.0);

                continue;
            }

            foreach ($text_field['words'] as $word)
            {
                $distance = self::word_distance($token, $word);

                if ($distance === NULL)
                {
                    continue;
                }

                $best_score = min($best_score, 3.0 + $distance);
            }
        }

        return $best_score === PHP_INT_MAX ? NULL : $best_score;
    }

    /**
     * Distance between a token and a field word, when they are close enough.
     *
     * Two comparisons tolerate small typos: against the start of the word (so a partial token
     * like "smi" matches "smith") and against the whole word (so a misspelled token still
     * matches when the difference is within the length-dependent tolerance).
     *
     * @param string $token The token to be matched.
     * @param string $word The field word to match against.
     *
     * @return int|NULL Returns the edit distance or NULL when the pair is not close enough.
     */
    protected static function word_distance(string $token, string $word): ?int
    {
        if (strlen($token) <= 2)
        {
            // Short tokens must match exactly, otherwise the matching becomes too noisy.
            return NULL;
        }

        $tolerance = strlen($token) >= 5 ? 2 : 1;

        // Memoized, because the same token gets compared with the same words of every record.
        $cache_key = $token . '|' . $word;

        if (array_key_exists($cache_key, self::$word_distance_cache))
        {
            return self::$word_distance_cache[$cache_key];
        }

        // Compare the token against the equally long start of the word (a partial token like
        // "smi" matches "smith") and then against the whole word. A length difference bigger
        // than the tolerance for the whole word cannot be reached with the tolerated edits.
        $distance = self::edit_distance($token, substr($word, 0, strlen($token)));

        if ($distance > $tolerance && strlen($word) > strlen($token)
            && abs(strlen($word) - strlen($token)) <= $tolerance)
        {
            $distance = self::edit_distance($token, $word);
        }

        if ($distance > $tolerance)
        {
            $distance = NULL;
        }

        self::$word_distance_cache[$cache_key] = $distance;

        return $distance;
    }

    /**
     * Damerau-Levenshtein distance (with restricted transpositions).
     */
    protected static function edit_distance(string $a, string $b): int
    {
        $length_a = strlen($a);
        $length_b = strlen($b);

        if ($length_a === 0)
        {
            return $length_b;
        }

        if ($length_b === 0)
        {
            return $length_a;
        }

        $distances = [];

        for ($i = 0; $i <= $length_a; $i++)
        {
            $distances[$i][0] = $i;
        }

        for ($j = 0; $j <= $length_b; $j++)
        {
            $distances[0][$j] = $j;
        }

        for ($i = 1; $i <= $length_a; $i++)
        {
            for ($j = 1; $j <= $length_b; $j++)
            {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;

                $distances[$i][$j] = min(
                    $distances[$i - 1][$j] + 1,
                    $distances[$i][$j - 1] + 1,
                    $distances[$i - 1][$j - 1] + $cost
                );

                if ($i > 1 && $j > 1 && $a[$i - 1] === $b[$j - 2] && $a[$i - 2] === $b[$j - 1])
                {
                    $distances[$i][$j] = min($distances[$i][$j], $distances[$i - 2][$j - 2] + 1);
                }
            }
        }

        return $distances[$length_a][$length_b];
    }
}
