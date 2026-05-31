<?php

namespace ObsidianSlicers\TroopTracker\Repository;

use XF\Mvc\Entity\Repository;

class UserGroups extends Repository
{
    public function getUserGroups(int $userId): array
    {
        $user = $this->db()->fetchRow("
            SELECT user_group_id, secondary_group_ids
            FROM xf_user
            WHERE user_id = ?
        ", [$userId]);

        if (!$user)
        {
            return [];
        }

        $groupIds = [$user['user_group_id']];

        if (!empty($user['secondary_group_ids']))
        {
            $secondaryIds = array_map('intval', explode(',', $user['secondary_group_ids']));
            $groupIds     = array_merge($groupIds, $secondaryIds);
        }

        $groupIds = array_unique($groupIds);

        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        $groups = $this->db()->fetchAll("
            SELECT user_group_id, title, banner_text, display_style_priority
            FROM xf_user_group
            WHERE user_group_id IN ($placeholders)
            ORDER BY display_style_priority DESC
        ", $groupIds);

        $result = [];
        foreach ($groups as $group)
        {
            $result[] = [
                'groupID'    => $group['user_group_id'],
                'title'      => $group['title'],
                'bannerText' => $group['banner_text'],
                'order'      => $group['display_style_priority'],
                'isPrimary'  => $group['user_group_id'] === $user['user_group_id'],
            ];
        }

        return $result;
    }
}
