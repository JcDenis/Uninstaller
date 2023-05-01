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
 * Cleaner for Dotclear VAR directory used by modules.
 *
 * It allows modules to delete an entire sub folder
 * of DC_VAR directory path.
 */
class Vars extends CleanerParent
{
    use TraitCleanerDir;

    public function __construct()
    {
        parent::__construct(new CleanerDescriptor(
            id:   'vars',
            name: __('Var'),
            desc: __('Folders from Dotclear VAR directory'),
            actions: [
                // delete a $ns folder and their files
                new ActionDescriptor(
                    id:      'delete',
                    select:  __('delete selected var directories'),
                    query:   __('delete "%s" var directory'),
                    success: __('"%s" var directory deleted'),
                    error:   __('Failed to delete "%s" var directory')
                ),
            ]
        ));
    }

    public function distributed(): array
    {
        return [];
    }

    public function values(): array
    {
        $res = [];
        foreach (self::getDirs(DC_VAR) as $dir) {
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
            self::delDir(DC_VAR, $ns, true);

            return true;
        }

        return false;
    }
}
