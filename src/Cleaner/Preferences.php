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
use dcWorkspace;
use Dotclear\Database\Statement\{
    DeleteStatement,
    SelectStatement
};
use Dotclear\Plugin\Uninstaller\{
    AbstractCleaner,
    ActionDescriptor
};

/**
 * Users preferences cleaner.
 *
 * Cleaner manages entire users preferences workspace
 * except 'delete_related' which can pickup preference ws/id pairs
 */
class Preferences extends AbstractCleaner
{
    protected function properties(): array
    {
        return [
            'id'   => 'preferences',
            'name' => __('Preferences'),
            'desc' => __('Users preferences workspaces'),
        ];
    }

    protected function actions(): array
    {
        return [
            new ActionDescriptor([
                'id'      => 'delete_global',
                'select'  => __('delete selected global preferences workspaces'),
                'query'   => __('delete "%s" global preferences workspace'),
                'success' => __('"%s" global preferences workspace deleted'),
                'error'   => __('Failed to delete "%s" global preferences workspace'),
            ]),
            new ActionDescriptor([
                'id'      => 'delete_local',
                'select'  => __('delete selected users preferences workspaces'),
                'query'   => __('delete "%s" users preferences workspace'),
                'success' => __('"%s" users preferences workspace deleted'),
                'error'   => __('Failed to delete "%s" users preferences workspace'),
            ]),
            new ActionDescriptor([
                'id'      => 'delete_all',
                'select'  => __('delete selected preferences workspaces'),
                'query'   => __('delete "%s" preferences workspace'),
                'success' => __('"%s" preferences workspace deleted'),
                'error'   => __('Failed to delete "%s" preferences workspace'),
            ]),
            // $ns = 'pref_ws:pref_id;pref_ws:pref_id;...' for global and users preferences
            new ActionDescriptor([
                'id'      => 'delete_related',
                'query'   => __('delete related preferences'),
                'success' => __('related preferences deleted'),
                'error'   => __('Failed to delete related preferences'),
            ]),
        ];
    }

    public function distributed(): array
    {
        return [
            'accessibility',
            'interface',
            'maintenance',
            'profile',
            'dashboard',
            'favorites',
            'toggles',
        ];
    }

    public function values(): array
    {
        $sql = new SelectStatement();
        $sql->from(dcCore::app()->prefix . dcWorkspace::WS_TABLE_NAME)
            ->columns(['pref_ws'])
            ->where($sql->orGroup(['user_id IS NULL', 'user_id IS NOT NULL']))
            ->group('pref_ws');

        $res = $sql->select();
        if ($res == null || $res->isEmpty()) {
            return [];
        }

        $rs = [];
        $i  = 0;
        while ($res->fetch()) {
            $sql = new SelectStatement();
            $sql->from(dcCore::app()->prefix . dcWorkspace::WS_TABLE_NAME)
                ->fields([$sql->count('*')])
                ->where($sql->orGroup(['user_id IS NULL', 'user_id IS NOT NULL']))
                ->and('pref_ws = ' . $sql->quote($res->f('pref_ws')))
                ->group('pref_ws');

            $rs[$i]['key']   = $res->f('pref_ws');
            $rs[$i]['value'] = (int) $sql->select()?->f(0);
            $i++;
        }

        return $rs;
    }

    public function execute(string $action, string $ns): bool
    {
        $sql = new DeleteStatement();

        if ($action == 'delete_global' && self::checkNs($ns)) {
            $sql->from(dcCore::app()->prefix . dcWorkspace::WS_TABLE_NAME)
                ->where('user_id IS NULL')
                ->and('pref_ws = ' . $sql->quote((string) $ns))
                ->delete();

            return true;
        }
        if ($action == 'delete_local' && self::checkNs($ns)) {
            $sql->from(dcCore::app()->prefix . dcWorkspace::WS_TABLE_NAME)
                ->where('user_id = ' . $sql->quote((string) dcCore::app()->blog?->id))
                ->and('pref_ws = ' . $sql->quote((string) $ns))
                ->delete();

            return true;
        }
        if ($action == 'delete_all' && self::checkNs($ns)) {
            $sql->from(dcCore::app()->prefix . dcWorkspace::WS_TABLE_NAME)
                ->where('pref_ws = ' . $sql->quote((string) $ns))
                ->and($sql->orGroup(['user_id IS NULL', 'user_id IS NOT NULL']))
                ->delete();

            return true;
        }
        if ($action == 'delete_related') {
            // check ns match ws:id;
            $reg_ws = substr(dcWorkspace::WS_NAME_SCHEMA, 2, -2);
            $reg_id = substr(dcWorkspace::WS_ID_SCHEMA, 2, -2);
            if (!preg_match_all('#((' . $reg_ws . '):(' . $reg_id . ');?)#', $ns, $matches)) {
                return false;
            }

            // build ws/id requests
            $or = [];
            foreach ($matches[2] as $key => $name) {
                $or[] = $sql->andGroup(['pref_ws = ' . $sql->quote((string) $name), 'pref_id = ' . $sql->quote((string) $matches[3][$key])]);
            }
            if (empty($or)) {
                return false;
            }

            $sql->from(dcCore::app()->prefix . dcWorkspace::WS_TABLE_NAME)
                ->where($sql->orGroup($or))
                ->and($sql->orGroup(['user_id IS NULL', 'user_id IS NOT NULL']))
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
        return preg_match(dcWorkspace::WS_NAME_SCHEMA, $ns);
    }
}
