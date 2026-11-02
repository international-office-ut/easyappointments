<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Deployment;
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
            config(App::class)->defaultLocale = session()->get('language');

            return;
        }

        $languageCookie = $this->request->getCookie('language');

        if ($languageCookie !== NULL
            && in_array($languageCookie, config(Deployment::class)->available_languages, TRUE))
        {
            session()->set('language', $languageCookie);
            config(App::class)->defaultLocale = $languageCookie;
        }
    }
}
