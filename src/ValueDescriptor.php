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
 * Cleaner value descriptor
 */
class ValueDescriptor
{
    /**
     * Contructor populate descriptor properties.
     *
     * @param   string  $ns     The namespace
     * @param   string  $id     The ID on the namespace
     * @param   int     $count  The count of ID on the namespace
     */
    public function __construct(
        public readonly string $ns,
        public readonly string $id,
        public readonly int $count,

    ) {
    }

    /**
     * Get descriptor properties.
     *
     * @return  array<string,string>    The properties
     */
    public function dump(): array
    {
        return [
            'ns'      => $this->ns,
            'id'      => $this->id,
            'count'  => $this->count,
        ];
    }
}
