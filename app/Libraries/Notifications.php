<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.4.0
 * ---------------------------------------------------------------------------- */

namespace App\Libraries;

use Exception;

use App\Models\Admins_Model;
use App\Models\Appointments_Model;
use App\Models\Providers_Model;
use App\Models\Secretaries_Model;
use App\Libraries\Timezones;
use App\Models\Settings_Model;
use Config\Deployment;
use EA\Engine\Notifications\Email as EmailClient;
use EA\Engine\Types\Email;
use EA\Engine\Types\Text;
use EA\Engine\Types\Url;

/**
 * Notifications (CI4 port of the CI3 application/libraries/Notifications.php)
 *
 * Handles the system notifications (mostly related to scheduling changes).
 */
class Notifications {
    public function __construct()
    {
    }

    /**
     * Build the email contractor with the CI4-native view renderer.
     *
     * @return EmailClient
     */
    protected function email_client(): EmailClient
    {
        $deployment = config(Deployment::class);

        $email_config = [
            'useragent' => 'Easy!Appointments',
            'protocol' => $deployment->email_protocol,
            'mailtype' => 'html',
            'smtp_debug' => '0',
            'smtp_auth' => $deployment->smtp_auth,
            'smtp_host' => $deployment->smtp_host,
            'smtp_crypto' => $deployment->smtp_crypto,
            'smtp_port' => $deployment->smtp_port,
            'smtp_user' => $deployment->smtp_user,
            'smtp_pass' => $deployment->smtp_pass,
            'charset' => 'UTF-8',
        ];

        $view_renderer = static fn (string $view, array $data = []): string
            => view($view, $data);

        return new EmailClient($email_config, $view_renderer, (new Timezones)->to_array());
    }

    /**
     * Read a POST value from the current request (the CI3 $this->CI->input->post path).
     *
     * @param string $key POST field name.
     *
     * @return mixed Returns the posted value (matches the CI3 FALSE on absence).
     */
    protected function post($key)
    {
        return service('request')->getPost($key);
    }

    /**
     * Send the required notifications, related to an appointment creation/modification.
     *
     * @param array $appointment Appointment record.
     * @param array $service Service record.
     * @param array $provider Provider record.
     * @param array $customer Customer record.
     * @param array $settings Required settings for the notification content.
     * @param bool|false $manage_mode
     */
    public function notify_appointment_saved($appointment, $service, $provider, $customer, $settings, $manage_mode = FALSE)
    {
        try
        {
            $email = $this->email_client();

            if ($manage_mode)
            {
                $customer_title = new Text(lang('appointment_changes_saved'));
                $customer_message = new Text('');
                $provider_title = new Text(lang('appointment_details_changed'));
                $provider_message = new Text('');
            }
            else
            {
                $customer_title = new Text(lang('appointment_booked'));
                $customer_message = new Text(lang('thank_you_for_appointment'));
                $provider_title = new Text(lang('appointment_added_to_your_plan'));
                $provider_message = new Text(lang('appointment_link_description'));
            }

            $customer_link = new Url(site_url('appointments/index/' . $appointment['hash']));
            $provider_link = new Url(site_url('backend/index/' . $appointment['hash']));

            $ics_stream = (new Ics_file)->get_stream($appointment, $service, $provider, $customer);

            $send_customer = filter_var(
                (new Settings_Model)->get_setting('customer_notifications'),
                FILTER_VALIDATE_BOOLEAN);

            if ($send_customer === TRUE)
            {
                $email->send_appointment_details($appointment, $provider,
                    $service, $customer, $settings, $customer_title,
                    $customer_message, $customer_link, new Email($customer['email']), new Text($ics_stream), $customer['timezone']);
            }

            $send_provider = filter_var(
                (new Providers_Model)->get_setting('notifications', $provider['id']),
                FILTER_VALIDATE_BOOLEAN);

            if ($send_provider === TRUE)
            {
                $email->send_appointment_details($appointment, $provider,
                    $service, $customer, $settings, $provider_title,
                    $provider_message, $provider_link, new Email($provider['email']), new Text($ics_stream), $provider['timezone']);
            }

            // Notify admins
            $admins = (new Admins_Model)->get_batch();

            foreach ($admins as $admin)
            {
                if ($admin['settings']['notifications'] === '0')
                {
                    continue;
                }

                $email->send_appointment_details($appointment, $provider,
                    $service, $customer, $settings, $provider_title,
                    $provider_message, $provider_link, new Email($admin['email']), new Text($ics_stream), $admin['timezone']);
            }

            // Notify secretaries
            $secretaries = (new Secretaries_Model)->get_batch();

            foreach ($secretaries as $secretary)
            {
                if ($secretary['settings']['notifications'] === '0')
                {
                    continue;
                }

                if (!in_array($provider['id'], $secretary['providers']))
                {
                    continue;
                }

                $email->send_appointment_details($appointment, $provider,
                    $service, $customer, $settings, $provider_title,
                    $provider_message, $provider_link, new Email($secretary['email']), new Text($ics_stream), $secretary['timezone']);
            }
        }
        catch (Exception $exception)
        {
            log_message('error', $exception->getMessage());
            log_message('error', $exception->getTraceAsString());
        }
    }

    /**
     * Send the required notifications, related to an appointment removal.
     *
     * @param array $appointment Appointment record.
     * @param array $service Service record.
     * @param array $provider Provider record.
     * @param array $customer Customer record.
     * @param array $settings Required settings for the notification content.
     */
    public function notify_appointment_deleted($appointment, $service, $provider, $customer, $settings)
    {
        // Send email notification to customer and provider.
        try
        {
            $email = $this->email_client();

            $send_provider = filter_var((new Providers_Model)->get_setting('notifications', $provider['id']),
                FILTER_VALIDATE_BOOLEAN);

            if ($send_provider === TRUE)
            {
                $email->send_delete_appointment($appointment, $provider,
                    $service, $customer, $settings, new Email($provider['email']),
                    new Text($this->post('cancel_reason')));
            }

            $send_customer = filter_var(
                (new Settings_Model)->get_setting('customer_notifications'),
                FILTER_VALIDATE_BOOLEAN);

            if ($send_customer === TRUE)
            {
                $email->send_delete_appointment($appointment, $provider,
                    $service, $customer, $settings, new Email($customer['email']),
                    new Text($this->post('cancel_reason')));
            }

            // Notify admins
            $admins = (new Admins_Model)->get_batch();

            foreach ($admins as $admin)
            {
                if ($admin['settings']['notifications'] === '0')
                {
                    continue;
                }

                $email->send_delete_appointment($appointment, $provider,
                    $service, $customer, $settings, new Email($admin['email']),
                    new Text($this->post('cancel_reason')));
            }

            // Notify secretaries
            $secretaries = (new Secretaries_Model)->get_batch();

            foreach ($secretaries as $secretary)
            {
                if ($secretary['settings']['notifications'] === '0')
                {
                    continue;
                }

                if (!in_array($provider['id'], $secretary['providers']))
                {
                    continue;
                }

                $email->send_delete_appointment($appointment, $provider,
                    $service, $customer, $settings, new Email($secretary['email']),
                    new Text($this->post('cancel_reason')));
            }
        }
        catch (Exception $exception)
        {
            $exceptions[] = $exception;
        }
    }
}
