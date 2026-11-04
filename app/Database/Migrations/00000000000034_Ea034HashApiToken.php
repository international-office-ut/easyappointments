<?php

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

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Class Migration_Ea034HashApiToken
 *
 * Converts the stored API token to its SHA-256 hash, so that the secret only lives at rest
 * in hashed form and the settings page cannot display it anymore. An empty value (no token
 * configured) stays empty and the API keeps failing closed until a token is generated again.
 *
 * @property CI_DB_query_builder $db
 * @property CI_DB_forge $dbforge
 */
class Migration_Ea034HashApiToken extends Migration {
    /**
     * Upgrade method.
     */
    public function up()
    {
        $current_value = $this->db->table('settings')
            ->select('value')
            ->where('name', 'api_token')
            ->get()
            ->getRowArray()['value'] ?? '';

        if ($current_value === '')
        {
            return;
        }

        // Values that already are a SHA-256 hash stay untouched (the migration is idempotent).
        if (preg_match('/^[a-f0-9]{64}$/', $current_value))
        {
            return;
        }

        $this->db->table('settings')
            ->where('name', 'api_token')
            ->update(['value' => hash('sha256', $current_value)]);
    }

    /**
     * Downgrade method.
     */
    public function down()
    {
        // Nothing to restore: the plaintext of the previous token is not stored anymore
        // and a new token can simply be generated in the settings page.
    }
}
