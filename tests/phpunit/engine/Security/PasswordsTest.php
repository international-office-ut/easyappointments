<?php

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

namespace EA\Engine\Security;

use PHPUnit\Framework\TestCase;

class PasswordsTest extends TestCase {
    public function test_mod_hashes_carry_the_algorithm_and_verify()
    {
        $hash = Passwords::hash('secret-password');

        $this->assertTrue(Passwords::isMod($hash));
        $this->assertNotSame('secret-password', $hash);
        $this->assertTrue(Passwords::verify('secret-password', '', $hash));
        $this->assertFalse(Passwords::verify('wrong-password', '', $hash));
    }

    public function test_hash_is_self_salted_and_nondeterministic()
    {
        $first = Passwords::hash('same-password');
        $second = Passwords::hash('same-password');

        $this->assertNotSame($first, $second);
        $this->assertTrue(Passwords::verify('same-password', '', $first));
        $this->assertTrue(Passwords::verify('same-password', '', $second));
    }

    public function test_legacy_digest_verifies_with_the_stored_salt()
    {
        $salt = str_repeat('a', 64);
        $legacy = hash('sha256', substr($salt, 0, 32) . 'legacy-password' . substr($salt, 32));

        for ($i = 0; $i < 100000; $i++)
        {
            $legacy = hash('sha256', $legacy);
        }

        $this->assertFalse(Passwords::isMod($legacy));
        $this->assertTrue(Passwords::verify('legacy-password', $salt, $legacy));
        $this->assertFalse(Passwords::verify('other-password', $salt, $legacy));
    }

    public function test_legacy_verification_requires_the_salt()
    {
        $this->expectException(\InvalidArgumentException::class);

        Passwords::verify('legacy-password', '', str_repeat('0', 64));
    }

    public function test_empty_hashes_never_verify()
    {
        $this->assertFalse(Passwords::verify('anything', 'salt', ''));
    }

    public function test_needs_rehash_for_legacy_and_modern()
    {
        $this->assertTrue(Passwords::needsRehash(str_repeat('0', 64)));

        $modern = Passwords::hash('x');
        $this->assertFalse(Passwords::needsRehash($modern));
    }

    public function test_algorithm_drift_schedules_rehash()
    {
        if ( ! in_array('argon2id', password_algos(), true))
        {
            $this->markTestSkipped('argon2id not available on this runtime.');
        }

        $bcrypt = password_hash('drift', PASSWORD_BCRYPT);

        $this->assertTrue(str_starts_with($bcrypt, '$2y$'));
        $this->assertTrue(Passwords::needsRehash($bcrypt));
        $this->assertTrue(Passwords::verify('drift', '', $bcrypt));
    }
}
