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
use Exception;

/**
 * The cleaners stack.
 */
class Cleaners
{
    /** @var    array<string,AbstractCleaner>   $cleaners   The cleaner stack */
    private array $cleaners = [];

    /**
     * Contructor load cleaners.
     */
    public function __construct()
    {
        # --BEHAVIOR-- UninstallerConstruct: Uninstaller
        dcCore::app()->callBehavior('UninstallerCleanersConstruct', $this);
    }

    /**
     * Add a cleaner.
     *
     * @param   AbstractCleaner     $cleaner    The cleaner instance
     *
     * @return  Cleaners    Self instance
     */
    public function add(AbstractCleaner $cleaner): Cleaners
    {
        if (!isset($this->cleaners[$cleaner->id])) {
            $this->cleaners[$cleaner->id] = $cleaner;
        }

        return $this;
    }

    /**
     * Get all clearners.
     *
     * @return  array<string,AbstractCleaner>   The cleaners
     */
    public function dump(): array
    {
        return $this->cleaners;
    }

    /**
     * Get a cleaner.
     *
     * @param   string  $id     The cleaner id
     *
     * @return  null|AbstractCleaner    The cleaner
     */
    public function get(string $id): ?AbstractCleaner
    {
        return $this->cleaners[$id] ?? null;
    }

    /**
     * Execute cleaner action on an value.
     *
     * @param   string  $id         The cleaner id
     * @param   string  $action     The action id
     * @param   string  $ns         The value
     *
     * @return  bool    The success
     */
    public function execute(string $id, string $action, string $ns): bool
    {
        if (!isset($this->cleaners[$id])) {
            throw new Exception(sprintf(__('Unknown cleaner "%s"'), $id));
        }
        if (in_array($ns, [My::id(), My::root()])) {
            throw new Exception(__("Unsintaller can't remove itself"));
        }

        # --BEHAVIOR-- UninstallerBeforeAction: string, string, string
        dcCore::app()->callBehavior('UninstallerBeforeAction', $id, $action, $ns);

        return $this->cleaners[$id]->execute($action, $ns);
    }
}
