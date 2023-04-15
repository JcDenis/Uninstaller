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
use Dotclear\Database\Statement\{
    DeleteStatement,
    SelectStatement
};
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
        $sql = new SelectStatement();
        $sql->from(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME)
            ->columns(['setting_ns'])
            ->where($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
            ->group('setting_ns');

        $res = $sql->select();
        $rs  = [];
        $i   = 0;
        while ($res->fetch()) {
            $sql = new SelectStatement();
            $sql->from(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME)
                ->fields([$sql->count('*')])
                ->where($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
                ->and('setting_ns = ' . $sql->quote($res->f('setting_ns')))
                ->group('setting_ns');

            $rs[$i]['key']   = $res->f('setting_ns');
            $rs[$i]['value'] = $sql->select()->f(0);
            $i++;
        }

        return $rs;
    }

    public function execute(string $action, string $ns): bool
    {
        $sql = new DeleteStatement();

        if ($action == 'delete_global') {
            $sql->from(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME)
                ->where('blog_id IS NULL')
                ->and('setting_ns = ' . $sql->quote((string) $ns))
                ->delete();

            return true;
        }
        if ($action == 'delete_local') {
            $sql->from(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME)
                ->where('blog_id = ' . $sql->quote((string) dcCore::app()->blog?->id))
                ->and('setting_ns = ' . $sql->quote((string) $ns))
                ->delete();

            return true;
        }
        if ($action == 'delete_all') {
            $sql->from(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME)
                ->where('setting_ns = ' . $sql->quote((string) $ns))
                ->and($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
                ->delete();

            return true;
        }
        if ($action == 'delete_related') {
            $or = [];
            foreach (explode(';', $ns) as $pair) {
                $exp = explode(':', $pair);
                if (count($exp) == 2) {
                    $or[] = $sql->andGroup(['setting_ns = ' . $sq->quote((string) $exp[0]), 'setting_id = ' . $sql->quote((string) $exp[1])]);
                }
            }
            if (empty($or)) {
                return false;
            }

            $sql->from(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME)
                ->where($sql->orGroup($or))
                ->and($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
                ->delete();

            return true;
        }

        return false;
    }
}
