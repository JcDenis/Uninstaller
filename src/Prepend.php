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
use Dotclear\Core\Process;

class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
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
