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
use dcLog;
use Dotclear\Database\Statement\{
    DeleteStatement,
    SelectStatement
};
use Dotclear\Plugin\Uninstaller\{
    AbstractCleaner,
    ActionDescriptor,
    ValueDescriptor
};

/**
 * Cleaner for Dotclear logs used by modules.
 *
 * It allows modules to delete a "log_table"
 * of Dotclear dcLog::LOG_TABLE_NAME database table.
 */
class Logs extends AbstractCleaner
{
    protected function properties(): array
    {
        return [
            'id'   => 'logs',
            'name' => __('Logs'),
            'desc' => __('Logs in Dotclear logs table'),
        ];
    }

    protected function actions(): array
    {
        return [
            // delete all $ns log_table entries
            new ActionDescriptor([
                'id'      => 'delete_all',
                'select'  => __('delete selected logs tables'),
                'query'   => __('delete "%s" logs table'),
                'success' => __('"%s" logs table deleted'),
                'error'   => __('Failed to delete "%s" logs table'),
            ]),
        ];
    }

    public function distributed(): array
    {
        return [
            'dcDeprecated',
            'maintenance',
        ];
    }

    public function values(): array
    {
        $sql = new SelectStatement();
        $sql->from(dcCore::app()->prefix . dcLog::LOG_TABLE_NAME)
            ->columns([
                $sql->as($sql->count('*'), 'counter'),
                'log_table',
            ])
            ->where($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
            ->group('log_table');

        $rs = $sql->select();
        if (is_null($rs) || $rs->isEmpty()) {
            return [];
        }

        $res = [];
        while ($rs->fetch()) {
            $res[] = new ValueDescriptor(
                $rs->f('log_table'),
                '',
                (int) $rs->f('counter')
            );
        }

        return $res;
    }

    public function execute(string $action, string $ns): bool
    {
        if ($action == 'delete_all') {
            $sql = new DeleteStatement();
            $sql->from(dcCore::app()->prefix . dcLog::LOG_TABLE_NAME)
                ->where('log_table = ' . $sql->quote((string) $ns))
                ->and($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
                ->delete();

            return true;
        }

        return false;
    }
}
