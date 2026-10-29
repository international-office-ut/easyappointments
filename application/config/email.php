<?php defined('BASEPATH') or exit('No direct script access allowed');

// Add custom values by settings them to the $config array.
// Example: $config['smtp_host'] = 'smtp.gmail.com';
// @link https://codeigniter.com/user_guide/libraries/email.html
//
// Server-specific values (host, credentials) live in the untracked "config.php" file
// (Config class), so that this tracked file carries no deployment secrets.

$config['useragent'] = 'Easy!Appointments';
$config['protocol'] = Config::EMAIL_PROTOCOL; // or 'mail'
$config['mailtype'] = 'html'; // or 'text'
$config['smtp_debug'] = '0'; // or '1'
$config['smtp_auth'] = Config::SMTP_AUTH; // for anonymous relay or TRUE.
$config['smtp_host'] = Config::SMTP_HOST;
$config['smtp_crypto'] = Config::SMTP_CRYPTO; // or 'ssl'
$config['smtp_port'] = Config::SMTP_PORT;
$config['smtp_user'] = Config::SMTP_USER;
$config['smtp_pass'] = Config::SMTP_PASS;