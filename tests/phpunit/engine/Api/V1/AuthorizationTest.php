<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.2.0
 * ---------------------------------------------------------------------------- */

namespace EA\Engine\Api\V1;

use PHPUnit\Framework\TestCase;

class AuthorizationTest extends TestCase {
    public function test_bearer_token_passes_on_equal_tokens()
    {
        $this->assertTrue(Authorization::verify_bearer('secret-token', 'secret-token'));
    }

    public function test_bearer_token_fails_on_mismatch()
    {
        $this->assertFalse(Authorization::verify_bearer('secret-token', 'wrong-token'));
    }

    public function test_bearer_token_requires_both_values()
    {
        $this->assertFalse(Authorization::verify_bearer('', 'secret-token'));
        $this->assertFalse(Authorization::verify_bearer('secret-token', NULL));
        $this->assertFalse(Authorization::verify_bearer(NULL, NULL));
    }

    public function test_admin_credentials_pass_for_admin_user()
    {
        Authorization::check_admin_credentials(
            'admin',
            'secret',
            function () {
                return ['role_slug' => 'admin'];
            },
            'admin'
        );

        $this->assertTrue(TRUE); // No exception reached this point.
    }

    public function test_admin_credentials_fail_on_wrong_password()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The provided credentials do not match any admin user!');

        Authorization::check_admin_credentials('admin', 'wrong', function () {
            return FALSE;
        }, 'admin');
    }

    public function test_admin_credentials_fail_for_non_admin_role()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The provided credentials do not match any admin user!');

        Authorization::check_admin_credentials('user', 'secret', function () {
            return ['role_slug' => 'secretary'];
        }, 'admin');
    }

    public function test_thrown_exception_carries_status_information()
    {
        try
        {
            Authorization::check_admin_credentials('user', 'secret', function () {
                return ['role_slug' => 'provider'];
            }, 'admin');

            $this->fail('Expected a 401 exception.');
        }
        catch (Exception $exception)
        {
            $this->assertSame(401, $exception->getCode());
            $this->assertSame('Unauthorized', $exception->get_header());
        }
    }
}
