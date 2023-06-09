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
 * Cleaner actions stack.
 *
 * @implements Iterator<string,ActionsCleanersStack>
 */
class ActionsStack implements Countable, Iterator
{
    /** @var    array<string,ActionsCleanersStack>  $stack  The stack */
    private array $stack = [];

    public function exists(string $offset): bool
    {
        return isset($this->stack[$offset]);
    }

    public function get(string $offset): ActionsCleanersStack
    {
        if (!$this->exists($offset)) {
            $this->set($offset, new ActionsCleanersStack());
        }

        return $this->stack[$offset];
    }

    public function set(string $offset, ActionsCleanersStack $value): void
    {
        $this->stack[$offset] = $value;
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
     * @return false|ActionsCleanersStack
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->stack);
    }

    /**
     * return null|string
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
