<?php

/*
 | --------------------------------------------------------------------
 | App Namespace
 | --------------------------------------------------------------------
 |
 | This defines the default Namespace that is used throughout
 | CodeIgniter to refer to the Application directory. Change
 | this constant to change the namespace that all application
 | classes should use.
 |
 | NOTE: changing this will require manually modifying the
 | existing namespaces of App\* namespaced-classes.
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
 | --------------------------------------------------------------------------
 | Composer Path
 | --------------------------------------------------------------------------
 |
 | The path that Composer's autoload file is expected to live. By default,
 | the vendor folder is in the Root directory, but you can customize that here.
 */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', ROOTPATH . 'vendor/autoload.php');

/*
 |--------------------------------------------------------------------------
 | Timing Constants
 |--------------------------------------------------------------------------
 |
 | Provide simple ways to work with the myriad of PHP functions that
 | require information to be in seconds.
 */
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR')   || define('HOUR', 3600);
defined('DAY')    || define('DAY', 86400);
defined('WEEK')   || define('WEEK', 604800);
defined('MONTH')  || define('MONTH', 2_592_000);
defined('YEAR')   || define('YEAR', 31_536_000);
defined('DECADE') || define('DECADE', 315_360_000);

/*
 | --------------------------------------------------------------------------
 | Exit Status Codes
 | --------------------------------------------------------------------------
 |
 | Used to indicate the conditions under which the script is exit()ing.
 | While there is no universal standard for error codes, there are some
 | broad conventions.  Three such conventions are mentioned below, for
 | those who wish to make use of them.  The CodeIgniter defaults were
 | chosen for the least overlap with these conventions, while still
 | leaving room for others to be defined in future versions and user
 | applications.
 |
 | The three main conventions used for determining exit status codes
 | are as follows:
 |
 |    Standard C/C++ Library (stdlibc):
 |       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
 |       (This link also contains other GNU-specific conventions)
 |    BSD sysexits.h:
 |       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
 |    Bash scripting:
 |       http://tldp.org/LDP/abs/html/exitcodes.html
 |
 */
defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS', 0);        // no errors
defined('EXIT_ERROR')          || define('EXIT_ERROR', 1);          // generic error
defined('EXIT_CONFIG')         || define('EXIT_CONFIG', 3);         // configuration error
defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE', 4);   // file not found
defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS', 5);  // unknown class
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT', 7);     // invalid user input
defined('EXIT_DATABASE')       || define('EXIT_DATABASE', 8);       // database error
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN', 9);      // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX', 125);    // highest automatically-assigned error code

// Easy!Appointments constants (single CI4 bootstrap home).
defined('DB_SLUG_CUSTOMER')       || define('DB_SLUG_CUSTOMER', 'customer');
defined('DB_SLUG_PROVIDER')       || define('DB_SLUG_PROVIDER', 'provider');
defined('DB_SLUG_ADMIN')          || define('DB_SLUG_ADMIN', 'admin');
defined('DB_SLUG_SECRETARY')      || define('DB_SLUG_SECRETARY', 'secretary');

defined('FILTER_TYPE_PROVIDER')   || define('FILTER_TYPE_PROVIDER', 'provider');
defined('FILTER_TYPE_SERVICE')    || define('FILTER_TYPE_SERVICE', 'service');

defined('AJAX_SUCCESS')           || define('AJAX_SUCCESS', 'SUCCESS');
defined('AJAX_FAILURE')           || define('AJAX_FAILURE', 'FAILURE');

defined('SETTINGS_SYSTEM')        || define('SETTINGS_SYSTEM', 'SETTINGS_SYSTEM');
defined('SETTINGS_USER')          || define('SETTINGS_USER', 'SETTINGS_USER');

defined('PRIV_VIEW')              || define('PRIV_VIEW', 1);
defined('PRIV_ADD')               || define('PRIV_ADD', 2);
defined('PRIV_EDIT')              || define('PRIV_EDIT', 4);
defined('PRIV_DELETE')            || define('PRIV_DELETE', 8);

defined('PRIV_APPOINTMENTS')      || define('PRIV_APPOINTMENTS', 'appointments');
defined('PRIV_CUSTOMERS')         || define('PRIV_CUSTOMERS', 'customers');
defined('PRIV_SERVICES')          || define('PRIV_SERVICES', 'services');
defined('PRIV_USERS')             || define('PRIV_USERS', 'users');
defined('PRIV_SYSTEM_SETTINGS')   || define('PRIV_SYSTEM_SETTINGS', 'system_settings');
defined('PRIV_USER_SETTINGS')     || define('PRIV_USER_SETTINGS', 'user_settings');

defined('DATE_FORMAT_DMY')        || define('DATE_FORMAT_DMY', 'DMY');
defined('DATE_FORMAT_DDMY')       || define('DATE_FORMAT_DDMY', 'DDMY');
defined('DATE_FORMAT_MDY')        || define('DATE_FORMAT_MDY', 'MDY');
defined('DATE_FORMAT_YMD')        || define('DATE_FORMAT_YMD', 'YMD');

defined('TIME_FORMAT_REGULAR')    || define('TIME_FORMAT_REGULAR', 'regular');
defined('TIME_FORMAT_MILITARY')   || define('TIME_FORMAT_MILITARY', 'military');

defined('MIN_PASSWORD_LENGTH')    || define('MIN_PASSWORD_LENGTH', 7);
defined('ANY_PROVIDER')           || define('ANY_PROVIDER', 'any-provider');

defined('CALENDAR_VIEW_DEFAULT')  || define('CALENDAR_VIEW_DEFAULT', 'default');
defined('CALENDAR_VIEW_TABLE')    || define('CALENDAR_VIEW_TABLE', 'table');

defined('AVAILABILITIES_TYPE_FLEXIBLE') || define('AVAILABILITIES_TYPE_FLEXIBLE', 'flexible');
defined('AVAILABILITIES_TYPE_FIXED')    || define('AVAILABILITIES_TYPE_FIXED', 'fixed');

defined('APPOINTMENT_MODE_ONLINE')      || define('APPOINTMENT_MODE_ONLINE', 'ONLINE');
defined('APPOINTMENT_MODE_ON_PREMISE')  || define('APPOINTMENT_MODE_ON_PREMISE', 'ON_PREMISE');
defined('APPOINTMENT_MODE_BOTH')        || define('APPOINTMENT_MODE_BOTH', 'BOTH');

defined('EVENT_MINIMUM_DURATION') || define('EVENT_MINIMUM_DURATION', 5); // Minutes
