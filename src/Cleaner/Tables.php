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

use dbSchema;
use dcCore;
use Dotclear\Plugin\Uninstaller\{
    AbstractCleaner,
    ActionDescriptor
};

class Tables extends AbstractCleaner
{
    protected function properties(): array
    {
        return [
            'id'   => 'tables',
            'name' => __('Tables'),
            'desc' => __('All database tables of Dotclear'),
        ];
    }

    protected function actions(): array
    {
        return [
            new ActionDescriptor([
                'id'      => 'delete',
                'query'   => __('delete "%s" table'),
                'success' => __('"%s" table deleted'),
                'error'   => __('Failed to delete "%s" table'),
            ]),
            new ActionDescriptor([
                'id'      => 'empty',
                'query'   => __('empty "%s" table'),
                'success' => __('"%s" table emptied'),
                'error'   => __('Failed to empty "%s" table'),
            ]),
        ];
    }

    public function distributed(): array
    {
        return [
            'blog',
            'category',
            'comment',
            'link',
            'log',
            'media',
            'meta',
            'permissions',
            'ping',
            'post',
            'post_media',
            'pref',
            'session',
            'setting',
            'spamrule',
            'user',
            'version',
        ];
    }

    public function values(): array
    {
        $object = dbSchema::init(dcCore::app()->con);
        $res    = $object->getTables();

        $rs = [];
        $i  = 0;
        foreach ($res as $k => $v) {
            if ('' != dcCore::app()->prefix) {
                if (!preg_match('/^' . preg_quote(dcCore::app()->prefix) . '(.*?)$/', $v, $m)) {
                    continue;
                }
                $v = $m[1];
            }
            $rs[$i]['key']   = $v;
            $rs[$i]['value'] = dcCore::app()->con->select('SELECT count(*) FROM ' . $res[$k])->f(0);
            $i++;
        }

        return $rs;
    }

    public function execute(string $action, string $ns): bool
    {
        if (in_array($action, ['empty', 'delete'])) {
            dcCore::app()->con->execute(
                'DELETE FROM ' . dcCore::app()->con->escapeSystem(dcCore::app()->prefix . $ns)
            );
        }
        if ($action == 'empty') {
            return true;
        }
        if ($action == 'delete') {
            dcCore::app()->con->execute(
                'DROP TABLE ' . dcCore::app()->con->escapeSystem(dcCore::app()->prefix . $ns)
            );

            return true;
        }

        return false;
    }
}
