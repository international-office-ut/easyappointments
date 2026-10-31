<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Get date in RFC3339
 *
 * For example used in XML/Atom
 *
 * @link http://stackoverflow.com/questions/5671433/php-time-to-calendar-dates-time-format
 *
 * @param integer $timestamp
 *
 * @return string date in RFC3339
 *
 * @author Boris Korobkov
 */
function date3339($timestamp = 0)
{
    if ( ! $timestamp)
    {
        $timestamp = time();
    }
    $date = date('Y-m-d\TH:i:s', $timestamp);

    $matches = [];
    if (preg_match('/^([\-+])(\d{2})(\d{2})$/', date('O', $timestamp), $matches))
    {
        $date .= $matches[1] . $matches[2] . ':' . $matches[3];
    }
    else
    {
        $date .= 'Z';
    }
    return $date;
}

/**
 * Generate a hash of password string.
 *
 * For user security, all system passwords are stored in hash string into the database. Use this method to produce the
 * hashed password.
 *
 * @param string $salt Salt value for current user. This value is stored on the database and is used when generating
 * the password hash.
 * @param string $password Given string password.
 *
 * @return string Returns the hash string of the given password.
 */
function hash_password($salt, $password)
{
    $half = (int)(strlen($salt) / 2);
    $hash = hash('sha256', substr($salt, 0, $half) . $password . substr($salt, $half));

    for ($i = 0; $i < 100000; $i++)
    {
        $hash = hash('sha256', $hash);
    }

    return $hash;
}

/**
 * Generate a new password salt.
 *
 * This method will not check if the salt is unique in database. This must be done
 * from the calling procedure.
 *
 * @return string Returns a salt string.
 */
function generate_salt()
{
    $max_length = 100;
    $salt = hash('sha256', (uniqid(rand(), TRUE)));
    return substr($salt, 0, $max_length);
}

/**
 * Describe the field names of a data argument.
 *
 * Exception messages must not contain the submitted data itself, only the names of its
 * fields, so that payloads never get reflected back to the client.
 *
 * @param mixed $data The data argument of an exception message.
 *
 * @return string Returns the field names or the value type.
 */
function describe_data_fields($data): string
{
    if (is_array($data))
    {
        return '[' . implode(', ', array_map('strval', array_keys($data))) . ']';
    }

    return gettype($data);
}
