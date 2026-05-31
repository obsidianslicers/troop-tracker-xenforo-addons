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

        if (!$userId)
        {
            return $this->apiError('A valid user_id is required.', 'invalid_user_id', [], 400);
        }

        /** @var \ObsidianSlicers\TroopTracker\Repository\UserGroups $repo */
        $repo = $this->repository('ObsidianSlicers\TroopTracker:UserGroups');

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
