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

use adminModulesList;
use dcCore;
use dcModuleDefine;
use dcNsProcess;
use dcPage;
use dcUtils;
use Exception;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN');

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->addBehaviors([
            // add "unsinstall" button to modules list
            'adminModulesListGetActionsV2' => function (adminModulesList $list, dcModuleDefine $define): string {
                // do not unsintall current theme
                if ($define->get('type') == 'theme' && $define->getId() == dcCore::app()->blog?->settings->get('system')->get('theme')) {
                    return '';
                }

                return !count(Uninstaller::instance()->loadModules([$define])->getUserActions($define->getId())) ? '' :
                    sprintf(
                        ' <a href="%s" class="button delete uninstall_module_button">' . __('Uninstall') . '</a>',
                        dcCore::app()->adminurl?->get('admin.plugin.' . My::id(), ['type' => $define->get('type'), 'id' => $define->getId()])
                    );
            },
            // perform direct action on theme deletion
            'themeBeforeDeleteV2' => function (dcModuleDefine $define): void {
                self::moduleBeforeDelete($define);
            },
            // perform direct action on plugin deletion
            'pluginBeforeDeleteV2' => function (dcModuleDefine $define): void {
                self::moduleBeforeDelete($define);
            },
            // add js to hide delete button when uninstaller exists
            'pluginsToolsHeadersV2' => function (): string {
                return self::modulesToolsHeader();
            },
            // add js to hide delete button when uninstaller exists
            'themesToolsHeadersV2' => function (): string {
                return self::modulesToolsHeader();
            },
        ]);

        return true;
    }

    /**
     * Perfom direct action on module deletion.
     *
     * This does not perform action on disabled module.
     *
     * @param   dcModuleDefine  $define     The module
     */
    protected static function moduleBeforeDelete(dcModuleDefine $define): void
    {
        if (dcCore::app()->blog?->settings->get('system')->get('no_uninstall_direct')) {
            return;
        }

        try {
            $uninstaller = Uninstaller::instance()->loadModules([$define]);

            // Do not perform action on disabled module if a duplicate exists.
            if ($define->get('state') != dcModuleDefine::STATE_ENABLED) {
                if (!in_array($define->get('type'), ['plugin', 'theme']) 
                    || $define->get('type') == 'plugin' && 1 < count(dcCore::app()->plugins->getDefines(['id' => $define->getId()]))
                    || $define->get('type') == 'theme' && 1 < count(dcCore::app()->themes->getDefines(['id' => $define->getId()]))
                ) {
                    return;
                }
            }

            $done = [];
            foreach ($uninstaller->getDirectActions($define->getId()) as $cleaner => $stack) {
                foreach ($stack as $action) {
                    if ($uninstaller->execute($cleaner, $action->id, $action->ns)) {
                        $done[] = $action->success;
                    } else {
                        dcCore::app()->error->add($action->error);
                    }
                }
            }

            // if direct actions are made, do not execute dotclear delete action.
            if (!empty($done)) {
                array_unshift($done, __('Plugin has been successfully uninstalled.'));
                dcPage::addSuccessNotice(implode('<br />', $done));
                if ($define->get('type') == 'theme') {
                    dcCore::app()->adminurl?->redirect('admin.blog.theme', [], '#themes');
                } else {
                    dcCore::app()->adminurl?->redirect('admin.plugins', [], '#plugins');
                }
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    /**
     * Get backend URL of uninstaller js.
     *
     * @return  string  The URL
     */
    protected static function modulesToolsHeader(): string
    {
        return dcUtils::jsModuleLoad(My::id() . '/js/backend.js');
    }
}
