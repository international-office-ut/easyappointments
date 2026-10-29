<?php defined('BASEPATH') or exit('No direct script access allowed');

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

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\HmacAlgorithm;

/**
 * Captcha Controller
 *
 * Returns ALTCHA proof-of-work challenges for the booking page (see https://altcha.org).
 *
 * @package Controllers
 */
class Captcha extends EA_Controller {
    /**
     * Return a new ALTCHA challenge as JSON.
     */
    public function index()
    {
        if ( ! defined('\Config::ALTCHA_HMAC_KEY') || Config::ALTCHA_HMAC_KEY === '')
        {
            show_error('The "ALTCHA_HMAC_KEY" configuration value is not set. See the "config.php" file.', 500);
        }

        $altcha = new Altcha(hmacSignatureSecret: Config::ALTCHA_HMAC_KEY);

        $challenge = $altcha->createChallenge(new CreateChallengeOptions(
            algorithm: new Pbkdf2(HmacAlgorithm::SHA256),
            cost: 5000,
            counter: random_int(5000, 10000),
            expiresAt: time() + 900,
        ));

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($challenge));
    }
}
