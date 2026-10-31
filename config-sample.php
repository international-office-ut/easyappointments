<?php
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
 * Easy!Appointments Configuration File
 *
 * Set your installation BASE_URL * without the trailing slash * and the database
 * credentials in order to connect to the database. You can enable the DEBUG_MODE
 * while developing the application.
 *
 * Set the default language by changing the LANGUAGE constant. For a full list of
 * available languages look at the /application/config/config.php file.
 *
 * IMPORTANT:
 * If you are updating from version 1.0 you will have to create a new "config.php"
 * file because the old "configuration.php" is not used anymore.
 */
class Config {

    // ------------------------------------------------------------------------
    // GENERAL SETTINGS
    // ------------------------------------------------------------------------

    const BASE_URL      = 'http://url-to-easyappointments-directory';
    const DEBUG_MODE    = FALSE;
    const ALTCHA_HMAC_KEY = '';  // Required when CAPTCHA is enabled; openssl rand -hex 32

    // ------------------------------------------------------------------------
    // EMAIL (SMTP)
    // ------------------------------------------------------------------------

    const EMAIL_PROTOCOL = 'smtp'; // or 'mail'
    const SMTP_HOST      = 'smtp.example.com';
    const SMTP_PORT      = 587;
    const SMTP_AUTH      = FALSE;   // set TRUE when the server requires authentication
    const SMTP_USER      = '';      // leave empty for anonymous relay
    const SMTP_PASS      = '';
    const SMTP_CRYPTO    = 'tls';   // '', 'ssl' or 'tls'

    // ------------------------------------------------------------------------
    // SHIBBOLETH (SSO)
    // ------------------------------------------------------------------------

    const SHIB_ENABLED     = FALSE;
    const SHIB_ATTR_EMAIL  = 'mail';          // or 'eppn' (released by the IdP)
    const SHIB_ATTR_NAME   = 'displayName';   // optional, '' to skip
    const SHIB_PASSWORD_LOGIN = TRUE;         // FALSE forbids the username/password login
    const SHIB_LOGIN_PATH  = '/Shibboleth.sso/Login';
    const SHIB_LOGOUT_PATH = '/Shibboleth.sso/Logout';

    // ------------------------------------------------------------------------
    // PROVIDER CALENDAR FEED (ICAL)
    // ------------------------------------------------------------------------

    const ICAL_FEED_RANGE_PAST  = 30;    // days of past appointments included
    const ICAL_FEED_RANGE_FUTURE = 365;  // days of upcoming appointments included
    const ICAL_FEED_INCLUDE_CUSTOMER = TRUE;  // show the customer name, phone and email on feed events

    // ------------------------------------------------------------------------
    // INSTALLATION
    // ------------------------------------------------------------------------

    const INSTALL_ALLOWED_RANGES = [];  // extra CIDR ranges that may run the setup, e.g. ['203.0.113.0/24', '2001:db8::/32'] — loopback and private LAN addresses are always allowed

    // ------------------------------------------------------------------------
    // DATABASE SETTINGS
    // ------------------------------------------------------------------------

    const DB_HOST       = 'localhost';
    const DB_NAME       = 'easyappointments';
    const DB_USERNAME   = 'root';
    const DB_PASSWORD   = 'root';

    // ------------------------------------------------------------------------
}

/* End of file config.php */
/* Location: ./config.php */
