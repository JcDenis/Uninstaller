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
use Dotclear\Database\Statement\{
    DeleteStatement,
    DropStatement,
    SelectStatement
};
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
                'select'  => __('delete selected tables'),
                'query'   => __('delete "%s" table'),
                'success' => __('"%s" table deleted'),
                'error'   => __('Failed to delete "%s" table'),
            ]),
            new ActionDescriptor([
                'id'      => 'empty',
                'select'  => __('empty selected tables'),
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
            // get only tables with dotclear prefix
            if ('' != dcCore::app()->prefix) {
                if (!preg_match('/^' . preg_quote(dcCore::app()->prefix) . '(.*?)$/', $v, $m)) {
                    continue;
                }
                $v = $m[1];
            }

            $sql = new SelectStatement();
            $sql->from(dcCore::app()->prefix . $res[$k])
                ->fields([$sql->count('*')]);

            $rs[$i]['key']   = $v;
            $rs[$i]['value'] = (int) $sql->select()?->f(0);
            ;
            $i++;
        }

        return $rs;
    }

    public function execute(string $action, string $ns): bool
    {
        if (in_array($action, ['empty', 'delete'])) {
            $sql = new DeleteStatement();
            $sql->from(dcCore::app()->prefix . $ns)
                ->delete();
        }
        if ($action == 'empty') {
            return true;
        }
        if ($action == 'delete') {
            $sql = new DropStatement();
            $sql->from(dcCore::app()->prefix . $ns)
                ->drop();

            return true;
        }

        return false;
    }
}
