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
 * Cleaner descriptor.
 */
class CleanerDescriptor
{
    /** @var    array<string,ActionDescriptor>  $actions    The actions descriptions */
    public readonly array $actions;

    /**
     * Contructor populate descriptor properties.
     *
     * @param   string                          $id         The cleaner ID
     * @param   string                          $name       The cleaner name
     * @param   string                          $desc       The cleaner description
     * @param   array<int,ActionDescriptor>     $actions    The actions descriptions
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $desc,
        array $actions
    ) {
        $valid = [];
        foreach ($actions as $action) {
            if (is_a($action, ActionDescriptor::class) && $action->id != 'undefined') {
                $valid[$action->id] = $action;
            }
        }
        $this->actions = $valid;
    }

    /**
     * Get descriptor properties.
     *
     * @return  array<string,mixed>     The properties
     */
    public function dump(): array
    {
        return get_object_vars($this);
    }
}
