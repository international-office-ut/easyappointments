<?php

namespace App\Controllers;

use Exception;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\Notifications;
use App\Libraries\Timezones;
use Config\Deployment;
use EA\Engine\Notifications\Email as EmailClient;
use Psr\Log\LoggerInterface;

/**
 * Easy!Appointments base controller: language bootstrap. The session
 * choice wins, then the "language" cookie, and the framework locale is
 * set accordingly (browser detection belongs to \Config\App::$defaultLocale).
 */
abstract class BaseController extends Controller
{
    /**
     * @param CLIRequest|IncomingRequest $request
     * @param ResponseInterface $response
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Caution: do not move this below the language logic, framework
        // initialization must happen first.
        parent::initController($request, $response, $logger);

        $this->configure_language();
    }

    /**
     * Configure the language: the picker choice of the session wins, then the persisted
     * cookie, then the browser detection of the config bootstrap (German browsers get
     * German, everything else English).
     */
    private function configure_language()
    {
        if (session()->has('language'))
        {
            config(\Config\App::class)->defaultLocale = session()->get('language');
            service('language')->setLocale(session()->get('language'));

            return;
        }

        $languageCookie = $this->request->getCookie('language');

        if ($languageCookie !== NULL
            && in_array($languageCookie, config(Deployment::class)->available_languages, TRUE))
        {
            session()->set('language', $languageCookie);
            config(\Config\App::class)->defaultLocale = $languageCookie;
            service('language')->setLocale($languageCookie);
        }
    }

    /**
     * Normalize an exception for AJAX JSON responses.
     *
     * @param Exception $exception
     * @return array
     */
    protected function ajax_exception(Exception $exception): array
    {
        return [
            'message' => $exception->getMessage(),
            'trace' => ea_config('debug') ? $exception->getTrace() : []
        ];
    }

    /**
     * Build the email client for the CI4 stack.
     */
    protected function buildEmailClient(): EmailClient
    {
        return Notifications::email_client();
    }
}
