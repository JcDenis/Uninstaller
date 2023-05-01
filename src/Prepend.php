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
        dcCore::app()->addBehavior('UninstallerCleanersConstruct', function (CleanersStack $cleaners): void {
            $cleaners
                ->set(new Cleaner\Settings())
                ->set(new Cleaner\Preferences())
                ->set(new Cleaner\Tables())
                ->set(new Cleaner\Versions())
                ->set(new Cleaner\Logs())
                ->set(new Cleaner\Caches())
                ->set(new Cleaner\Vars())
                ->set(new Cleaner\Themes())
                ->set(new Cleaner\Plugins())
            ;
        });

        return true;
    }
}
