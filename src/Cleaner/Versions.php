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
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Plugin\Uninstaller\{
    AbstractCleaner,
    ActionDescriptor,
    ValueDescriptor
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
                'select'  => __('delete selected versions numbers'),
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
        $sql = new SelectStatement();
        $rs  = $sql
            ->from(dcCore::app()->prefix . dcCore::VERSION_TABLE_NAME)
            ->columns(['module', 'version'])
            ->select();

        if (is_null($rs) || $rs->isEmpty()) {
            return [];
        }

        $res = [];
        while ($rs->fetch()) {
            $res[] = new ValueDescriptor(
                $rs->f('module'),
                $rs->f('version'),
                1
            );
        }

        return $res;
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
