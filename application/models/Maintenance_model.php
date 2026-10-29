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

/**
 * Maintenance Model
 *
 * Data retention purges: appointments, provider blocks (rows with `is_unavailable` flag),
 * and customers without appointments that are older than the configured retention period
 * in days. All purges run as direct SQL deletes (no notifications, no side effects). The
 * where clauses are applied directly on the delete statements: collecting thousands of
 * record ids first would blow up CI's query builder (regex length limits).
 *
 * @property CI_DB_query_builder $db
 */
class Maintenance_model extends EA_Model {
    /**
     * Purge appointments that end before the cutoff date.
     *
     * @param int $days Retention period in days.
     * @param bool $dry_run When TRUE the results are only reported and nothing is removed.
     *
     * @return int Returns the number of affected records.
     */
    public function purge_appointments(int $days, bool $dry_run = FALSE): int
    {
        if ( ! $this->db->table_exists('appointments'))
        {
            return 0;
        }

        $this->appointments_where($days);

        if ($dry_run)
        {
            return $this->db->count_all_results('appointments');
        }

        $this->appointments_where($days);
        $this->db->delete('appointments');

        return $this->db->affected_rows();
    }

    /**
     * Purge provider blocks ("unavailable" appointment rows) that end before the cutoff date.
     *
     * Blocked periods are stored inside the appointments table with the `is_unavailable`
     * flag set, so the purge targets those rows in the same way as the appointment purge.
     *
     * @param int $days Retention period in days.
     * @param bool $dry_run When TRUE the results are only reported and nothing is removed.
     *
     * @return int Returns the number of affected records.
     */
    public function purge_provider_blocks(int $days, bool $dry_run = FALSE): int
    {
        if ( ! $this->db->table_exists('appointments'))
        {
            return 0;
        }

        $this->provider_blocks_where($days);

        if ($dry_run)
        {
            return $this->db->count_all_results('appointments');
        }

        $this->provider_blocks_where($days);
        $this->db->delete('appointments');

        return $this->db->affected_rows();
    }

    /**
     * Purge customer users that have no appointments (anymore).
     *
     * Customers are user records with the customer role; their related rows (settings etc.)
     * are removed by the database cascades. Run the appointment purge first, so that
     * customers with only outdated appointments disappear within the same run.
     *
     * @param bool $dry_run When TRUE the results are only reported and nothing is removed.
     *
     * @return int Returns the number of affected records.
     */
    public function purge_customers_without_appointments(bool $dry_run = FALSE): int
    {
        if ( ! $this->db->table_exists('users'))
        {
            return 0;
        }

        $this->customers_where();

        if ($dry_run)
        {
            return $this->db->count_all_results('users');
        }

        $this->customers_where();
        $this->db->delete('users');

        return $this->db->affected_rows();
    }

    /**
     * Build the where clause for outdated appointment records.
     *
     * @param int $days Retention period in days.
     *
     * @return void
     */
    protected function appointments_where(int $days): void
    {
        $this->db->where('end_datetime <', $this->cutoff_date($days));
    }

    /**
     * Build the where clause for outdated provider block records.
     *
     * @param int $days Retention period in days.
     *
     * @return void
     */
    protected function provider_blocks_where(int $days): void
    {
        $this->db
            ->where('is_unavailable', TRUE)
            ->where('end_datetime <', $this->cutoff_date($days));
    }

    /**
     * Build the where clause for customer rows without appointments.
     *
     * @return void
     */
    protected function customers_where(): void
    {
        $this->db
            ->where("id_roles = (SELECT id FROM `" . $this->db->dbprefix('roles')
                . "` WHERE slug = '" . DB_SLUG_CUSTOMER . "')", NULL, FALSE)
            ->where("NOT EXISTS (SELECT ap.id FROM `" . $this->db->dbprefix('appointments')
                . "` ap WHERE ap.id_users_customer = `" . $this->db->dbprefix('users') . "`.id)", NULL, FALSE);
    }

    /**
     * Compute the cutoff date of the provided retention period in days.
     *
     * Appointments are considered expired when their end datetime falls before the
     * cutoff date.
     *
     * @param int $days Retention period in days.
     *
     * @return string Returns the cutoff datetime in Y-m-d H:i:s format.
     */
    public function cutoff_date(int $days): string
    {
        return (new DateTime('now'))
            ->modify('-' . max(0, $days) . ' days')
            ->format('Y-m-d 00:00:00');
    }
}
