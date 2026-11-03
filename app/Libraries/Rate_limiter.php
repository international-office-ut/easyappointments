<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.4.0
 * ---------------------------------------------------------------------------- */

namespace App\Libraries;

/**
 * File-based rate limiter that stores its records in the storage cache directory.
 *
 * Used by the login flows and the calendar feed endpoints.
 */
class Rate_limiter {
    /**
     * Rate limiting paths point into the storage cache directory.
     *
     * @param string $key Unique key of the rate limit record.
     * @return string
     */
    public static function path(string $key): string
    {
        return implode(DIRECTORY_SEPARATOR, [ROOTPATH, 'storage', 'cache', sha1($key) . '.ratelimit']);
    }

    /**
     * Returns the rate limit record of the provided key, removing it if it is expired or corrupted.
     *
     * @param string $key Unique key of the rate limit record.
     * @return array|null
     */
    public static function read(string $key): ?array
    {
        $path = self::path($key);
        $contents = @file_get_contents($path);

        if ($contents === FALSE)
        {
            return NULL;
        }

        $record = json_decode($contents, TRUE);

        if ( ! is_array($record) || ! isset($record['hits'], $record['expires']))
        {
            @unlink($path);

            return NULL;
        }

        if ($record['expires'] < time())
        {
            @unlink($path);

            return NULL;
        }

        return $record;
    }

    /**
     * Read the current hit counter of a rate limit key.
     *
     * @param string $key Rate limit key.
     *
     * @return int
     */
    public static function hits(string $key): int
    {
        $record = self::read($key);

        return $record === NULL ? 0 : (int)($record['hits'] ?? 0);
    }

    /**
     * Returns whether the provided key has reached its attempts limit.
     *
     * @param string $key Unique key of the rate limit record.
     * @param int $max_attempts Maximum attempts allowed within the window.
     * @return bool
     */
    public static function has_reached_limit(string $key, int $max_attempts): bool
    {
        $record = self::read($key);

        return $record !== NULL && $record['hits'] >= $max_attempts;
    }

    /**
     * Registers a new attempt for the provided key, so that the limit gets closer.
     *
     * @param string $key Unique key of the rate limit record.
     * @param int $window Attempts window in seconds.
     */
    public static function register_attempt(string $key, int $window): void
    {
        $record = self::read($key);

        if ($record === NULL)
        {
            $record = [
                'hits' => 0,
                'expires' => time() + $window
            ];

            // A new rate limit record is a good moment to clean up forgotten records of
            // other keys, so that the cache directory cannot grow without bounds.
            self::prune();
        }

        $record['hits']++;

        @file_put_contents(self::path($key), json_encode($record), LOCK_EX);
    }

    /**
     * Removes all stale rate limit records from the storage cache directory.
     */
    public static function prune(): void
    {
        $files = @glob(dirname(self::path('prune')) . '/*.ratelimit');

        if ($files === FALSE)
        {
            return;
        }

        foreach ($files as $file)
        {
            if (@filemtime($file) < time() - 30 * DAY)
            {
                @unlink($file);
            }
        }
    }

    /**
     * Removes the rate limit record of the provided key.
     *
     * @param string $key Unique key of the rate limit record.
     */
    public static function clear(string $key): void
    {
        @unlink(self::path($key));
    }
}
