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

namespace Dotclear\Plugin\Uninstaller;

use dcCore;
use dcNsProcess;

class Prepend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN')
            && My::phpCompliant()
            && dcCore::app()->auth?->isSuperAdmin();

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        // Add cleaners to Uninstaller
        dcCore::app()->addBehavior('UninstallerAddCleaner', [Cleaner\Settings::class, 'create']);
        dcCore::app()->addBehavior('UninstallerAddCleaner', [Cleaner\Tables::class, 'create']);
        dcCore::app()->addBehavior('UninstallerAddCleaner', [Cleaner\Versions::class, 'create']);
        dcCore::app()->addBehavior('UninstallerAddCleaner', [Cleaner\Logs::class, 'create']);
        dcCore::app()->addBehavior('UninstallerAddCleaner', [Cleaner\Caches::class, 'create']);
        dcCore::app()->addBehavior('UninstallerAddCleaner', [Cleaner\Vars::class, 'create']);
        dcCore::app()->addBehavior('UninstallerAddCleaner', [Cleaner\Themes::class, 'create']);
        dcCore::app()->addBehavior('UninstallerAddCleaner', [Cleaner\Plugins::class, 'create']);

        return true;
    }
}
