<?php
/**
 * @brief Uninstaller, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis and Contributors
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\Uninstaller\Cleaner;

use dcCore;
use Dotclear\Plugin\Uninstaller\{
    AbstractCleaner,
    ActionDescriptor,
    TraitCleanerDir
};

class Themes extends AbstractCleaner
{
    use TraitCleanerDir;

    protected function properties(): array
    {
        return [
            'id'   => 'themes',
            'name' => __('Themes'),
            'desc' => __('Folders from blog themes directory'),
        ];
    }

    protected function actions(): array
    {
        return [
            new ActionDescriptor([
                'id'      => 'delete',
                'select'  => __('delete selected themes files and directories'),
                'query'   => __('delete "%s" theme files and directories'),
                'success' => __('"%s" theme files and directories deleted'),
                'error'   => __('Failed to delete "%s" theme files and directories'),
            ]),
        ];
    }

    public function distributed(): array
    {
        return explode(',', DC_DISTRIB_THEMES);
    }

    public function values(): array
    {
        if (($path = dcCore::app()->blog?->themes_path) === null) {
            return [];
        }

        $res = self::getDirs($path);
        sort($res);

        return $res;
    }

    public function execute(string $action, string $ns): bool
    {
        if ($action != 'delete' || ($path = dcCore::app()->blog?->themes_path) === null) {
            return false;
        }

        self::delDir($path, $ns, true);

        return true;
    }
}
