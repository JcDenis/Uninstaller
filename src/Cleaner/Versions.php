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
    ActionDescriptor
};

class Versions extends AbstractCleaner
{
    protected function properties(): array
    {
        return [
            'id'   => 'versions',
            'name' => __('Versions'),
            'desc' => __('Versions registered in table "version" of Dotclear'),
        ];
    }

    protected function actions(): array
    {
        return [
            new ActionDescriptor([
                'id'      => 'delete',
                'query'   => __('delete "%s" version number'),
                'success' => __('"%s" version number deleted'),
                'error'   => __('Failed to delete "%s" version number'),
            ]),
        ];
    }

    public function distributed(): array
    {
        return [
            'antispam',
            'blogroll',
            'blowupConfig',
            'core',
            'dcCKEditor',
            'dcLegacyEditor',
            'pages',
            'pings',
            'simpleMenu',
            'tags',
            'widgets',
        ];
    }

    public function values(): array
    {
        $res = dcCore::app()->con->select('SELECT * FROM ' . dcCore::app()->prefix . dcCore::VERSION_TABLE_NAME);

        $rs = [];
        $i  = 0;
        while ($res->fetch()) {
            $rs[$i]['key']   = $res->f('module');
            $rs[$i]['value'] = $res->f('version');
            $i++;
        }

        return $rs;
    }

    public function execute(string $action, string $ns): bool
    {
        if ($action == 'delete') {
            dcCore::app()->con->execute(
                'DELETE FROM  ' . dcCore::app()->prefix . dcCore::VERSION_TABLE_NAME . ' ' .
                "WHERE module = '" . dcCore::app()->con->escapeStr((string) $ns) . "' "
            );

            return true;
        }

        return false;
    }
}
