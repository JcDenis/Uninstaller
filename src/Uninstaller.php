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
    /** @var    Uninstaller     $uninstaller   Uninstaller instance */
    private static $uninstaller;

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
            $class = $module->get('namespace') . '\\Uninstall';
            if ($module->getId() != My::id() && is_a($class, dcNsProcess::class, true)) {
                $this->modules[$module->getId()] = $this->module = $module;
                if ($class::init()) {
                    if ($class::process()) {
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
     * Get a module <var>$id</var> Define if it exists.
     *
     * @param   string  $id     The module ID
     *
     * @return  dcModuleDefine   Module Define
     */
    public function getModule(string $id): ?dcModuleDefine
    {
        return $this->modules[$id] ?? null;
    }

    /**
     * Get all modules Define.
     *
     * @return  array   Modules Define
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Check if the module <var>$id</var> exists.
     *
     * @param   string  $id     Module ID
     *
     * @return  boolean     Success
     */
    public function hasModule(string $id): bool
    {
        return isset($this->modules[$id]);
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
     * @return  array   Modules id
     */
    public function getUserActions(string $id): array
    {
        return $this->getActions('user', $id);
    }

    /**
     * Get modules <var>$id</var> predefined direct actions associative array
     *
     * @param   string  $id     The module ID
     * @return  array   Modules id
     */
    public function getDirectActions(string $id): array
    {
        return $this->getActions('direct', $id);
    }

    /**
     * Get module <var>$id</var> custom actions fields.
     *
     * @param   string  $id     The module ID
     * @return  string  HTML render of custom form fields
     */
    public function render(string $id): string
    {
        $output = '';
        if ($this->hasRender($id)) {
            $class = $this->getModule($id)?->get('namespace') . '\\Uninstall';

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
     * @return boolean      Success
     */
    public function execute(string $cleaner, string $action, string $ns): bool
    {
        if (!isset($this->cleaners->get($cleaner)->actions[$action]) || empty($ns)) {
            return false;
        }
        $this->cleaners->execute($cleaner, $action, $ns);

        return true;
    }

    private function addAction(string $group, string $cleaner, string $action, string $ns): void
    {
        // invalid group or no current module or no cleaner id or ns
        if (!self::group($group) || null === $this->module || empty($cleaner) || empty($ns)) {
            return;
        }
        // unknow cleaner action
        if (!isset($this->cleaners->get($cleaner)->actions[$action])) {
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

    private function getActions(string $group, string $id): array
    {
        if (!self::group($group) || !isset($this->actions[$group][$id])) {
            return [];
        }
        $res = [];
        foreach ($this->cleaners->dump() as $cleaner) {
            if (!isset($this->actions[$group][$id][$cleaner->id])) {
                continue;
            }
            $res[$cleaner->id] = $this->actions[$group][$id][$cleaner->id];
        }

        return $res;
    }

    private function group(string $group): bool
    {
        return in_array($group, ['user', 'direct']);
    }
}
