<?php

namespace ObsidianSlicers\TroopTracker\Repository;

use XF\Mvc\Entity\Repository;

class UpgradeStats extends Repository
{
    public function getActiveUpgrades(): array
    {
        return $this->db()->fetchAll("SELECT * FROM xf_user_upgrade_active");
    }

    public function getExpiredUpgrades(): array
    {
        return $this->db()->fetchAll("SELECT * FROM xf_user_upgrade_expired");
    }

    public function getUpgradeDefinitions(): array
    {
        return $this->db()->fetchAll("SELECT * FROM xf_user_upgrade");
    }

    public function getCurrentMonthResults(): array
    {
        $startOfMonth = strtotime(date('Y-m-01 00:00:00'));
        $endOfMonth   = strtotime(date('Y-m-t 23:59:59'));

        $active = $this->db()->fetchAll("
            SELECT 'active' AS status, user_upgrade_record_id, user_id,
                   user_upgrade_id, start_date, end_date
            FROM xf_user_upgrade_active
            WHERE start_date BETWEEN ? AND ?
        ", [$startOfMonth, $endOfMonth]);

        $expired = $this->db()->fetchAll("
            SELECT 'expired' AS status, user_upgrade_record_id, user_id,
                   user_upgrade_id, start_date, end_date
            FROM xf_user_upgrade_expired
            WHERE start_date BETWEEN ? AND ?
        ", [$startOfMonth, $endOfMonth]);

        return array_merge($active, $expired);
    }

    public function getPaymentLog(): array
    {
        return $this->db()->fetchAll("
            SELECT * FROM xf_payment_provider_log
            WHERE log_message = 'Payment received, upgraded/extended.'
            ORDER BY provider_log_id DESC
        ");
    }

    public function getUserDonationStats(int $userId): array
    {
        $db = $this->db();

        $monthsCount = $db->fetchOne("
            SELECT COUNT(DISTINCT DATE_FORMAT(FROM_UNIXTIME(start_date), '%Y-%m'))
            FROM (
                SELECT start_date FROM xf_user_upgrade_active WHERE user_id = ?
                UNION ALL
                SELECT start_date FROM xf_user_upgrade_expired WHERE user_id = ?
            ) combined
        ", [$userId, $userId]);

        $totalDonated = $db->fetchOne("
            SELECT COALESCE(SUM(pr.cost_amount), 0)
            FROM xf_payment_provider_log ppl
            JOIN xf_purchase_request pr ON ppl.purchase_request_key = pr.request_key
            WHERE pr.user_id = ?
              AND ppl.log_message = 'Payment received, upgraded/extended.'
        ", [$userId]);

        return [
            'months_donated' => (int) $monthsCount,
            'total_donated'  => round((float) $totalDonated, 2),
        ];
    }

    public function getUserDonations(int $userId): array
    {
        $db = $this->db();

        $active = $db->fetchAll("
            SELECT 'active' AS status,
                   a.user_upgrade_record_id, a.user_id,
                   a.user_upgrade_id, a.start_date, a.end_date,
                   u.title, u.cost_amount, u.cost_currency
            FROM xf_user_upgrade_active a
            JOIN xf_user_upgrade u ON a.user_upgrade_id = u.user_upgrade_id
            WHERE a.user_id = ?
            ORDER BY a.start_date DESC
        ", [$userId]);

        $expired = $db->fetchAll("
            SELECT 'expired' AS status,
                   e.user_upgrade_record_id, e.user_id,
                   e.user_upgrade_id, e.start_date, e.end_date,
                   u.title, u.cost_amount, u.cost_currency
            FROM xf_user_upgrade_expired e
            JOIN xf_user_upgrade u ON e.user_upgrade_id = u.user_upgrade_id
            WHERE e.user_id = ?
            ORDER BY e.start_date DESC
        ", [$userId]);

        usort($active, fn($a, $b) => $b['start_date'] <=> $a['start_date']);
        usort($expired, fn($a, $b) => $b['start_date'] <=> $a['start_date']);

        return array_merge($active, $expired);
    }
}
