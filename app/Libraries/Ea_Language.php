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

namespace App\Libraries;

use Exception;

use CodeIgniter\Language\Language;

/**
 * Easy!Appointments language layer on top of the CI4 Language service.
 *
 * The CI4 `lang()` calls in the ported EA stack use bare key names
 * (`lang('appointment_booked')`), while the CI4 loader expects "file.key"
 * dotted names — a bare line is echoed verbatim otherwise. This class maps
 * both conventions onto the translations file of the active locale, so the
 * views and the backend keep reading the translation sources single-source.
 */
class Ea_Language extends Language {
    public function __construct(string $locale)
    {
        parent::__construct($locale);
    }

    public function getLine(string $line, array $args = [])
    {
        if (! str_contains($line, '.'))
        {
            $line = 'Translations.' . $line;
        }

        return parent::getLine($line, $args);
    }

    /**
     * All translated lines of the active locale (keys conform to the bridge convention).
     */
    public function all(): array
    {
        return $this->language[$this->locale]['Translations'] ?? [];
    }
}
