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

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Deployment values that the legacy root "config.php" used to own.
 *
 * Everything is read from the environment at boot, so tracked sources never
 * carry deployment secrets. Keep the same env variable names as previous
 * releases, so that existing deployment files keep working.
 */
class Deployment extends BaseConfig
{
    public bool $debug_mode = FALSE;

    public ?string $altcha_hmac_key = NULL;

    public ?string $email_protocol = NULL;
    public ?string $smtp_host = NULL;
    public int $smtp_port = 587;
    public bool $smtp_auth = FALSE;
    public ?string $smtp_user = NULL;
    public ?string $smtp_pass = NULL;
    public string $smtp_crypto = 'tls';

    public bool $shib_enabled = FALSE;
    public string $shib_attr_email = 'mail';
    public string $shib_attr_name = 'displayName';
    public bool $shib_password_login = TRUE;
    public string $shib_login_path = '/Shibboleth.sso/Login';
    public string $shib_logout_path = '/Shibboleth.sso/Logout';

    public int $ical_feed_range_past = 30;
    public int $ical_feed_range_future = 365;
    public bool $ical_feed_include_customer = TRUE;

    public array $install_allowed_ranges = [];

    /** EA version string (release identity, see the packaging task). */
    public string $version = '2.1.0';

    public string $release_label = '';

    /** Asset cache-busting token, bumped per release. */
    public string $cache_busting_token = 'ZW018';

    /** Languages that the frontends and the backend user settings expose. */
    public array $available_languages = ['english', 'german'];

    public function __construct()
    {
        parent::__construct();

        $this->debug_mode = self::env_bool('DEBUG_MODE', FALSE);
        $this->altcha_hmac_key = (string) env('ALTCHA_HMAC_KEY', '');

        $this->email_protocol = (string) env('EMAIL_PROTOCOL', 'smtp');
        $this->smtp_host = (string) env('SMTP_HOST', '');
        $this->smtp_port = (int) env('SMTP_PORT', 587);
        $this->smtp_auth = self::env_bool('SMTP_AUTH', FALSE);
        $this->smtp_user = (string) env('SMTP_USER', '');
        $this->smtp_pass = (string) env('SMTP_PASS', '');
        $this->smtp_crypto = (string) env('SMTP_CRYPTO', 'tls');

        $this->shib_enabled = self::env_bool('SHIB_ENABLED', FALSE);
        $this->shib_attr_email = (string) env('SHIB_ATTR_EMAIL', 'mail');
        $this->shib_attr_name = (string) env('SHIB_ATTR_NAME', 'displayName');
        $this->shib_password_login = self::env_bool('SHIB_PASSWORD_LOGIN', TRUE);
        $this->shib_login_path = (string) env('SHIB_LOGIN_PATH', '/Shibboleth.sso/Login');
        $this->shib_logout_path = (string) env('SHIB_LOGOUT_PATH', '/Shibboleth.sso/Logout');

        $this->ical_feed_range_past = (int) env('ICAL_FEED_RANGE_PAST', 30);
        $this->ical_feed_range_future = (int) env('ICAL_FEED_RANGE_FUTURE', 365);
        $this->ical_feed_include_customer = self::env_bool('ICAL_FEED_INCLUDE_CUSTOMER', TRUE);

        $ranges = (string) env('INSTALL_ALLOWED_RANGES', '');
        $this->install_allowed_ranges = $ranges === '' ? [] : array_filter(array_map('trim', explode(',', $ranges)));

        $this->version = (string) env('EA_VERSION', $this->version);
        $this->release_label = (string) env('EA_RELEASE_LABEL', '');
        $this->cache_busting_token = (string) env('EA_CACHE_BUSTING_TOKEN', 'ZW018');
    }

    /**
     * Boolean env reader.
     *
     * Accepts the truthy/falsy spellings used by deployment files (true/false,
     * 1/0, yes/no) because a plain "(bool)" cast would happily evaluate the
     * string "false" as TRUE.
     *
     * @param string $key Env variable name.
     * @param bool $fallback Value used when the key is absent or empty.
     *
     * @return bool Returns the parsed boolean.
     */
    protected static function env_bool(string $key, bool $fallback): bool
    {
        $value = env($key);

        if ($value === NULL || $value === '')
        {
            return $fallback;
        }

        if (is_bool($value))
        {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], TRUE);
    }
}
