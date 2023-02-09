<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_extfiledir
 */

namespace totara_extfiledir\local;

/**
 * External file content storage.
 */
final class store {
    /** @var string name of the store */
    private $idnumber;
    /** @var string store purpose description in markdown format */
    private $description = '';
    /** @var string directory path */
    private $filedir;
    /** @var bool is store active? */
    private $active = false;
    /** @var bool true means add fields to store */
    private $add = false;
    /** @var bool true means delete files from store */
    private $delete = false;
    /** @var bool true means use for content restore */
    private $restore = false;
    /** @var int permissions for newly created directories */
    private $directorypermissions;
    /** @var int permissions for new files in store */
    private $filepermissions;

    /**
     * Store constructor.
     *
     * @param array $config
     */
    private function __construct(array $config) {
        global $CFG;

        $this->idnumber = $config['idnumber'];
        if (preg_match('#[^a-zA-Z0-9_]#', $this->idnumber)) {
            debugging('Invalid character detected in store idnumber: ' . $this->idnumber, DEBUG_ALL);
        }

        $this->filedir = $config['filedir'];

        if (isset($config['description'])) {
            $this->description = $config['description'];
        }

        if (isset($config['active'])) {
            $this->active = (bool)$config['active'];
        }

        if ($this->active && !is_dir($this->filedir)) {
            // Disable if directory does not exist to prevent errors.
            $this->active = false;
            error_log("External filedir storage config '{$this->idnumber}' contains invalid directory");
        }

        if (isset($config['add'])) {
            $this->add = (bool)$config['add'];
        }
        if (isset($config['delete'])) {
            $this->delete = (bool)$config['delete'];
        }
        if (isset($config['restore'])) {
            $this->restore = (bool)$config['restore'];
        }
        if (!empty($config['directorypermissions'])) {
            $this->directorypermissions = (int)$config['directorypermissions'];
        } else {
            $this->directorypermissions = (int)$CFG->directorypermissions;
        }
        if (!empty($config['filepermissions'])) {
            $this->filepermissions = (int)$config['filepermissions'];
        } else {
            $this->filepermissions = ($CFG->directorypermissions & 0666);
        }
    }

    /**
     * Returns short store name.
     *
     * @return string
     */
    public function get_idnumber(): string {
        return $this->idnumber;
    }

    /**
     * Returns short description in markdown format.
     *
     * @return string
     */
    public function get_description(): string {
        return $this->description;
    }

    /**
     * Is store active?
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->active;
    }

    /**
     * Returns store path.
     *
     * @return string
     */
    public function get_filedir(): string {
        return $this->filedir;
    }

    /**
     * New directory permissions.
     *
     * @return int
     */
    public function get_directorypermissions(): int {
        return $this->directorypermissions;
    }

    /**
     * New file permissions.
     *
     * @return int
     */
    public function get_filepermissions(): int {
        return $this->filepermissions;
    }

    /**
     * Is adding of contents to store allowed?
     *
     * @return bool
     */
    public function add_enabled(): bool {
        return $this->add;
    }

    /**
     * Is deleting of contents from store allowed?
     *
     * @return bool
     */
    public function delete_enabled(): bool {
        return $this->delete;
    }

    /**
     * Is store used for content restore?
     *
     * @return bool
     */
    public function restore_enabled(): bool {
        return $this->restore;
    }

