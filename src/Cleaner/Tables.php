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
use Dotclear\Database\{
    AbstractSchema,
    Structure
};
use Dotclear\Database\Statement\{
    DeleteStatement,
    DropStatement,
    SelectStatement
};
use Dotclear\Plugin\Uninstaller\{
    ActionDescriptor,
    CleanerDescriptor,
    CleanerParent,
    ValueDescriptor
};

/**
 * Cleaner for Dotclear cache directory used by modules.
 *
 * It allows modules to delete or truncate a database table.
 */
class Tables extends CleanerParent
{
    public function __construct()
    {
        parent::__construct(new CleanerDescriptor(
            id:   'tables',
            name: __('Tables'),
            desc: __('All database tables of Dotclear'),
            actions: [
                // delete $ns database table
                new ActionDescriptor(
                    id:      'delete',
                    select:  __('delete selected tables'),
                    query:   __('delete "%s" table'),
                    success: __('"%s" table deleted'),
                    error:   __('Failed to delete "%s" table'),
                    default: false
                ),
                // truncate (empty) $ns database table
                new ActionDescriptor(
                    id:      'empty',
                    select:  __('empty selected tables'),
                    query:   __('empty "%s" table'),
                    success: __('"%s" table emptied'),
                    error:   __('Failed to empty "%s" table'),
                    default: false
                ),
            ]
        ));
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
        $object = AbstractSchema::init(dcCore::app()->con);
        $tables = $object->getTables();

        $res = [];
        foreach ($tables as $k => $v) {
            // get only tables with dotclear prefix
            if ('' != dcCore::app()->prefix) {
                if (!preg_match('/^' . preg_quote(dcCore::app()->prefix) . '(.*?)$/', $v, $m)) {
                    continue;
                }
                $v = $m[1];
            }

            $sql = new SelectStatement();

            $res[] = new ValueDescriptor(
                ns:    (string) $v,
                count: (int) $sql->from($tables[$k])->fields([$sql->count('*')])->select()?->f(0)
            );
        }

        return $res;
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
            $struct = new Structure(dcCore::app()->con, dcCore::app()->prefix);
            if ($struct->tableExists($ns)) {
                $sql = new DropStatement();
                $sql->from(dcCore::app()->prefix . $ns)
                    ->drop();
            }

            return true;
        }

        return false;
    }
}
