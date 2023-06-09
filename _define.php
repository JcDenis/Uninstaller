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
if (!defined('DC_RC_PATH')) {
    return null;
}

$this->registerModule(
    'Uninstaller',
    'Uninstall cleanly plugins and themes',
    'Jean-Christian Denis and Contributors',
    '0.9.6',
    [
        'requires' => [
            ['php', '8.1'],
            ['core', '2.27'],
        ],
        'permissions' => null,
        'type'        => 'plugin',
        'settings'    => [
            'self' => false,
        ],
        'support'    => 'https://github.com/JcDenis/' . basename(__DIR__),
        'details'    => 'https://plugins.dotaddict.org/dc2/details/' . basename(__DIR__),
        'repository' => 'https://raw.githubusercontent.com/JcDenis/' . basename(__DIR__) . '/master/dcstore.xml',
    ]
);
