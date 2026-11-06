<?php

namespace ObsidianSlicers\TroopTracker;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    /**
     * Class extensions dropped in 1.2.1. Upgrading clears any leftover
     * registrations so the old files are no longer loaded.
     */
    private const RETIRED_EXTENSIONS = [
        'ObsidianSlicers\TroopTracker\XF\Entity\User',
        'ObsidianSlicers\TroopTracker\XF\Pub\Controller\Attachment',
    ];

    public function upgrade1020170Step1(): void
    {
        $placeholders = implode(', ', array_fill(0, count(self::RETIRED_EXTENSIONS), '?'));
        $this->db()->delete(
            'xf_class_extension',
            "to_class IN ($placeholders)",
            self::RETIRED_EXTENSIONS
        );

        \XF::repository('XF:ClassExtension')->rebuildExtensionCache();
    }
}
