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
use dcNamespace;
use Dotclear\Plugin\Uninstaller\{
    AbstractCleaner,
    ActionDescriptor
};

/**
 * Settings cleaner.
 * 
 * Cleaner manages entire setting namespace 
 * except 'delete_related' which can pickup settings ns/id pairs
 */
class Settings extends AbstractCleaner
{
    protected function properties(): array
    {
        return [
            'id'   => 'settings',
            'name' => __('Settings'),
            'desc' => __('Namespaces registered in dcSettings'),
        ];
    }

    protected function actions(): array
    {
        return [
            new ActionDescriptor([
                'id'      => 'delete_global',
                'query'   => __('delete "%s" global settings'),
                'success' => __('"%s" global settings deleted'),
                'error'   => __('Failed to delete "%s" global settings'),
            ]),
            new ActionDescriptor([
                'id'      => 'delete_local',
                'query'   => __('delete "%s" blog settings'),
                'success' => __('"%s" blog settings deleted'),
                'error'   => __('Failed to delete "%s" blog settings'),
            ]),
            new ActionDescriptor([
                'id'      => 'delete_all',
                'query'   => __('delete "%s" settings'),
                'success' => __('"%s" settings deleted'),
                'error'   => __('Failed to delete "%s" settings'),
            ]),
            // $ns = 'setting_ns:setting_id;setting_ns:setting_id;...' for global and blogs settings
            new ActionDescriptor([
                'id'      => 'delete_related',
                'query'   => __('delete related settings'),
                'success' => __('related settings deleted'),
                'error'   => __('Failed to delete related settings'),
            ]),
        ];
    }

    public function distributed(): array
    {
        return [
            'akismet',
            'antispam',
            'breadcrumb',
            'dcckeditor',
            'dclegacyeditor',
            'maintenance',
            'pages',
            'pings',
            'system',
            'themes',
            'widgets',
        ];
    }

    public function values(): array
    {
        $res = dcCore::app()->con->select(
            'SELECT setting_ns ' .
            'FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
            'WHERE blog_id IS NULL ' .
            'OR blog_id IS NOT NULL ' .
            'GROUP BY setting_ns'
        );

        $rs = [];
        $i  = 0;
        while ($res->fetch()) {
            $rs[$i]['key']   = $res->f('setting_ns');
            $rs[$i]['value'] = dcCore::app()->con->select(
                'SELECT count(*) FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
                "WHERE setting_ns = '" . $res->f('setting_ns') . "' " .
                'AND (blog_id IS NULL OR blog_id IS NOT NULL) ' .
                'GROUP BY setting_ns '
            )->f(0);
            $i++;
        }

        return $rs;
    }

    public function execute(string $action, string $ns): bool
    {
        if ($action == 'delete_global') {
            dcCore::app()->con->execute(
                'DELETE FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
                'WHERE blog_id IS NULL ' .
                "AND setting_ns = '" . dcCore::app()->con->escapeStr((string) $ns) . "' "
            );

            return true;
        }
        if ($action == 'delete_local') {
            dcCore::app()->con->execute(
                'DELETE FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
                "WHERE blog_id = '" . dcCore::app()->con->escapeStr((string) dcCore::app()->blog?->id) . "' " .
                "AND setting_ns = '" . dcCore::app()->con->escapeStr((string) $ns) . "' "
            );

            return true;
        }
        if ($action == 'delete_all') {
            dcCore::app()->con->execute(
                'DELETE FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
                "WHERE setting_ns = '" . dcCore::app()->con->escapeStr((string) $ns) . "' " .
                "AND (blog_id IS NULL OR blog_id != '') "
            );

            return true;
        }
        if ($action == 'delete_related') {
            $or = [];
            foreach (explode(';', $ns) as $pair) {
                $exp = explode(':', $pair);
                if (count($exp) == 2) {
                    $or[] = "setting_ns = '" . dcCore::app()->con->escapeStr((string) $exp[0]) . "' AND setting_id = '" . dcCore::app()->con->escapeStr((string) $exp[1]) . "'";
                }
            }
            if (empty($or)) {
                return false;
            }
            dcCore::app()->con->execute(
                'DELETE FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
                "WHERE (" . implode(' OR ', $or) . ") " .
                "AND (blog_id IS NULL OR blog_id != '') "
            );

            return true;
        }

        return false;
    }
}
