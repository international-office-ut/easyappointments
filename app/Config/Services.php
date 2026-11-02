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

use App\Libraries\Ea_Language;
use CodeIgniter\Language\Language as LanguageService;
use CodeIgniter\Language\LanguageInterface;
use CodeIgniter\Config\BaseService;

/**
 * Services Overrides
 *
 * (CI4 port: maps the CI3 bare-key language convention onto the CI4 Language
 * service, so the EA sources keep working with their own `lang('key')` style.)
 */
class Services extends BaseService {
    public static function language(?string $locale = null, bool $getShared = true): LanguageInterface | LanguageService
    {
        if ($getShared)
        {
            return static::getSharedInstance('language', $locale);
        }

        $locale = $locale ?? config(App::class)->defaultLocale;

        return new Ea_Language($locale);
    }
}
