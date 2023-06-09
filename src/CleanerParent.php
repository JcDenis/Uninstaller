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

/**
 * Cleaner abstract class.
 *
 * Cleaner manages only one part of uninstall process.
 * For exemple Settings, Caches, db, etc...
 */
abstract class CleanerParent
{
    /** @var    string  $id     The cleaner Id */
    public readonly string $id;

    /** @var    string  $id     The cleaner name */
    public readonly string $name;

    /** @var    string  $id     The cleaner description */
    public readonly string $desc;

    /** @var    array<string,ActionDescriptor>  $actions    The cleaner actions decriptions */
    public readonly array $actions;

    /**
     * Constructor set up a Cleaner.
     */
    public function __construct(CleanerDescriptor $descriptor)
    {
        $this->id      = $descriptor->id;
        $this->name    = $descriptor->name;
        $this->desc    = $descriptor->desc;
        $this->actions = $descriptor->actions;
    }

    /**
     * Get an action description.
     *
     * @return  null|ActionDescriptor   The action descriptor
     */
    final public function get(string $id)
    {
        return $this->actions[$id] ?? null;
    }

    /**
     * Get list of distirbuted values for the cleaner.
     *
     * @return  array<int,string>   The values [value,]
     */
    abstract public function distributed(): array;

    /**
     * Get all possible ns values from the cleaner.
     *
     * @return  array<int,ValueDescriptor>  The values.
     */
    abstract public function values(): array;

    /**
     * Get all related values for a namespace from the cleaner.
     *
     * @param   string  $ns     The namespace
     *
     * @return  array<int,ValueDescriptor>  The values.
     */
    public function related(string $ns): array
    {
        return [];
    }

    /**
     * Execute action on an value.
     *
     * @param   string  $action     The action id
     * @param   string  $ns         The value.
     *
     * @return  bool    The success
     */
    abstract public function execute(string $action, string $ns): bool;
}
