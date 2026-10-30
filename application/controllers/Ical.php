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

use Jsvrcek\ICS\CalendarExport;
use Jsvrcek\ICS\CalendarStream;
use Jsvrcek\ICS\Model\Calendar;
use Jsvrcek\ICS\Model\CalendarEvent;
use Jsvrcek\ICS\Model\Description\Location;
use Jsvrcek\ICS\Utility\Formatter;

/**
 * Class Ical
 *
 * Serves the provider calendar feeds: calendar clients (Outlook, Google Calendar etc.) poll
 * the feed URL with the personal token of the provider and receive the appointments as an
 * iCalendar subscription.
 *
 * The token lives in the URL path, only its hash is stored and every request is rate
 * limited, so guessing tokens is not viable and a leaked URL can be revoked by the
 * provider from the backend.
 */
class Ical extends EA_Controller {
    /**
     * Request rate limit per token and IP.
     */
    private const RATE_LIMIT_HITS = 180;

    /**
     * Rate limit window in seconds.
     */
    private const RATE_LIMIT_WINDOW = 3600;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->helper('appointment_location');
    }

    /**
     * Feed endpoint of the provider calendar subscription.
     *
     * @param string|null $token The feed token (base64url, optional ".ics" suffix).
     */
    public function fetch($token = NULL)
    {
        if ($token === NULL)
        {
            show_error('Missing calendar feed token.', 403);
        }

        $token = preg_replace('/\.ics$/', '', (string)$token);

        if ($this->rate_limit_exceeded($token))
        {
            show_error('Too many feed requests.', 429);
        }

        $provider_id = $this->providers_model->get_provider_id_by_feed_token($token);

        if ( ! $provider_id)
        {
            show_error('The calendar feed token is not valid.', 404);
        }

        $provider = $this->providers_model->get_row($provider_id);
        $appointments = $this->get_feed_appointments($provider_id);

        $stream = $this->build_feed_stream($provider, $appointments);

        $etag = 'W/"' . sha1($stream) . '"';

        $this->output->set_header('Content-Type: text/calendar; charset=utf-8');
        $this->output->set_header('Content-Disposition: inline; '
            . 'filename="easyappointments-' . preg_replace('/[^a-z0-9_-]/i', '-', $provider['first_name'] . '-'
            . $provider['last_name']) . '.ics"');
        $this->output->set_header('Cache-Control: private, no-store');
        $this->output->set_header('ETag: ' . $etag);

        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag)
        {
            $this->output->set_status_header(304);

            return;
        }

        $this->output->set_status_header(200);
        $this->output->set_output($stream);
    }

    /**
     * Fetch the appointments of the feed window (configurable in config.php).
     *
     * @param int $provider_id Provider record id.
     *
     * @return array Returns the appointments with the service name resolved.
     */
    protected function get_feed_appointments(int $provider_id): array
    {
        $past_days = defined('\Config::ICAL_FEED_RANGE_PAST') ? Config::ICAL_FEED_RANGE_PAST : 30;
        $future_days = defined('\Config::ICAL_FEED_RANGE_FUTURE') ? Config::ICAL_FEED_RANGE_FUTURE : 365;

        return $this->db
            ->select('appointments.*, services.name AS service_name')
            ->from('appointments')
            ->join('services', 'services.id = appointments.id_services', 'inner')
            ->where('appointments.id_users_provider', $provider_id)
            ->where('appointments.is_unavailable', FALSE)
            ->where('appointments.end_datetime >=', (new DateTime('now'))
                ->modify('-' . max(0, (int)$past_days) . ' days')->format('Y-m-d H:i:s'))
            ->where('appointments.start_datetime <=', (new DateTime('now'))
                ->modify('+' . max(0, (int)$future_days) . ' days')->format('Y-m-d H:i:s'))
            ->order_by('appointments.start_datetime', 'ASC')
            ->get()
            ->result_array();
    }

    /**
     * Build the iCalendar stream of the feed events.
     *
     * The feed deliberately stays minimal: only the appointment facts that the provider
     * manages in the backend are included, no customer contact information.
     *
     * @param array $provider Provider record (timezone matters).
     * @param array $appointments Appointments with the resolved service name.
     *
     * @return string Returns the rendered iCalendar stream.
     */
    protected function build_feed_stream(array $provider, array $appointments): string
    {
        $timezone = new DateTimeZone($provider['timezone'] ?? 'UTC');

        $calendar = new Calendar();
        $calendar
            ->setProdId('-//EasyAppointments//Open Source Web Scheduler//EN')
            ->setTimezone($timezone)
            ->setName($provider['first_name'] . ' ' . $provider['last_name']);

        foreach ($appointments as $appointment)
        {
            $event = new CalendarEvent();
            $event
                ->setStart(new DateTime($appointment['start_datetime'], $timezone))
                ->setEnd(new DateTime($appointment['end_datetime'], $timezone))
                ->setStatus('CONFIRMED')
                ->setSummary($appointment['service_name'])
                ->setUid((string)$appointment['id']);

            $location_value = $appointment['location'] ?? '';

            if ($location_value)
            {
                $location = new Location();
                $location->setName((string)$location_value);
                $event->addLocation($location);
            }

            $calendar->addEvent($event);
        }

        $calendar_export = new CalendarExport(new CalendarStream, new Formatter());
        $calendar_export->addCalendar($calendar);

        return $calendar_export->getStream();
    }

    /**
     * Rate limit path points into the storage cache directory (same scheme as the login
     * limiter of the user controller).
     *
     * @return string
     */
    protected function rate_limit_path(string $key): string
    {
        return implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'storage', 'cache',
            sha1($key) . '.ratelimit']);
    }

    /**
     * Returns whether the feed requests are over the accepted rate.
     *
     * @param string $token The feed token of the request.
     *
     * @return bool
     */
    protected function rate_limit_exceeded(string $token): bool
    {
        $keys = [
            'ical|client|' . $this->input->ip_address(),
            'ical|token|' . sha1($token)
        ];

        $expires = time() + self::RATE_LIMIT_WINDOW;

        foreach ($keys as $key)
        {
            if ($this->rate_limit_hits($key) >= self::RATE_LIMIT_HITS)
            {
                return TRUE;
            }
        }

        foreach ($keys as $key)
        {
            $path = $this->rate_limit_path($key);
            $record = @json_decode((string)@file_get_contents($path), TRUE);

            if ( ! is_array($record) || empty($record['expires']) || $record['expires'] < time())
            {
                $record = ['hits' => 0, 'expires' => $expires];
            }

            $record['hits']++;

            @file_put_contents($path, json_encode($record), LOCK_EX);
        }

        return FALSE;
    }

    /**
     * Read the current hit counter of a rate limit key.
     *
     * @param string $key Rate limit key.
     *
     * @return int
     */
    protected function rate_limit_hits(string $key): int
    {
        $record = @json_decode((string)@file_get_contents($this->rate_limit_path($key)), TRUE);

        if ( ! is_array($record) || empty($record['expires']) || $record['expires'] < time())
        {
            return 0;
        }

        return (int)($record['hits'] ?? 0);
    }
}
