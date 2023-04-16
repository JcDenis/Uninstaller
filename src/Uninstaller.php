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

use dcModuleDefine;
use dcNsProcess;
use dcUtils;
use Exception;

/**
 * @brief Modules uninstall features handler
 *
 * Provides an object to handle modules uninstall features.
 */
class Uninstaller
{
    /** @var    string  The Uninstall class name */
    public const UNINSTALL_CLASS_NAME = 'Uninstall';

    /** @var    Cleaners    $cleaners The cleaners stack */
    public readonly Cleaners $cleaners;

    /** @var    null|dcModuleDefine     $module Current module */
    private ?dcModuleDefine $module = null;

    /** @var    array<string,dcModuleDefine>    $modules Loaded modules stack */
    private array $modules = [];

    /** @var    array<int,string>   List of modules with custom actions render */
    private array $renders = [];

    /** @var    array   List of registered actions */
    private array $actions = ['user' => [], 'direct' => []];

    /** @var    Uninstaller     $uninstaller   Uninstaller instance */
    private static $uninstaller;

    /**
     * Constructor load cleaners.
     */
    public function __construct()
    {
        $this->cleaners = new Cleaners();
    }

    /**
     * Get singleton instance.
     *
     * @return  Uninstaller     Uninstaller instance
     */
    public static function instance(): Uninstaller
    {
        if (!is_a(self::$uninstaller, Uninstaller::class)) {
            self::$uninstaller = new Uninstaller();
        }

        return self::$uninstaller;
    }

    /**
     * Load modules.
     *
     * Load modules resets previously loaded modules and actions.
     *
     * @param   array<int,dcModuleDefine>  $modules   List of modules Define
     *
     * @return  Uninstaller     Uninstaller instance
     */
    public function loadModules(array $modules): Uninstaller
    {
        // reset unsintaller
        $this->module  = null;
        $this->modules = [];
        $this->renders = [];
        $this->actions = ['user' => [], 'direct' => []];

        foreach ($modules as $module) {
            if (!($module instanceof dcModuleDefine)) {
                continue;
            }
            $class = $module->get('namespace') . '\\' . self::UNINSTALL_CLASS_NAME;
            if ($module->getId() != My::id() && is_a($class, dcNsProcess::class, true)) {
                $this->modules[$module->getId()] = $this->module = $module;
                // check class prerequiretics
                if ($class::init()) {
                    // if class process returns true
                    if ($class::process()) {
                        // add custom action (served by class render method )
                        $this->renders[] = $module->getId();
                    }
                    $this->module = null;
                }
            }
        }
        uasort(
            $this->modules,
            fn ($a, $b) => dcUtils::removeDiacritics(mb_strtolower($a->get('name'))) <=> dcUtils::removeDiacritics(mb_strtolower($b->get('name')))
        );

        return $this;
    }

    /**
     * Check if the module <var>$id</var> has action custom fields.
     *
     * @param   string  $id     Module ID
     *
     * @return  boolean     Success
     */
    public function hasRender(string $id): bool
    {
        return isset($this->modules[$id]) && in_array($id, $this->renders);
    }

    /**
     * Add a predefined action to user unsintall features.
     *
     * This method should be called from module Uninstall::proces() method.
     * User will be prompted before doing these actions.
     *
     * @param   string  $cleaner    The cleaner ID
     * @param   string  $action     The action ID
     * @param   string  $ns         Name of setting related to module
     *
     * @return  Uninstaller     Uninstaller instance
     */
    public function addUserAction(string $cleaner, string $action, string $ns): Uninstaller
    {
        $this->addAction('user', $cleaner, $action, $ns);

        return $this;
    }

    /**
     * Add a predefined action to direct unsintall features.
     *
     * This method should be called from module Uninstall::proces() method.
     * Direct actions will be called from behavior xxxBeforeDelete and
     * user will NOT be prompted before these actions execution.
     * Note: If module is disabled, direct actions are not executed.
     *
     * @param   string  $cleaner    The cleaner ID
     * @param   string  $action     The action ID
     * @param   string  $ns         Name of setting related to module.
     *
     * @return  Uninstaller     Uninstaller instance
     */
    public function addDirectAction(string $cleaner, string $action, string $ns): Uninstaller
    {
        $this->addAction('direct', $cleaner, $action, $ns);

        return $this;
    }

    /**
     * Get modules <var>$id</var> predefined user actions associative array
     *
     * @param   string  $id     The module ID
     *
     * @return  array   List module user actions group by cleaner
     */
    public function getUserActions(string $id): array
    {
        return $this->actions['user'][$id] ?? [];
    }

    /**
     * Get modules <var>$id</var> predefined direct actions associative array
     *
     * @param   string  $id     The module ID
     *
     * @return  array   List module direct actions group by cleaner
     */
    public function getDirectActions(string $id): array
    {
        return $this->actions['direct'][$id] ?? [];
    }

    /**
     * Get module <var>$id</var> custom actions fields.
     *
     * @param   string  $id     The module ID
     *
     * @return  string  HTML render of custom form fields
     */
    public function render(string $id): string
    {
        $output = '';
        if ($this->hasRender($id)) {
            $class = $this->modules[$id]->get('namespace') . '\\' . self::UNINSTALL_CLASS_NAME;

            ob_start();

            try {
                $class::render();
                $output = (string) ob_get_contents();
            } catch (Exception $e) {
            }
            ob_end_clean();
        }

        return $output;
    }

    /**
     * Execute a predifined action.
     *
     * This function call dcAdvancedCleaner to do actions.
     *
     * @param   string      $cleaner    The cleaner ID
     * @param   string      $action     The action ID
     * @param   string      $ns         Name of setting related to module.
     *
     * @return  boolean     The success
     */
    public function execute(string $cleaner, string $action, string $ns): bool
    {
        // unknown cleaner/action or no ns
        if (!isset($this->cleaners->get($cleaner)->actions[$action]) || empty($ns)) {
            return false;
        }

        $this->cleaners->execute($cleaner, $action, $ns);

        return true;
    }

    /**
     * Add a predefined action to unsintall features.
     *
     * @param   string  $group      The group (user or direct)
     * @param   string  $cleaner    The cleaner ID
     * @param   string  $action     The action ID
     * @param   string  $ns         Name of setting related to module.
     */
    private function addAction(string $group, string $cleaner, string $action, string $ns): void
    {
        // no current module or no cleaner id or no ns or unknown cleaner action
        if (null === $this->module
            || empty($cleaner)
            || empty($ns)
            || !isset($this->cleaners->get($cleaner)->actions[$action])
        ) {
            return;
        }

        // fill action properties
        $this->actions[$group][$this->module->getId()][$cleaner][] = new ActionDescriptor([
            'id'      => $action,
            'ns'      => $ns,
            'select'  => $this->cleaners->get($cleaner)->actions[$action]->select,
            'query'   => sprintf($this->cleaners->get($cleaner)->actions[$action]->query, $ns),
            'success' => sprintf($this->cleaners->get($cleaner)->actions[$action]->success, $ns),
            'error'   => sprintf($this->cleaners->get($cleaner)->actions[$action]->error, $ns),
        ]);
    }
}
