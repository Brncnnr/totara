<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core;

use DirectoryIterator;
use RecursiveDirectoryIterator;

defined('MOODLE_INTERNAL') || die();

/**
 * Provide platform-independent path manipulation.
 */
final class path {
    /** Path separator string native to the OS. */
    const SEPARATOR = DIRECTORY_SEPARATOR;

    /**
     * Path delimiter string native to the OS, for the PATH environment variable.
     */
    const DELIMITER = DIRECTORY_SEPARATOR !== '/' ? ';' : ':';

    /** @var string */
    private $path = '';

    /** @var bool|null */
    private static $is_windows_flag = null;

    /**
     * Constructor.
     *
     * @param string|path $path
     * @param (string|path)[] ...$more
     */
    public function __construct($path, ...$more) {
        if ($path instanceof path) {
            $this->path = $path->path;
        } else {
            $this->path = self::slashify($path);
            $this->cleanup();
        }
        if (!empty($more)) {
            $this->path .= '/' . $this->join_internal($more);
            $this->cleanup();
        }
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return $this->to_string();
    }

    /**
     * Append path(s) at the end of the current path.
     *
     * @param string|path $path
     * @param (string|path)[] ...$more
     * @return self new object
     */
    public function join($path, ...$more): self {
        array_splice($more, 0, 0, [self::slashify($path)]);
        return new self($this, ...$more);
    }

    /**
     * Compare two paths lexicographically. On Unix systems, case matters.
     * This is useful in a comparison function:
     * e.g. usort($paths, function ($x, $y) { return $x->compare_to($y); })
     *
     * @param string|path $source
     * @return integer
     * * \< 0 if path1 is less than path2
     * * \> 0 if path1 is greater than path2
     * * 0 if they are equal
     */
    public function compare_to($source): int {
        $source = new self($source);
        return self::compare_internal($this->path, $source->path);
    }

    /**
     * Return the equality of two paths.
     *
     * @param string|path $source
     * @return boolean
     */
    public function equals($source): bool {
        $source = new self($source);
        return self::compare_internal($this->path, $source->path) === 0;
    }

    /**
     * Return the canonicalised absolute path.
     *
     * @return self new object
     */
    public function canonicalise(): self {
        $path = $this->to_native_string();
        $real_path = realpath($path);
        return new self($real_path);
    }

    /**
     * Return the path.
     *
     * @param boolean $native whether to use the native path format or not:
     * * set true to use path::SEPARATOR as a path separator
     * * set false to use slash on any OS
     * * some system call functions might require the native path format on Windows
     * @return string
     */
    public function out(bool $native = false): string {
        if ($native) {
            return $this->to_native_string();
        } else {
            return $this->to_string();
        }
    }

    /**
     * Return the path using slash as a path separator.
     * See out() for more information.
     *
     * @return string
     */
    public function to_string(): string {
        return $this->path;
    }

    /**
     * Return the path using path::SEPARATOR as a path separator.
     * See out() for more information.
     *
     * @return string
     */
    public function to_native_string(): string {
        return self::unslashify($this->path);
    }

    /**
     * Return the directory path.
     *
     * @return self|null the directory path or null
     * * if the current path is /kia/ora, /kia will be returned
     * * if the current path does not contain a path separator, null will be returned
     */
    public function get_parent(): ?self {
        $index = strrpos($this->path, '/');
        if ($index !== false) {
            $path = substr($this->path, 0, $index);
            return new self($path);
        }
        return null;
    }

    /**
     * Return the file name.
     *
     * @return string
     * * if the current path is /kia/ora, ora is returned
     * * if the current path does not contain a path separator, the whole string is returned
     */
    public function get_name(): string {
        $index = strrpos($this->path, '/');
        if ($index !== false) {
            return substr($this->path, $index + 1);
        }
        return $this->path;
    }

    /**
     * Return the file extension starting with the dot.
     *
     * @return string file extension including the dot e.g. '.png' or an empty string if the file path does not have an extension
     */
    public function get_extension(): string {
        $is_windows = self::is_windows();
        $index = false;
        for ($i = 0, $len = strlen($this->path); $i < $len; $i++) {
            $ch = substr($this->path, $i, 1);
            if ($ch === '.') {
                $index = $i;
            } else if ($ch === '/') {
                $index = false;
            } else if ($ch === ' ' && $is_windows) {
                // Windows does not accept the space character as a file extension.
                $index = false;
            }
        }
        if ($index !== false) {
            return substr($this->path, $index);
        }
        return '';
    }

    /**
     * Return whether the path is absolute or not.
     *
     * @return boolean
     */
    public function is_absolute(): bool {
        if (strncmp($this->path, '/', 1) === 0) {
            return true;
        }
        if (self::is_windows()) {
            return preg_match('#^[a-zA-Z]:/#', $this->path);
        }
        return false;
    }

