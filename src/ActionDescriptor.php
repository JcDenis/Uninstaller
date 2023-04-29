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
 * Cleaner action descriptor
 */
class ActionDescriptor
{
    /**
     * Contructor populate descriptor properties.
     *
     * @param   string  $id         The action ID
     * @param   string  $query      The query message
     * @param   string  $success    The succes message
     * @param   string  $error      The error message
     * @param   string  $ns         The namespace (for defined action)
     * @param   string  $select     The generic message (used for self::values() management)
     */
    public function __construct(
        public readonly string $id,
        public readonly string $query,
        public readonly string $success,
        public readonly string $error,
        public readonly string $ns = '',
        public readonly string $select = ''
    ) {
    }

    /**
     * Get descriptor properties.
     *
     * @return  array<string,string>    The properties
     */
    public function dump(): array
    {
        return get_object_vars($this);
    }
}
