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
    CleanerDescriptor,
    TraitCleanerDir,
    ValueDescriptor
};

/**
 * Cleaner for Dotclear cache directory used by modules.
 *
 * It allows modules to delete an entire sub folder
 * of DC_TPL_CACHE directory path.
 */
class Caches extends AbstractCleaner
{
    use TraitCleanerDir;

    public function __construct()
    {
        parent::__construct(new CleanerDescriptor(
            id:   'caches',
            name: __('Cache'),
            desc: __('Folders from cache directory'),
            actions: [
                // delete a $ns folder and thier files.
                new ActionDescriptor(
                    id:      'delete',
                    select:  __('delete selected cache directories'),
                    query:   __('delete "%s" cache directory'),
                    success: __('"%s" cache directory deleted'),
                    error:   __('Failed to delete "%s" cache directory')
                ),
                // delete $ns folder files but keep folder
                new ActionDescriptor(
                    id:      'empty',
                    select:  __('empty selected cache directories'),
                    query:   __('empty "%s" cache directory'),
                    success: __('"%s" cache directory emptied'),
                    error:   __('Failed to empty "%s" cache directory')
                ),
            ]
        ));
    }

    public function distributed(): array
    {
        return [
            'cbfeed',
            'cbtpl',
            'dcrepo',
            'versions',
        ];
    }

    public function values(): array
    {
        $res = [];
        foreach (self::getDirs(DC_TPL_CACHE) as $dir) {
            $res[] = new ValueDescriptor(
                ns:    $dir['key'],
                count: (int) $dir['value']
            );
        }

        return $res;
    }

    public function execute(string $action, string $ns): bool
    {
        if ($action == 'empty') {
            self::delDir(DC_TPL_CACHE, $ns, false);

            return true;
        }
        if ($action == 'delete') {
            self::delDir(DC_TPL_CACHE, $ns, true);

            return true;
        }

        return false;
    }
}
