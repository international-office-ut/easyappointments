<?php defined('BASEPATH') or exit('No direct script access allowed');

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

/**
 * Class Language
 *
 * Public language endpoint: the page language selector lives on every page (including the
 * public booking page), so it must not depend on backend-restricted routes.
 */
class Language extends EA_Controller {
    /**
     * Store the selected language on the session (validated against the available languages).
     */
    public function change_language()
    {
        try
        {
            $found = FALSE;

            foreach (config('available_languages') as $language)
            {
                if ($language === $this->input->post('language'))
                {
                    $found = TRUE;
                    break;
                }
            }

            if ( ! $found)
            {
                throw new Exception('Translations for the given language does not exist ('
                    . $this->input->post('language') . ').');
            }

            $this->session->set_userdata('language', $this->input->post('language'));
            $this->config->set_item('language', $this->input->post('language'));

            // Persist the picker choice beyond the PHP session (validated on read again).
            $this->input
                ->set_cookie('language', $this->input->post('language'), 6 * 30 * 86400);

            $response = AJAX_SUCCESS;
        }
        catch (Exception $exception)
        {
            $this->output->set_status_header(500);

            $response = [
                'message' => $exception->getMessage(),
                'trace' => config('debug') ? $exception->getTrace() : []
            ];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
}
