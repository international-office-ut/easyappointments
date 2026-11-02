<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

namespace EA\Engine\Security;

use InvalidArgumentException;

/**
 * Password hashing and verification for user credentials.
 *
 * Generates PHP-native password hashes (Argon2id when the runtime provides
 * it, bcrypt otherwise). Modern hashes carry their own salt and cost
 * parameters, so the legacy "user_settings.salt" column is not part of
 * them anymore; the column and the CI3 key derivation stay only as the
 * fallback path for pre-cutover accounts.
 *
 * Legacy format (pre-argon2): salted, iterated SHA-256 hex digest
 * (`hash_password()`), which gets re-hashed into the modern format with the
 * lazy rehash of a successful login.
 */
class Passwords {
    /**
     * Distinguish the modern password hashes from the legacy hex digests.
     *
     * @param string $password_hash Stored hash string.
     *
     * @return bool Returns TRUE for crypt-format hashes (argon2id/bcrypt).
     */
    public static function isMod(string $password_hash): bool
    {
        return str_starts_with($password_hash, '$');
    }

    /**
     * Hash a plaintext password with the strongest available algorithm.
     *
     * @param string $password Plaintext password.
     *
     * @return string Returns the password hash (self-contained salt).
     */
    public static function hash(string $password): string
    {
        return password_hash($password, self::algorithm());
    }

    /**
     * Verify a plaintext password against a stored hash.
     *
     * Modern hashes go through password_verify(); legacy hashes keep the
     * CI3 salted-iteration derivation with the per-row salt.
     *
     * @param string $password Plaintext password.
     * @param string $salt Stored salt of the user row (legacy path).
     * @param string $password_hash Stored password hash.
     *
     * @return bool Returns whether the credentials match.
     */
    public static function verify(string $password, string $salt, string $password_hash): bool
    {
        if ($password_hash === '')
        {
            return FALSE;
        }

        if (self::isMod($password_hash))
        {
            return password_verify($password, $password_hash);
        }

        return self::legacyHash($salt, $password) === $password_hash;
    }

    /**
     * Whether the stored hash must be re-hashed into the modern format.
     *
     * Legacy digests always need it; modern hashes follow the standard
     * password_needs_rehash contract (cost/algorithm drift).
     *
     * @param string $password_hash Stored hash string.
     *
     * @return bool Returns TRUE when the row should be upgraded.
     */
    public static function needsRehash(string $password_hash): bool
    {
        if ( ! self::isMod($password_hash))
        {
            return TRUE;
        }

        return password_needs_rehash($password_hash, self::algorithm());
    }

    /**
     * The configured algorithm of the runtime.
     *
     * @return int|string Returns an algorithm constant for password_needs_rehash.
     */
    protected static function algorithm()
    {
        return in_array('argon2id', password_algos(), true)
            ? PASSWORD_ARGON2ID
            : PASSWORD_BCRYPT;
    }

    /**
     * CI3-equivalent legacy derivation (salted, iterated SHA-256).
     *
     * @param string $salt Stored salt of the user row.
     * @param string $password Plaintext password.
     *
     * @return string Returns the legacy hex digest.
     */
    protected static function legacyHash(string $salt, string $password): string
    {
        if ($salt === '')
        {
            throw new InvalidArgumentException('The legacy password check requires the stored user salt.');
        }

        $half = (int) (strlen($salt) / 2);

        $hash = hash('sha256', substr($salt, 0, $half) . $password . substr($salt, $half));

        for ($i = 0; $i < 100000; $i++)
        {
            $hash = hash('sha256', $hash);
        }

        return $hash;
    }
}