    /**
     * Add content file to store.
     *
     * @param string $contenthash
     * @param \Closure $filewriter writes content to specified filepath
     * @return bool success
     */
    public function write_content(string $contenthash, \Closure $filewriter): bool {
        if (!$this->active || !$this->add) {
            return false;
        }

        $target = $this->get_filepath($contenthash);
        clearstatcache(true); // Better clear the caches because the filedir might be accessed concurrently.
        if (file_exists($target)) {
            return true;
        }

        $targetdir = dirname($target);
        if (!file_exists($targetdir)) {
            mkdir($targetdir, $this->directorypermissions, true);
        }
        if (!is_writable($targetdir)) {
            error_log("External filedir store '{$this->idnumber}' is not writable: " . $contenthash);
            return false;
        }

        try {
            $temptarget = $target . '.tmp';
            $success = $filewriter($temptarget);
            if (!$success || !file_exists($temptarget)) {
                error_log("Failed to copy content file '$contenthash' to external filedir store '{$this->idnumber}'");
                return false;
            }
            if (sha1_file($temptarget) !== $contenthash) {
                // Out of disk space on NFS volume?
                unlink($temptarget);
                error_log("Failed to copy content file '$contenthash' to external filedir store '{$this->idnumber}'");
                return false;
            }
            rename($temptarget, $target);
            chmod($target, $this->filepermissions); // Fix permissions if needed.
            @unlink($temptarget); // Just in case anything fails in a weird way.
            return true;
        } catch (\Throwable $e) {
            error_log('Exception while writing file content to external store: ' . $contenthash . "\n" . $e->getMessage());
            return false;
        }
    }

    /**
     * Read content from file store.
     *
     * @param string $contenthash
     * @param \Closure $filereader reads content from file parameter
     * @return bool success
     */
    public function read_content(string $contenthash, \Closure $filereader): bool {
        if (!$this->active) {
            return false;
        }

        $filepath = $this->get_filepath($contenthash);
        clearstatcache(true); // Better clear the caches because the filedir might be accessed concurrently.
        if (!file_exists($filepath)) {
            return false;
        }

        try {
            return $filereader($filepath);
        } catch (\Throwable $e) {
            error_log('Exception while reading external store file content: ' . $contenthash . "\n" . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete content from file store.
     *
     * @param string $contenthash
     * @return bool
     */
    public function delete_content(string $contenthash): bool {
        if (!$this->active || !$this->delete) {
            return false;
        }

        $filepath = $this->get_filepath($contenthash);
        clearstatcache(true); // Better clear the caches because the filedir might be accessed concurrently.
        if (!file_exists($filepath)) {
            return true;
        }

        unlink($filepath);
        if (file_exists($filepath)) {
            error_log('Error deleting external store file content: ' . $contenthash);
            return false;
        }

        return true;
    }

    /**
     * Is the content available in store?
     *
     * @param string $contenthash
     * @param bool $clearstatcache
     * @return bool
     */
    public function is_content_available(string $contenthash, $clearstatcache = true): bool {
        if (!$this->active) {
            return false;
        }

        $filepath = $this->get_filepath($contenthash);
        if ($clearstatcache) {
            clearstatcache(true, $filepath);
        }
        return file_exists($filepath);
    }

    /**
     * Returns full content file path.
     *
     * @param string $contenthash
     * @return string
     */
    protected function get_filepath(string $contenthash): string {
        return $this->filedir . '/' . self::get_relative_filepath($contenthash);
    }

    /**
     * Returns standard relative path to content hash file in external filedir.
     *
     * @param string $contenthash
     * @return string
     */
    public static function get_relative_filepath(string $contenthash): string {
        $l1 = $contenthash[0].$contenthash[1];
        $l2 = $contenthash[2].$contenthash[3];
        return "$l1/$l2/$contenthash";
    }

    /**
     * Returns list of all available stores.
     *
     * @return self[]
     */
    final public static function get_stores(): array {
        global $CFG;

        static $stores = null;

        if (PHPUNIT_TEST) {
            $stores = null;
        }

        if ($stores === null) {
            $stores = [];

            if (!empty($CFG->totara_extfiledir_stores)) {
                foreach ($CFG->totara_extfiledir_stores as $config) {
                    if (!isset($config['idnumber']) || $config['idnumber'] === '') {
                        // Invalid entry.
                        continue;
                    }
                    if (isset($stores[$config['idnumber']])) {
                        // Duplicate entry.
                        continue;
                    }

                    if (!isset($config['filedir'])) {
                        // Invalid entry.
                        continue;
                    }
                    $config['filedir'] = rtrim($config['filedir'], '/\\');
                    if ($config['filedir'] === '') {
                        // Invalid entry.
                        continue;
                    }

                    $stores[$config['idnumber']] = new self($config);
                }
            }
        }

        return $stores;
    }
}
