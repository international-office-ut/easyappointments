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

namespace App\Models;

use Exception;

use CodeIgniter\Database\BaseConnection;

/**
 * Maintenance Model
 *
 * Data retention purges: appointments, provider blocks (rows with `is_unavailable` flag),
 * and customers without appointments that are older than the configured retention period
 * in days. All purges run as direct SQL deletes (no notifications, no side effects). The
 * where clauses are applied directly on the delete statements: collecting thousands of
 * record ids first would blow up the query builder (regex length limits).
 *
 * @property BaseConnection $db
 */
class Maintenance_Model extends Ea_Model {
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
        if ( ! $this->db->tableExists('appointments'))
        {
            return 0;
        }

        $builder = $this->appointments_where($days);

        if ($dry_run)
        {
            return $builder->countAllResults();
        }

        $builder->delete();

        return $this->db->affectedRows();
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
        if ( ! $this->db->tableExists('appointments'))
        {
            return 0;
        }

        $builder = $this->provider_blocks_where($days);

        if ($dry_run)
        {
            return $builder->countAllResults();
        }

        $builder->delete();

        return $this->db->affectedRows();
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
        if ( ! $this->db->tableExists('users'))
        {
            return 0;
        }

        $builder = $this->customers_where();

        if ($dry_run)
        {
            return $builder->countAllResults();
        }

        $builder->delete();

        return $this->db->affectedRows();
    }

    /**
     * Build the where-clause builder for outdated appointment records.
     *
     * @param int $days Retention period in days.
     *
     * @return \CodeIgniter\Database\Query\Builder
     */
    protected function appointments_where(int $days)
    {
        return $this->db->table('appointments')
            ->where('end_datetime <', $this->cutoff_date($days));
    }

    /**
     * Build the where-clause builder for outdated provider block records.
     *
     * @param int $days Retention period in days.
     *
     * @return \CodeIgniter\Database\Query\Builder
     */
    protected function provider_blocks_where(int $days)
    {
        return $this->db->table('appointments')
            ->where('is_unavailable', TRUE)
            ->where('end_datetime <', $this->cutoff_date($days));
    }

    /**
     * Build the where-clause builder for customer rows without appointments.
     *
     * @return \CodeIgniter\Database\Query\Builder
     */
    protected function customers_where()
    {
        return $this->db->table('users')
            ->where("id_roles = (SELECT id FROM `" . $this->db->prefixTable('roles')
                . "` WHERE slug = '" . DB_SLUG_CUSTOMER . "')", NULL, FALSE)
            ->where("NOT EXISTS (SELECT ap.id FROM `" . $this->db->prefixTable('appointments')
                . "` ap WHERE ap.id_users_customer = `" . $this->db->prefixTable('users') . "`.id)", NULL, FALSE);
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
        return (new \DateTime('now'))
            ->modify('-' . max(0, $days) . ' days')
            ->format('Y-m-d 00:00:00');
    }
}
