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
use CodeIgniter\Config\BaseService;
use CodeIgniter\Language\LanguageInterface;

/**
 * Services Overrides
 *
 * (CI4 port of the CI3 language bootstrapping into a custom Language service
 * so that the bare `lang('key')` style of the EA code works on both stacks.)
 */
class Services extends BaseService {
    public static function language(?string $locale = null, bool $getShared = true): LanguageInterface | Language
    {
        if ($getShared)
        {
            return static::getSharedInstance('language', $locale);
        }

        $locale = $locale ?? config(App::class)->defaultLocale;

        return new Ea_Language($locale);
    }
}
