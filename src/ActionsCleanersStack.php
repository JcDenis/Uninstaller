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
use Iterator;

/**
 * Cleaner actions cleaners stack.
 *
 * @implements Iterator<int,ActionDescriptor>
 */
class ActionsCleanersStack implements Countable, Iterator
{
    /** @var    array<int,ActionDescriptor>  $stack  The stack */
    private array $stack = [];

    public function exists(int $offset): bool
    {
        return isset($this->stack[$offset]);
    }

    /**
     * @return null|ActionDescriptor
     */
    public function get(int $offset)
    {
        return $this->stack[$offset] ?? null;
    }

    public function set(ActionDescriptor $value): void
    {
        $this->stack[] = $value;
    }

    public function unset(int $offset): void
    {
        unset($this->stack[$offset]);
    }

    public function rewind(): void
    {
        reset($this->stack);
    }

    /**
     * @return false|ActionDescriptor
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->stack);
    }

    /**
     * @return null|int
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
}
