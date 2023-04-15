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
    /** @var    string  $id     The action ID */
    public readonly string $id;

    /** @var    string  $query  The orm query message */
    public readonly string $query;

    /** @var    string  $success    The succes message */
    public readonly string $success;

    /** @var    string  $error  The error message */
    public readonly string $error;

    /**
     * Contructor populate descriptor properties.
     */
    public function __construct(array $description)
    {
        $this->id      = (string) ($description['id'] ?? 'undefined');
        $this->query   = (string) ($description['query'] ?? 'undefined');
        $this->success = (string) ($description['success'] ?? 'undefined');
        $this->error   = (string) ($description['error'] ?? 'undefined');
    }

    /**
     * Get descriptor properties.
     *
     * @return  array<string,string>    The properties
     */
    public function dump(): array
    {
        return [
            'id'      => $this->id,
            'query'   => $this->query,
            'success' => $this->success,
            'error'   => $this->error,
        ];
    }
}
