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
use Exception;

class Install extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN') && My::phpCompliant()) {
            $version      = dcCore::app()->plugins->moduleInfo(My::id(), 'version');
            static::$init = is_string($version) ? dcCore::app()->newVersion(My::id(), $version) : true;
        }

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        try {
            dcCore::app()->blog?->settings->get('system')->put(
                'no_direct_uninstall',
                false,
                'boolean',
                'Disabled uninstall actions on module deletion',
                false,
                true
            );

            return true;
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return false;
        }
    }
}
