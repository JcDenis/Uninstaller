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

use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;

/**
 * Cleaner helper for files structure.
 */
trait TraitCleanerDir
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

    protected static function getDirs(string|array $roots): array
    {
        if (!is_array($roots)) {
            $roots = [$roots];
        }
        $rs = [];
        $i  = 0;
        foreach ($roots as $root) {
            $dirs = Files::scanDir($root);
            foreach ($dirs as $k) {
                if (in_array($k, self::EXCLUDED) || !is_dir($root . DIRECTORY_SEPARATOR . $k)) {
                    continue;
                }
                $rs[$i]['key']   = $k;
                $rs[$i]['value'] = count(self::scanDir($root . DIRECTORY_SEPARATOR . $k));
                $i++;
            }
        }

        return $rs;
    }

    protected static function delDir(string|array $roots, string $folder, bool $delfolder = true): bool
    {
        if (strpos($folder, DIRECTORY_SEPARATOR)) {
            return false;
        }
        if (!is_array($roots)) {
            $roots = [$roots];
        }
        foreach ($roots as $root) {
            if (file_exists($root . DIRECTORY_SEPARATOR . $folder)) {
                return self::delTree($root . DIRECTORY_SEPARATOR . $folder, $delfolder);
            }
        }

        return false;
    }

    protected static function scanDir(string $path, string $dir = '', array $res = []): array
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
                $res[] = $file;
                $res   = self::scanDir($path . DIRECTORY_SEPARATOR . $file, $dir . DIRECTORY_SEPARATOR . $file, $res);
            } else {
                $res[] = empty($dir) ? $file : $dir . DIRECTORY_SEPARATOR . $file;
            }
        }

        return $res;
    }

    protected static function delTree(string $dir, bool $delroot = true): bool
    {
        if (!is_dir($dir) || !is_readable($dir)) {
            return false;
        }
        if (substr($dir, -1) != DIRECTORY_SEPARATOR) {
            $dir .= DIRECTORY_SEPARATOR;
        }
        if (($d = @dir($dir)) === false) {
            return false;
        }
        while (($entryname = $d->read()) !== false) {
            if ($entryname != '.' && $entryname != '..') {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $entryname)) {
                    if (!self::delTree($dir . DIRECTORY_SEPARATOR . $entryname)) {
                        return false;
                    }
                } else {
                    if (!@unlink($dir . DIRECTORY_SEPARATOR . $entryname)) {
                        return false;
                    }
                }
            }
        }
        $d->close();

        if ($delroot) {
            return @rmdir($dir);
        }

        return true;
    }
}
