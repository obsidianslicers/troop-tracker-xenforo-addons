<?php

namespace ObsidianSlicers\TroopTracker\Api\Controller;

use XF\Api\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class UserGroups extends AbstractController
{
    public function actionGet(ParameterBag $params)
    {
        $this->assertApiScope('usergroups:read');

        $userId = $this->filter('user_id', 'uint');

        /** @var \ObsidianSlicers\TroopTracker\Repository\UserGroups $repo */
        $repo = $this->repository('ObsidianSlicers\TroopTracker:UserGroups');

        if (!$userId)
        {
            return $this->apiResult([
                'userGroups' => $repo->getAllUserGroups(),
            ]);
        }

        $groups = $repo->getUserGroups($userId);

        if (empty($groups))
        {
            return $this->apiError('User not found.', 'user_not_found', [], 404);
        }

        return $this->apiResult([
            'userId'     => $userId,
            'userGroups' => $groups,
        ]);
    }
}
