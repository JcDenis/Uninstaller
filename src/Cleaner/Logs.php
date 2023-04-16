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
    ActionDescriptor
};

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
            'maintenance',
        ];
    }

    public function values(): array
    {
        $sql = new SelectStatement();
        $sql->from(dcCore::app()->prefix . dcLog::LOG_TABLE_NAME)
            ->columns(['log_table'])
            ->where($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
            ->group('log_table');

        $res = $sql->select();
        if ($res == null || $res->isEmpty()) {
            return [];
        }

        $rs = [];
        $i  = 0;
        while ($res->fetch()) {
            $sql = new SelectStatement();
            $sql->from(dcCore::app()->prefix . dcLog::LOG_TABLE_NAME)
                ->fields([$sql->count('*')])
                ->where($sql->orGroup(['blog_id IS NULL', 'blog_id IS NOT NULL']))
                ->and('log_table = ' . $sql->quote($res->f('log_table')))
                ->group('log_table');

            $rs[$i]['key']   = $res->f('log_table');
            $rs[$i]['value'] = (int) $sql->select()?->f(0);
            $i++;
        }

        return $rs;
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
