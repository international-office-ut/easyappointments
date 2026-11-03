<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.4.2
 * ---------------------------------------------------------------------------- */

namespace App\Controllers;

use Exception;
/**
 * Language Controller
 *
 * Public language endpoint: the page language selector lives on every page (including the
 * public booking page), so it must not depend on backend-restricted routes.
 *

 */
class Language extends BaseController {
    /**
     * Store the selected language on the session (validated against the available languages).
     */
    public function change_language()
    {
        try
        {
            $found = FALSE;

            foreach (ea_config('available_languages') as $language)
            {
                if ($language === $this->request->getPost('language'))
                {
                    $found = TRUE;
                    break;
                }
            }

            if ( ! $found)
            {
                throw new Exception('Translations for the given language does not exist ('
                    . $this->request->getPost('language') . ').');
            }

            session()->set('language', $this->request->getPost('language'));
            config(\Config\App::class)->defaultLocale = $this->request->getPost('language');

            // Persist the picker choice beyond the PHP session (validated on read again).
            $this->response->setCookie('language', $this->request->getPost('language'), 6 * 30 * 86400);

            $response = AJAX_SUCCESS;
        }
        catch (Exception $exception)
        {
            $this->response->setStatusCode(500);

            $response = $this->ajax_exception($exception);
        }

        return $this->response->setContentType('application/json')->setBody(json_encode($response));
    }
}
