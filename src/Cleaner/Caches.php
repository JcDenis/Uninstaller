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
    TraitCleanerDir,
    ValueDescriptor
};

class Caches extends AbstractCleaner
{
    use TraitCleanerDir;

    protected function properties(): array
    {
        return [
            'id'   => 'caches',
            'name' => __('Cache'),
            'desc' => __('Folders from cache directory'),
        ];
    }

    protected function actions(): array
    {
        return [
            new ActionDescriptor([
                'id'      => 'delete',
                'select'  => __('delete selected cache directories'),
                'query'   => __('delete "%s" cache directory'),
                'success' => __('"%s" cache directory deleted'),
                'error'   => __('Failed to delete "%s" cache directory'),
            ]),
            new ActionDescriptor([
                'id'      => 'empty',
                'select'  => __('empty selected cache directories'),
                'query'   => __('empty "%s" cache directory'),
                'success' => __('"%s" cache directory emptied'),
                'error'   => __('Failed to empty "%s" cache directory'),
            ]),
        ];
    }

    public function distributed(): array
    {
        return ['cbfeed', 'cbtpl', 'dcrepo', 'versions'];
    }

    public function values(): array
    {
        $res = [];
        foreach (self::getDirs(DC_TPL_CACHE) as $dir) {
            $res[] = new ValueDescriptor(
                $dir['key'],
                '',
                (int) $dir['value']
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
