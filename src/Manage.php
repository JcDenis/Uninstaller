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
use dcPage;
use dcThemes;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Form,
    Hidden,
    Label,
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

        $type  = ($_REQUEST['type'] ?? 'theme') == 'theme' ? 'themes' : 'plugins';
        $redir = $type                          == 'themes' ? ['blog.themes', [], '#themes'] : ['admin.plugins', [], '#plugins'];

        if (empty($_REQUEST['id'])) {
            dcCore::app()->adminurl->redirect($redir[0], $redir[1], $redir[2]);
        }

        if ($type == 'themes' && !is_a(dcCore::app()->themes, 'dcThemes')) {
            dcCore::app()->themes = new dcThemes();
            dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path, null);
        }

        $define = dcCore::app()->{$type}->getDefine($_REQUEST['id']);
        if (!$define->isDefined()) {
            dcCore::app()->error->add(__('Unknown module id to uninstall'));
            dcCore::app()->adminurl->redirect($redir[0], $redir[1], $redir[2]);
        }

        $uninstaller = Uninstaller::instance()->loadModules([$define]);
        $actions     = $uninstaller->getUserActions($define->getId());
        if (empty($actions)) {
            dcCore::app()->error->add(__('There are no uninstall actions for this module'));
            dcCore::app()->adminurl->redirect($redir[0], $redir[1], $redir[2]);
        }

        if (empty($_POST)) {
            return true;
        }

        try {
            $done = [];
            foreach ($actions as $cleaner => $stack) {
                foreach ($stack as $action) {
                    if (isset($_POST['action'][$cleaner]) && isset($_POST['action'][$cleaner][$action['action']])) {
                        if ($uninstaller->execute($cleaner, $action['action'], $_POST['action'][$cleaner][$action['action']])) {
                            $done[] = $action['success'];
                        } else {
                            dcCore::app()->error->add($action['error']);
                        }
                    }
                }
            }
            if (!empty($done)) {
                array_unshift($done, __('Uninstall action successfuly excecuted'));
                dcPage::addSuccessNotice(implode('<br />', $done));
            } else {
                dcPage::addWarningNotice(__('No uninstall action done'));
            }
            dcCore::app()->adminurl->redirect($redir[0], $redir[1], $redir[2]);
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

        $type        = $_REQUEST['type'] == 'theme' ? 'themes' : 'plugins';
        $redir       = $type             == 'themes' ? ['blog.themes', [], '#themes'] : ['admin.plugins', [], '#plugins'];
        $define      = dcCore::app()->{$type}->getDefine($_REQUEST['id']);
        $uninstaller = Uninstaller::instance()->loadModules([$define]);
        $fields      = [];

        // custom actions form fields
        if ($uninstaller->hasRender($define->getId())) {
            $fields[] = (new Text('', $uninstaller->render($define->getId())));
        }

        dcPage::openModule(
            My::name(),
            dcPage::jsJson('uninstaller', ['confirm_uninstall' => __('Are you sure you perform these ations?')]) .
            dcPage::jsModuleLoad(My::id() . '/js/backend.js') .

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
                    (new Checkbox(['action[' . $cleaner . '][' . $action['action'] . ']', 'action_' . $cleaner . '_' . $action['action']], true))->value($action['ns']),
                    (new Label($action['query'], Label::OUTSIDE_LABEL_AFTER))->for('action_' . $cleaner . '_' . $action['action'])->class('classic'),
                ]);
            }
        }

        // submit
        $fields[] = (new Para())->items([
            dcCore::app()->formNonce(false),
            (new Hidden(['type'], $type)),
            (new Hidden(['id'], $define->getId())),
            (new Submit(['do']))->value(__('Perform selected actions'))->class('delete'),
            (new Text('', ' <a class="button" href="' . dcCore::app()->adminurl->get($redir[0], $redir[1]) . $redir[2] . '">' . __('Cancel') . '</a>')),
        ]);

        // display form
        echo (new Div())->items([
            (new Text('h3', sprintf(($type == 'themes' ? __('Uninstall theme "%s"') : __('Uninstall plugin "%s"')), __($define->get('name'))))),
            (new Text('p', sprintf(__('The module "%s %s" offers advanced unsintall process:'), $define->getId(), $define->get('version')))),
            (new Form('uninstall-form'))->method('post')->action(dcCore::app()->adminurl->get('admin.plugin.' . My::id()))->fields($fields),
        ])->render();

        dcPage::closeModule();
    }
}
