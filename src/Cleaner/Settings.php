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
                'select'  => __('delete selected global settings namespaces'),
                'query'   => __('delete "%s" global settings namespace'),
                'success' => __('"%s" global settings namespace deleted'),
                'error'   => __('Failed to delete "%s" global settings namespace'),
            ]),
            new ActionDescriptor([
                'id'      => 'delete_local',
                'select'  => __('delete selected blog settings namespaces'),
                'query'   => __('delete "%s" blog settings namespace'),
                'success' => __('"%s" blog settings namespace deleted'),
                'error'   => __('Failed to delete "%s" blog settings namespace'),
            ]),
            new ActionDescriptor([
                'id'      => 'delete_all',
                'select'  => __('delete selected settings namespaces'),
                'query'   => __('delete "%s" settings namespace'),
                'success' => __('"%s" settings namespace deleted'),
                'error'   => __('Failed to delete "%s" settings namespace'),
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
        if ($res == null || $res->isEmpty()) {
            return [];
        }

        $rs = [];
        $i  = 0;
        while ($res->fetch()) {
            $sql = new SelectStatement();
            $sql->from(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME)
                ->fields([$sql->count('*')])
                ->where($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
                ->and('setting_ns = ' . $sql->quote($res->f('setting_ns')))
                ->group('setting_ns');

            $rs[$i]['key']   = $res->f('setting_ns');
            $rs[$i]['value'] = (int) $sql->select()?->f(0);
            $i++;
        }

        return $rs;
    }

    public function execute(string $action, string $ns): bool
    {
        $sql = new DeleteStatement();

        if ($action == 'delete_global' && self::checkNs($ns)) {
            $sql->from(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME)
                ->where('blog_id IS NULL')
                ->and('setting_ns = ' . $sql->quote((string) $ns))
                ->delete();

            return true;
        }
        if ($action == 'delete_local' && self::checkNs($ns)) {
            $sql->from(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME)
                ->where('blog_id = ' . $sql->quote((string) dcCore::app()->blog?->id))
                ->and('setting_ns = ' . $sql->quote((string) $ns))
                ->delete();

            return true;
        }
        if ($action == 'delete_all' && self::checkNs($ns)) {
            $sql->from(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME)
                ->where('setting_ns = ' . $sql->quote((string) $ns))
                ->and($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
                ->delete();

            return true;
        }
        if ($action == 'delete_related') {
            // check ns match ns:id;
            $reg_ws = substr(dcNamespace::NS_NAME_SCHEMA, 2, -2);
            $reg_id = substr(dcNamespace::NS_ID_SCHEMA, 2, -2);
            if (!preg_match_all('#((' . $reg_ws . '):(' . $reg_id . ');?)#', $ns, $matches)) {
                return false;
            }

            // build ws/id requests
            $or = [];
            foreach ($matches[2] as $key => $name) {
                $or[] = $sql->andGroup(['setting_ns = ' . $sql->quote((string) $name), 'setting_id = ' . $sql->quote((string) $matches[3][$key])]);
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

    /**
     * Check well formed ns.
     *
     * @param   string  The ns to check
     *
     * @return  bool    True on well formed
     */
    private static function checkNs(string $ns): bool
    {
        return preg_match(dcNamespace::NS_NAME_SCHEMA, $ns);
    }
}
