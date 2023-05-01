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
    ActionDescriptor,
    CleanerDescriptor,
    CleanerParent,
    ValueDescriptor,
    TraitCleanerDir
};

/**
 * Cleaner for Dotclear plugins.
 *
 * It allows modules to delete their own folder.
 */
class Plugins extends CleanerParent
{
    use TraitCleanerDir;

    public function __construct()
    {
        parent::__construct(new CleanerDescriptor(
            id:   'plugins',
            name: __('Plugins'),
            desc: __('Folders from plugins directories'),
            actions: [
                // delete $ns plugin folder
                new ActionDescriptor(
                    id:      'delete',
                    select:  __('delete selected plugins files and directories'),
                    query:   __('delete "%s" plugin files and directories'),
                    success: __('"%s" plugin files and directories deleted'),
                    error:   __('Failed to delete "%s" plugin files and directories')
                ),
            ]
        ));
    }

    public function distributed(): array
    {
        return explode(',', DC_DISTRIB_PLUGINS);
    }

    public function values(): array
    {
        $dirs = self::getDirs(explode(PATH_SEPARATOR, DC_PLUGINS_ROOT));
        sort($dirs);

        $res = [];
        foreach ($dirs as $dir) {
            $res[] = new ValueDescriptor(
                ns:    $dir['key'],
                count: (int) $dir['value']
            );
        }

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
