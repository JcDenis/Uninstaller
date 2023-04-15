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

use Dotclear\Plugin\Uninstaller\{
    AbstractCleaner,
    ActionDescriptor,
    TraitCleanerDir
};

class Plugins extends AbstractCleaner
{
    use TraitCleanerDir;

    protected function properties(): array
    {
        return [
            'id'   => 'plugins',
            'name' => __('Plugins'),
            'desc' => __('Folders from plugins directories'),
        ];
    }

    protected function actions(): array
    {
        return [
            new ActionDescriptor([
                'id'      => 'delete',
                'query'   => __('delete "%s" plugin directory'),
                'success' => __('"%s" plugin directory deleted'),
                'error'   => __('Failed to delete "%s" plugin directory'),
            ]),
        ];
    }

    public function distributed(): array
    {
        return explode(',', DC_DISTRIB_PLUGINS);
    }

    public function values(): array
    {
        $res = self::getDirs(explode(PATH_SEPARATOR, DC_PLUGINS_ROOT));
        sort($res);

        return $res;
    }

    public function execute(string $action, string $ns): bool
    {
        if ($action == 'delete') {
            $res = explode(PATH_SEPARATOR, DC_PLUGINS_ROOT);
            self::delDir($res, $ns, true);

            return true;
        }

        return false;
    }
}
