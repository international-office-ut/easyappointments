<?php

namespace App\Controllers;

use Exception;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\Timezones;
use Config\Deployment;
use EA\Engine\Notifications\Email as EmailClient;
use Psr\Log\LoggerInterface;

/**
 * Easy!Appointments base controller: ports the CI3 EA_Controller
 * (application/core/EA_Controller.php) language bootstrap to CI4. The
 * logic is verbatim: the session choice wins, then the "language" cookie,
 * and the framework locale is set accordingly (the browser detection part of
 * the CI3 config.php bootstrap belongs to the CI4 App::$defaultLocale).
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
     * Build the CI3-shaped email client for the CI4 stack.
     */
    protected function buildEmailClient(): EmailClient
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
}
