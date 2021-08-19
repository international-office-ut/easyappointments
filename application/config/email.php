<?php defined('BASEPATH') or exit('No direct script access allowed');

// Add custom values by settings them to the $config array.
// Example: $config['smtp_host'] = 'smtp.gmail.com';
// @link https://codeigniter.com/user_guide/libraries/email.html

$config['useragent'] = 'Easy!Appointments';
$config['protocol'] = 'smtp'; // or 'mail'
$config['mailtype'] = 'html'; // or 'text'
$config['smtp_debug'] = '0'; // or '1'
$config['smtp_auth'] = FALSE; // for anonymous relay or TRUE.
$config['smtp_host'] = 'smtpserv.uni-tuebingen.de';
$config['smtp_crypto'] = 'tls'; // or 'ssl'
$config['smtp_port'] = 587;
// $config['smtp_user'] = '';
// $config['smtp_pass'] = '';