    /**
     * Return a relative path from the given parent path.
     *
     * @param string|path $parent
     * @param boolean $null_if_not set true to return null if it's not relative. otherwise return itself.
     * @return path|null new object
     * * if the current path is /kia/ora/koutou and parent is /kia, ora/koutou is returned
     */
    public function get_relative($parent, bool $null_if_not = false): ?path {
        $parent = (new path($parent))->to_string() . '/';
        if (self::compare_internal($this->path, $parent, strlen($parent)) === 0) {
            return new path(substr($this->path, strlen($parent)));
        }
        if ($null_if_not) {
            return null;
        }
        return new path($this);
    }

    /**
     * Return whether the file or directory exists.
     *
     * @return boolean
     */
    public function exists(): bool {
        return file_exists($this->to_native_string());
    }

    /**
     * Return whether the path is a directory.
     *
     * @return boolean
     */
    public function is_directory(): bool {
        return is_dir($this->to_native_string());
    }

    /**
     * Return whether the path is a file.
     *
     * @return boolean
     */
    public function is_file(): bool {
        return is_file($this->to_native_string());
    }

    /**
     * Return whether a file exists and is readable.
     *
     * @return boolean
     */
    public function is_readable(): bool {
        return is_readable($this->to_native_string());
    }

    /**
     * Create a DirectoryIterator instance.
     *
     * @return DirectoryIterator
     */
    public function create_directory_iterator(): DirectoryIterator {
        return new DirectoryIterator($this->to_native_string());
    }

    /**
     * Create a RecursiveDirectoryIterator instance.
     *
     * @param integer|null $flags see RecursiveDirectoryIterator::__construct
     * @return RecursiveDirectoryIterator
     */
    public function create_recursive_directory_iterator(int $flags = null): RecursiveDirectoryIterator {
        if ($flags === null) {
            return new RecursiveDirectoryIterator($this->to_native_string());
        } else {
            return new RecursiveDirectoryIterator($this->to_native_string(), $flags);
        }
    }

    /**
     * Convert a path separator to slash.
     *
     * @param string $path
     * @return string
     * @internal
     */
    private static function slashify(string $path): string {
        if (self::SEPARATOR === '/') {
            return $path;
        }
        return str_replace(self::SEPARATOR, '/', $path);
    }

    /**
     * Convert slash to path::SEPARATOR.
     *
     * @param string $path
     * @return string
     * @internal
     */
    private static function unslashify(string $path): string {
        if (self::SEPARATOR === '/') {
            return $path;
        }
        return str_replace('/', self::SEPARATOR, $path);
    }

    /**
     * Join array for an environment variable.
     *
     * @param string[] ...$paths
     * @return string
     */
    public static function export(string ...$paths): string {
        return implode(self::DELIMITER, $paths);
    }

    /**
     * Perform the following clean-up operation to the current path:
     * * Multiple slashes are removed e.g. /kia//ora -> /kia/ora
     * * On Windows, the first double slashes are kept for a UNC file path
     * * The last path separator is removed e.g. /kia/ora/ -> /kia/ora
     *
     * @return self this object
     * @internal
     */
    private function cleanup(): self {
        // Remove multiple slashes.
        $double_slashes = strncmp($this->path, '//', 2) === 0;
        $path = preg_replace('#/+#', '/', $this->path);
        // ... except the beginning on Windows.
        if ($double_slashes && self::is_windows()) {
            $path = '/' . $path;
        }
        // Remove the path separator from the end.
        if (substr($path, -1) === '/') {
            $path = substr($path, 0, -1);
        }
        $this->path = $path;
        return $this;
    }

    /**
     * Return whether the OS is Windows or not.
     *
     * @return boolean
     */
    private static function is_windows(): bool {
        if (self::$is_windows_flag === null) {
            if (defined('PHP_OS_FAMILY')) {
                self::$is_windows_flag = PHP_OS_FAMILY === 'Windows';
            } else {
                self::$is_windows_flag = strncasecmp(PHP_OS, 'WIN', 3) === 0;
            }
        }
        return self::$is_windows_flag;
    }

    /**
     * Concat paths.
     *
     * @param (string|path)[] $paths
     * @return string
     * @internal
     */
    private static function join_internal(array $paths): string {
        $paths = array_map(function ($path) {
            return (new self($path))->to_string();
        }, $paths);
        return implode('/', $paths);
    }

    /**
     * Compare two file paths lexicographically.
     * WARNING: This function is not very secure. Also, case insensitive comparison is used on Windows.
     *
     * @param string $path1
     * @param string $path2
     * @param string $length if greater than 0, only the first n characters are compared
     * @return integer 0, \> 0 or \< 0. see compare_to() for more information
     * @internal
     */
    private static function compare_internal(string $path1, string $path2, int $length = -1): int {
        // NOTE: this code assumes non Windows === Unix system.
        if (self::is_windows()) {
            if ($length < 0) {
                $x = strcasecmp($path1, $path2);
            } else {
                $x = strncasecmp($path1, $path2, $length);
            }
        } else {
            if ($length < 0) {
                $x = strcmp($path1, $path2);
            } else {
                $x = strncmp($path1, $path2, $length);
            }
        }
        return (int)($x > 0) - (int)($x < 0);
    }
}
