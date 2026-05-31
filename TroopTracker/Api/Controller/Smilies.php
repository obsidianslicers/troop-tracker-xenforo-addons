<?php

namespace ObsidianSlicers\TroopTracker\Api\Controller;

use XF\Api\Controller\AbstractController;
use XF\Mvc\ParameterBag;
use XF\Repository\SmilieRepository;
use XF\Util\Arr;

class Smilies extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertApiScopeByRequestMethod('smilie');
    }

    /**
     * @api-desc Returns all smilies and their categories.
     *
     * @api-out array $categories List of smilie categories
     * @api-out array $smilies List of all smilies
     * @api-out string $base_url Forum base URL for resolving relative image paths
     * @api-out int $total Total number of smilies
     */
    public function actionGet()
    {
        $smilieRepo = $this->repository(SmilieRepository::class);
        $listData   = $smilieRepo->getSmilieListData();

        $categories = [];
        foreach ($listData['smilieCategories'] AS $category)
        {
            $categories[] = [
                'smilie_category_id' => $category->smilie_category_id,
                'title'              => (string) $category->title,
                'display_order'      => $category->display_order,
            ];
        }

        $smilies = [];
        foreach ($listData['smilies'] AS $groupedSmilies)
        {
            foreach ($groupedSmilies AS $smilie)
            {
                $smilies[] = [
                    'smilie_id'          => $smilie->smilie_id,
                    'title'              => $smilie->title,
                    'smilie_text'        => Arr::stringToArray($smilie->smilie_text, '/\r?\n/'),
                    'smilie_category_id' => $smilie->smilie_category_id,
                    'display_order'      => $smilie->display_order,
                    'display_in_editor'  => (bool) $smilie->display_in_editor,
                    'emoji_shortname'    => $smilie->emoji_shortname,
                    'image_url'          => $smilie->image_url,
                    'image_url_2x'       => $smilie->image_url_2x,
                    'sprite_mode'        => (bool) $smilie->sprite_mode,
                    'sprite_params'      => $smilie->sprite_mode ? $smilie->sprite_params : null,
                ];
            }
        }

        return $this->apiResult([
            'categories' => $categories,
            'smilies'    => $smilies,
            'base_url'   => \XF::app()->options()->boardUrl . '/',
            'total'      => $listData['totalSmilies'],
        ]);
    }
}
