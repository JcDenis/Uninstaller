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

use Countable;
use dcCore;
use Iterator;
use Exception;

/**
 * The cleaners stack.
 *
 * @implements Iterator<string,CleanerParent>
 */
class CleanersStack implements Countable, Iterator
{
    /** @var    array<string,CleanerParent>   $stack   The cleaner stack */
    private array $stack = [];

    /**
     * Contructor load cleaners.
     */
    public function __construct()
    {
        # --BEHAVIOR-- UninstallerCleanersConstruct: CleanersStack
        dcCore::app()->callBehavior('UninstallerCleanersConstruct', $this);
    }

    public function exists(string $offset): bool
    {
        return isset($this->stack[$offset]);
    }

    /**
     * @return null|CleanerParent
     */
    public function get(string $offset)
    {
        return $this->stack[$offset] ?? null;
    }

    public function set(CleanerParent $value): CleanersStack
    {
        if (!isset($this->stack[$value->id])) {
            $this->stack[$value->id] = $value;
        }

        return $this;
    }

    public function unset(string $offset): void
    {
        unset($this->stack[$offset]);
    }

    public function rewind(): void
    {
        reset($this->stack);
    }

    /**
     * @return false|CleanerParent
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->stack);
    }

    /**
     * @return null|string
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->stack);
    }

    public function next(): void
    {
        next($this->stack);
    }

    public function valid(): bool
    {
        return key($this->stack) !== null;
    }

    public function count(): int
    {
        return count($this->stack);
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
        if (!isset($this->stack[$id])) {
            throw new Exception(sprintf(__('Unknown cleaner "%s"'), $id));
        }
        if (in_array($ns, [My::id(), My::path()])) {
            throw new Exception(__("Unsintaller can't remove itself"));
        }

        # --BEHAVIOR-- UninstallerBeforeAction: string, string, string
        dcCore::app()->callBehavior('UninstallerBeforeAction', $id, $action, $ns);

        return $this->stack[$id]->execute($action, $ns);
    }
}
