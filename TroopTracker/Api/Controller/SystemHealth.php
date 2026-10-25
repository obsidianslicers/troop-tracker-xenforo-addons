<?php

namespace ObsidianSlicers\TroopTracker\Api\Controller;

use XF\Api\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class SystemHealth extends AbstractController
{
    public function actionGet(ParameterBag $params)
    {
        $this->assertApiScope('system:read');

        /** @var \ObsidianSlicers\TroopTracker\Repository\SystemHealth $repo */
        $repo = $this->repository('ObsidianSlicers\TroopTracker:SystemHealth');

        return $this->apiResult([
            'xenforo'     => $repo->getXenforoInfo(),
            'php'         => $repo->getPhpInfo(),
            'server'      => $repo->getServerInfo(),
            'paths'       => $repo->getPathPermissions(),
            'attachments' => $repo->getAttachmentInfo(),
            'payments'    => $repo->getPaymentInfo(),
        ]);
    }
}
