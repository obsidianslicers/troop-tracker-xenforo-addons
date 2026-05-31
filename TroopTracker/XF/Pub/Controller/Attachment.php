<?php

namespace ObsidianSlicers\TroopTracker\XF\Pub\Controller;

use XF\Mvc\ParameterBag;

class Attachment extends XFCP_Attachment
{
    public function actionIndex(ParameterBag $params)
    {
        $attachment = $this->em()->find('XF:Attachment', $params->attachment_id);
        if (!$attachment)
        {
            throw $this->exception($this->notFound());
        }

        if ($attachment->temp_hash)
        {
            $hash = $this->filter('hash', 'str');
            if ($attachment->temp_hash !== $hash)
            {
                return $this->noPermission();
            }
        }
        else
        {
            $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $extension = strtolower(pathinfo($attachment->filename, PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedImageExtensions))
            {
                if (!$attachment->canView($error))
                {
                    return $this->noPermission($error);
                }
            }
        }

        if (!$this->filter('no_canonical', 'bool'))
        {
            $this->assertCanonicalUrl($this->buildLink('attachments', $attachment));
        }

        $attachPlugin = $this->plugin('XF:Attachment');
        return $attachPlugin->displayAttachment($attachment);
    }
}
