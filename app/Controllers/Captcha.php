<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

namespace App\Controllers;

use Exception;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\HmacAlgorithm;
use Config\Deployment;

/**
 * Captcha Controller
 *
 * Returns ALTCHA proof-of-work challenges for the booking page (see https://altcha.org).
 *
 * (CI4 port of the CI3 application/controllers/Captcha.php)
 */
class Captcha extends BaseController {
    /**
     * Return a new ALTCHA challenge as JSON.
     */
    public function index()
    {
        if (config(Deployment::class)->altcha_hmac_key === '')
        {
            $this->response->setStatusCode(500);
            $this->response->setBody('The "ALTCHA_HMAC_KEY" configuration value is not set. See the env configuration.');
            $this->response->send();
            exit;
        }

        $altcha = new Altcha(hmacSignatureSecret: config(Deployment::class)->altcha_hmac_key);

        $challenge = $altcha->createChallenge(new CreateChallengeOptions(
            algorithm: new Pbkdf2(HmacAlgorithm::SHA256),
            cost: 5000,
            counter: random_int(5000, 10000),
            expiresAt: time() + 900,
        ));

        return $this->response
            ->setContentType('application/json')
            ->setBody(json_encode($challenge));
    }
}
