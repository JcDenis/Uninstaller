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

namespace Dotclear\Plugin\Uninstaller\Helper;

use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;

/**
 * Cleaner helper for files structure.
 */
trait DirTrait
{
    /** @var    array<int,string>   The excluded files */
    public const EXCLUDED = [
        '.',
        '..',
        '__MACOSX',
        '.svn',
        '.hg',
        '.git',
        'CVS',
        '.directory',
        '.DS_Store',
        'Thumbs.db',
        '_disabled',
    ];

    /**
     * Get path structure.
     *
     * @param   string  $paths  The directories paths to scan
     *
     * @return  array<string,int>    The path structure
     */
    protected static function getDirs(string $paths): array
    {
        $paths = explode(PATH_SEPARATOR, $paths);

        $stack = [];
        foreach ($paths as $path) {
            $dirs = Files::scanDir($path);
            foreach ($dirs as $k) {
                if (!is_string($k) || in_array($k, self::EXCLUDED) || !is_dir($path . DIRECTORY_SEPARATOR . $k)) {
                    continue;
                }
                $stack[$k] = count(self::scanDir($path . DIRECTORY_SEPARATOR . $k));
            }
        }

        return $stack;
    }

    /**
     * Delete path structure.
     *
     * @param   string  $paths      The directories paths to scan
     * @param   string  $folder     The folder in path
     * @param   bool    $delete     Also delete folder itself
     *
     * @return  bool    True on success
     */
    protected static function delDir(string $paths, string $folder, bool $delete = true): bool
    {
        $paths = explode(PATH_SEPARATOR, $paths);

        if (strpos($folder, DIRECTORY_SEPARATOR)) {
            return false;
        }

        foreach ($paths as $path) {
            if (file_exists($path . DIRECTORY_SEPARATOR . $folder)) {
                return self::delTree($path . DIRECTORY_SEPARATOR . $folder, $delete);
            }
        }

        return false;
    }

    /**
     * Scan recursively a directory.
     *
     * @param   string              $path   The directory path to scan
     * @param   string              $dir    The current directory
     * @param   array<int,string>   $stack  The paths stack
     *
     * @return  array<int,string>   The paths stack
     */
    private static function scanDir(string $path, string $dir = '', array $stack = []): array
    {
        $path = Path::real($path);
        if ($path === false || !is_dir($path) || !is_readable($path)) {
            return [];
        }
        $files = Files::scandir($path);

        foreach ($files as $file) {
            if (in_array($file, self::EXCLUDED)) {
                continue;
            }
            if (is_dir($path . DIRECTORY_SEPARATOR . $file)) {
                $stack[] = $file;
                $stack   = self::scanDir($path . DIRECTORY_SEPARATOR . $file, $dir . DIRECTORY_SEPARATOR . $file, $stack);
            } else {
                $stack[] = empty($dir) ? $file : $dir . DIRECTORY_SEPARATOR . $file;
            }
        }

        return $stack;
    }

    /**
     * Delete path tree.
     *
     * @param   string  $path       The directory path
     * @param   bool    $delete     Also delete the directory path
     *
     * @return  bool    True on success
     */
    private static function delTree(string $path, bool $delete = true): bool
    {
        if (!is_dir($path) || !is_readable($path)) {
            return false;
        }
        if (substr($path, -1) != DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }
        if (($d = @dir($path)) === false) {
            return false;
        }
        while (($entryname = $d->read()) !== false) {
            if ($entryname != '.' && $entryname != '..') {
                if (is_dir($path . DIRECTORY_SEPARATOR . $entryname)) {
                    if (!self::delTree($path . DIRECTORY_SEPARATOR . $entryname)) {
                        return false;
                    }
                } else {
                    if (!@unlink($path . DIRECTORY_SEPARATOR . $entryname)) {
                        return false;
                    }
                }
            }
        }
        $d->close();

        if ($delete) {
            return @rmdir($path);
        }

        return true;
    }
}
