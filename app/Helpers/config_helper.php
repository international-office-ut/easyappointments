<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.3.0
 * ---------------------------------------------------------------------------- */

use Config\Deployment;

/**
 * Fetch Easy!Appointments configuration values by the CI3 names.
 *
 * The CodeIgniter 4 "config()" helper is reserved for config classes, so the
 * application keeps its own name here; the call sites stay segment-identical.
 *
 * @param string $key Configuration key ("debug", "base_url", ...).
 *
 * @return mixed Returns the configuration value.
 */
function ea_config($key)
{
    switch ($key)
    {
        case 'debug':                      return config(Deployment::class)->debug_mode;
        case 'cache_busting_token':        return config(Deployment::class)->cache_busting_token;
        case 'version':                    return config(Deployment::class)->version;
        case 'release_label':              return config(Deployment::class)->release_label;
        case 'available_languages':        return config(Deployment::class)->available_languages;
        case 'language':                   return config(App::class)->defaultLocale;
        case 'base_url':                   return rtrim((string) env('app.baseURL', ''), '/') . '/';

        default:
            throw new LogicException('Unknown configuration key: ' . $key);
    }
}

/**
 * Fetch a system setting quickly from the CI3-style magic in the old view data.
 *
 * The CI3 views called "$this->settings_model->get_setting(...)" from within the
 * template scope; the ported views use this tiny helper instead so that no template
 * magic reaches the model layer.
 *
 * @param string $name The database setting name.
 *
 * @return string Returns the setting value.
 */
function ea_setting($name)
{
    return (new \App\Models\Settings_Model)->get_setting($name);
}

/**
 * All translated lines of the active locale (the CI3 "$this->lang->language" parity).
 *
 * @return array
 */
function ea_langs()
{
    return service('language')->all();
}
