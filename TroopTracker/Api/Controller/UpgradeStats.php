<?php

namespace ObsidianSlicers\TroopTracker\Api\Controller;

use XF\Api\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class UpgradeStats extends AbstractController
{
    public function actionGet(ParameterBag $params)
    {
        $this->assertApiScope('upgrades:read');

        /** @var \ObsidianSlicers\TroopTracker\Repository\UpgradeStats $repo */
        $repo = $this->repository('ObsidianSlicers\TroopTracker:UpgradeStats');

        $active   = $repo->getActiveUpgrades();
        $expired  = $repo->getExpiredUpgrades();
        $upgrades = $repo->getUpgradeDefinitions();
        $monthly  = $repo->getCurrentMonthResults();
        $payments = $repo->getPaymentLog();

        return $this->apiResult([
            'userUpgradeActive'  => $active,
            'userUpgradeExpired' => $expired,
            'userUpgrades'       => $upgrades,
            'combinedResults'    => $monthly,
            'paymentLog'         => $payments,
        ]);
    }

    public function actionGetuser(ParameterBag $params)
    {
        $this->assertApiScope('upgrades:read');

        $userId = $this->filter('user_id', 'uint');
        if (!$userId) {
            return $this->apiError(\XF::phrase('upgradestats_user_id_required'), 'user_id_required', [], 400);
        }

        /** @var \ObsidianSlicers\TroopTracker\Repository\UpgradeStats $repo */
        $repo = $this->repository('ObsidianSlicers\TroopTracker:UpgradeStats');

        $stats   = $repo->getUserDonationStats($userId);
        $records = $repo->getUserDonations($userId);

        return $this->apiResult([
            'user_id'        => $userId,
            'months_donated' => $stats['months_donated'],
            'total_donated'  => $stats['total_donated'],
            'donations'      => $records,
        ]);
    }
}
