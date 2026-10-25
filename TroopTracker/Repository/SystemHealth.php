<?php

namespace ObsidianSlicers\TroopTracker\Repository;

use XF\Mvc\Entity\Repository;

class SystemHealth extends Repository
{
    private const EXTENSIONS = [
        'mysqli', 'mbstring', 'gd', 'imagick', 'curl', 'openssl', 'xml', 'zip', 'intl', 'exif',
    ];

    private const INI_SETTINGS = [
        'upload_max_filesize', 'post_max_size', 'memory_limit',
        'max_execution_time', 'max_input_time', 'max_file_uploads',
    ];

    private const WRITABLE_PATHS = ['internal_data', 'data'];

    private const ADDON_PATHS = ['js', 'src/addons', 'styles'];

    private const PAYMENT_ERROR_DAYS = 30;

    public function getXenforoInfo(): array
    {
        $pendingUpgrades = [];

        foreach (\XF::app()->addOnManager()->getAllAddOns() as $addOn)
        {
            if ($addOn->isInstalled() && $addOn->canUpgrade())
            {
                $pendingUpgrades[] = $addOn->getAddOnId();
            }
        }

        $addOn = \XF::app()->addOnManager()->getById('ObsidianSlicers/TroopTracker');
        $installed = $addOn ? $addOn->getInstalledAddOn() : null;

        return [
            'version_id'       => \XF::$versionId,
            'version_string'   => \XF::$version,
            'addon_version_id' => $installed ? (int) $installed->version_id : null,
            'pending_upgrades' => $pendingUpgrades,
        ];
    }

    public function getPhpInfo(): array
    {
        $extensions = [];
        foreach (self::EXTENSIONS as $extension)
        {
            $extensions[$extension] = extension_loaded($extension);
        }

        $ini = [];
        foreach (self::INI_SETTINGS as $setting)
        {
            $ini[$setting] = (string) ini_get($setting);
        }

        return [
            'version'    => PHP_VERSION,
            'sapi'       => PHP_SAPI,
            'extensions' => $extensions,
            'ini'        => $ini,
        ];
    }

    public function getServerInfo(): array
    {
        $root = \XF::getRootDirectory();

        return [
            'software'       => $_SERVER['SERVER_SOFTWARE'] ?? null,
            'apache_modules' => function_exists('apache_get_modules') ? apache_get_modules() : null,
            'disk_free'      => @disk_free_space($root) ?: null,
            'disk_total'     => @disk_total_space($root) ?: null,
        ];
    }

    public function getPathPermissions(): array
    {
        $root = \XF::getRootDirectory();
        $result = [];

        foreach (array_merge(self::WRITABLE_PATHS, self::ADDON_PATHS) as $path)
        {
            $result[$path] = $this->describePath($root . '/' . $path);
        }

        foreach (self::WRITABLE_PATHS as $path)
        {
            foreach (glob($root . '/' . $path . '/*', GLOB_ONLYDIR) ?: [] as $child)
            {
                $result[$path . '/' . basename($child)] = $this->describePath($child);
            }
        }

        return $result;
    }

    public function getAttachmentInfo(): array
    {
        $options = \XF::options();

        return [
            'max_file_size_kb' => (int) ($options->attachmentMaxFileSize ?? 0),
            'max_dimension'    => (int) ($options->attachmentMaxDimensions['width'] ?? 0),
            'image_library'    => (string) ($options->imageLibrary ?? 'gd'),
        ];
    }

    public function getPaymentInfo(): array
    {
        $db = $this->db();
        $since = \XF::$time - (self::PAYMENT_ERROR_DAYS * 86400);

        $errors = $db->fetchPairs("
            SELECT log_message, COUNT(*)
            FROM xf_payment_provider_log
            WHERE log_type = 'error' AND log_date >= ?
            GROUP BY log_message
        ", [$since]);

        $providerDataErrors = (int) $db->fetchOne("
            SELECT COUNT(*)
            FROM xf_error_log
            WHERE message LIKE '%provider_data%' AND exception_date >= ?
        ", [$since]);

        return [
            'active_profiles'         => (int) $db->fetchOne(
                'SELECT COUNT(*) FROM xf_payment_profile WHERE active = 1'
            ),
            'error_days'              => self::PAYMENT_ERROR_DAYS,
            'errors'                  => array_map('intval', $errors),
            'provider_data_tables'    => $this->findTablesWithColumn('provider_data'),
            'provider_data_errors'    => $providerDataErrors,
        ];
    }

    private function findTablesWithColumn(string $column): array
    {
        return $this->db()->fetchAllColumn("
            SELECT TABLE_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = ? AND TABLE_NAME LIKE 'xf\\_%'
        ", [$column]);
    }

    private function describePath(string $path): array
    {
        if (!file_exists($path))
        {
            return ['exists' => false, 'writable' => false, 'perms' => null, 'world_writable' => false];
        }

        $perms = fileperms($path) & 0777;

        return [
            'exists'         => true,
            'writable'       => is_writable($path),
            'perms'          => sprintf('%04o', $perms),
            'world_writable' => ($perms & 0002) === 0002,
        ];
    }
}
