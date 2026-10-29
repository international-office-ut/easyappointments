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
 * Data retention purges: appointments, customers without appointments and provider blocks
 * (unavailable periods) that are older than the configured retention period. The removal
 * happens directly in the database without triggering notifications or other side effects.
 *
 * @property CI_DB_query_builder $db
 */
class Maintenance_model extends EA_Model {
    /**
     * Purge appointments that end before the cutoff date.
     *
     * @param int $months Retention period in months.
     * @param bool $dry_run When TRUE the results are only reported and nothing is removed.
     *
     * @return int Returns the number of affected records.
     */
    public function purge_appointments(int $months, bool $dry_run = FALSE): int
    {
        if ( ! $this->db->table_exists('appointments'))
        {
            return 0;
        }

        $rows = $this->db
            ->select('id')
            ->from('appointments')
            ->where('end_datetime <', $this->cutoff_date($months))
            ->get()
            ->result_array();

        if ($dry_run)
        {
            return count($rows);
        }

        $this->remove_rows('appointments', $rows);

        return count($rows);
    }

    /**
     * Purge provider blocks (unavailable periods) that end before the cutoff date.
     *
     * @param int $months Retention period in months.
     * @param bool $dry_run When TRUE the results are only reported and nothing is removed.
     *
     * @return int Returns the number of affected records.
     */
    public function purge_unavailables(int $months, bool $dry_run = FALSE): int
    {
        // Some installations may not have the unavailables table.
        if ( ! $this->db->table_exists('unavailables'))
        {
            return 0;
        }

        $rows = $this->db
            ->select('id')
            ->from('unavailables')
            ->where('end_datetime <', $this->cutoff_date($months))
            ->get()
            ->result_array();

        if ($dry_run)
        {
            return count($rows);
        }

        $this->remove_rows('unavailables', $rows);

        return count($rows);
    }

    /**
     * Purge customer users that have no appointments anymore.
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

        $rows = $this->db
            ->select('users.id')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('roles.slug', DB_SLUG_CUSTOMER)
            ->where('NOT EXISTS (SELECT ap.id FROM ' . $this->db->dbprefix('appointments')
                . ' ap WHERE ap.id_users_customer = ' . $this->db->dbprefix('users') . '.id)', NULL, FALSE)
            ->get()
            ->result_array();

        if ($dry_run)
        {
            return count($rows);
        }

        $this->remove_rows('users', $rows);

        return count($rows);
    }

    /**
     * Remove the records of the provided table by their ids.
     *
     * @param string $table Target table name.
     * @param array $rows Result rows containing the id column.
     *
     * @return void
     */
    protected function remove_rows(string $table, array $rows): void
    {
        $ids = array_column($rows, 'id');

        if (empty($ids))
        {
            return;
        }

        $this->db
            ->where_in('id', $ids)
            ->delete($table);
    }

    /**
     * Compute the cutoff date of the provided retention period in months.
     *
     * Appointments are considered expired when their end datetime falls before the
     * cutoff date.
     *
     * @param int $months Retention period in months.
     *
     * @return string Returns the cutoff datetime in Y-m-d H:i:s format.
     */
    public function cutoff_date(int $months): string
    {
        return (new DateTime('now'))
            ->modify('-' . max(0, $months) . ' months')
            ->format('Y-m-d 00:00:00');
    }
}
