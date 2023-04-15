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

class Vars extends AbstractCleaner
{
    use TraitCleanerDir;

    protected function properties(): array
    {
        return [
            'id'   => 'vars',
            'name' => __('Var'),
            'desc' => __('Folders from Dotclear VAR directory'),
        ];
    }

    protected function actions(): array
    {
        return [
            new ActionDescriptor([
                'id'      => 'delete',
                'select'  => __('delete selected var directories'),
                'query'   => __('delete "%s" var directory'),
                'success' => __('"%s" var directory deleted'),
                'error'   => __('Failed to delete "%s" var directory'),
            ]),
        ];
    }

    public function distributed(): array
    {
        return [];
    }

    public function values(): array
    {
        return self::getDirs(DC_VAR);
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
