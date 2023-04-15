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
                'query'   => __('delete "%s" theme directory'),
                'success' => __('"%s" theme directory deleted'),
                'error'   => __('Failed to delete "%s" theme directory'),
            ]),
        ];
    }

    public function distributed(): array
    {
        return explode(',', DC_DISTRIB_THEMES);
    }

    public function values(): array
    {
        $res = self::getDirs(dcCore::app()->blog->themes_path);
        sort($res);

        return $res;
    }

    public function execute(string $action, string $ns): bool
    {
        if ($action == 'delete') {
            self::delDir(dcCore::app()->blog->themes_path, $ns, true);

            return true;
        }

        return false;
    }
}
