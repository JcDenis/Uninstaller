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
use dcModuleDefine;
use dcNsProcess;
use dcPage;
use dcThemes;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Form,
    Hidden,
    Label,
    Link,
    Para,
    Submit,
    Text
};
use Exception;

class Manage extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN')
            && dcCore::app()->auth?->isSuperAdmin()
            && My::phpCompliant();

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        // no module selected
        if (empty($_REQUEST['id'])) {
            self::doRedirect();
        }

        // load dcThemes if required
        if (self::getType() == 'theme' && !is_a(dcCore::app()->themes, 'dcThemes')) {
            dcCore::app()->themes = new dcThemes();
            dcCore::app()->themes->loadModules((string) dcCore::app()->blog?->themes_path);
        }

        // get selected module
        $define = dcCore::app()->{self::getType() . 's'}->getDefine($_REQUEST['id'], ['state' => dcModuleDefine::STATE_ENABLED]);
        if (!$define->isDefined()) {
            dcCore::app()->error->add(__('Unknown module id to uninstall'));
            self::doRedirect();
        }

        // load uninstaller for selected module and check if it has action
        $uninstaller = Uninstaller::instance()->loadModules([$define]);
        $actions     = $uninstaller->getUserActions($define->getId());
        if (!count($actions)) {
            dcCore::app()->error->add(__('There are no uninstall actions for this module'));
            self::doRedirect();
        }

        // nothing to do
        if (empty($_POST)) {
            return true;
        }

        try {
            $done = [];
            // loop through module uninstall actions and execute them
            foreach ($actions as $cleaner => $stack) {
                foreach ($stack as $action) {
                    if (isset($_POST['action'][$cleaner]) && isset($_POST['action'][$cleaner][$action->id])) {
                        if ($uninstaller->execute($cleaner, $action->id, $_POST['action'][$cleaner][$action->id])) {
                            $done[] = $action->success;
                        } else {
                            dcCore::app()->error->add($action->error);
                        }
                    }
                }
            }
            // list success actions
            if (!empty($done)) {
                array_unshift($done, __('Uninstall action successfuly excecuted'));
                dcPage::addSuccessNotice(implode('<br />', $done));
            } else {
                dcPage::addWarningNotice(__('No uninstall action done'));
            }
            self::doRedirect();
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        // load module uninstaller
        $define      = dcCore::app()->{self::getType() . 's'}->getDefine($_REQUEST['id'], ['state' => dcModuleDefine::STATE_ENABLED]);
        $uninstaller = Uninstaller::instance()->loadModules([$define]);
        $fields      = [];

        // custom actions form fields
        if ($uninstaller->hasRender($define->getId())) {
            $fields[] = (new Text('', $uninstaller->render($define->getId())));
        }

        dcPage::openModule(
            My::name(),
            dcPage::jsJson('uninstaller', ['confirm_uninstall' => __('Are you sure you perform these ations?')]) .
            dcPage::jsModuleLoad(My::id() . '/js/manage.js') .

            # --BEHAVIOR-- UninstallerHeader
            dcCore::app()->callBehavior('UninstallerHeader')
        );

        echo
        dcPage::breadcrumb([
            __('System') => '',
            My::name()   => '',
        ]) .
        dcPage::notices();

        // user actions form fields
        foreach ($uninstaller->getUserActions($define->getId()) as $cleaner => $stack) {
            foreach ($stack as $action) {
                $fields[] = (new Para())->items([
                    (new Checkbox(['action[' . $cleaner . '][' . $action->id . ']', 'action_' . $cleaner . '_' . $action->id], $action->default))->value($action->ns),
                    (new Label($action->query, Label::OUTSIDE_LABEL_AFTER))->for('action_' . $cleaner . '_' . $action->id)->class('classic'),
                ]);
            }
        }

        // submit
        $fields[] = (new Para())->separator(' ')->items([
            dcCore::app()->formNonce(false),
            (new Hidden(['type'], self::getType())),
            (new Hidden(['id'], $define->getId())),
            (new Submit(['do']))->value(__('Perform selected actions'))->class('delete'),
            (new Link())->class('button')->text(__('Cancel'))->href(self::getRedirect()),
        ]);

        // display form
        echo (new Div())->items([
            (new Text('h3', sprintf((self::getType() == 'theme' ? __('Uninstall theme "%s"') : __('Uninstall plugin "%s"')), __($define->get('name'))))),
            (new Text('p', sprintf(__('The module "%s %s" offers advanced unsintall process:'), $define->getId(), $define->get('version')))),
            (new Form('uninstall-form'))->method('post')->action(dcCore::app()->adminurl?->get('admin.plugin.' . My::id()))->fields($fields),
        ])->render();

        dcPage::closeModule();
    }

    private static function getType(): string
    {
        return ($_REQUEST['type'] ?? 'theme') == 'theme' ? 'theme' : 'plugin';
    }

    private static function getRedir(): string
    {
        return self::getType() == 'theme' ? 'admin.blog.theme' : 'admin.plugins';
    }

    private static function getRedirect(): string
    {
        return (string) dcCore::app()->adminurl?->get(self::getRedir()) . '#' . self::getType() . 's';
    }

    private static function doRedirect(): void
    {
        dcCore::app()->adminurl?->redirect(self::getRedir(), [], '#' . self::getType() . 's');
    }
}
