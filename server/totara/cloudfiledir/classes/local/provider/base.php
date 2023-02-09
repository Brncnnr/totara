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
 * @package totara_cloudfiledir
 */

namespace totara_cloudfiledir\local\provider;

/**
 * Cloud file content storage provider base class.
 */
abstract class base {
    /**
     * Provider specific connection options.
     * @var array
     */
    protected $options;

    /**
     * Store identifier for debugging.
     * @var string
     */
    protected $idnumber;

    /**
     * Name of bucket if used.
     * @var string
     */
    protected $bucket;

    /**
     * Prefix - top level folder (not for production use).
     * @var string
     */
    protected $prefix;

    /**
     * base constructor.
     * @param array $options
     * @param string $idnumber
     * @param string $bucket
     * @param string $prefix ignored in behat and phpunit, not intended for production use!
     */
    public function __construct(array $options, string $idnumber, string $bucket, string $prefix) {
        global $DB;

        $this->options = $options;
        $this->bucket = $bucket;
        $this->prefix = $prefix;
        $this->idnumber = $idnumber;

        if (PHPUNIT_TEST) {
            $this->prefix = 'phpunit_' . $DB->get_prefix();
        } else if ((defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING)) {
            $this->prefix = 'behat_' . $DB->get_prefix();
        } else {
            $this->prefix = $prefix;
        }
    }

    /**
     * Is the provider ready to be connected?
     *
     * @return bool
     */
    abstract public function is_ready(): bool;

    /**
     * Test connection to provider.
     *
     * @return bool success
     */
    abstract public function test_connection(): bool;

    /**
     * Is the content with given contenthash available from this store?
     *
     * @param string $contenthash
     * @return bool
     */
    abstract public function is_content_available(string $contenthash): bool;

    /**
     * Upload content file to cloud store.
     *
     * @param string $contenthash
     * @param string $filepath
     * @return bool success
     */
    abstract public function upload_content(string $contenthash, string $filepath): bool;

    /**
     * Upload content file to cloud store from file handle.
     *
     * @param string $contenthash
     * @param resource $handle
     * @param int $filesize
     * @return bool success
     */
    abstract public function upload_content_stream(string $contenthash, $handle, int $filesize): bool;

    /**
     * Download content file from store.
     *
     * @param string $contenthash
     * @param string $filepath
     * @return bool success, false if file does not exist or on error
     */
    abstract public function download_content(string $contenthash, string $filepath): bool;

    /**
     * Create temporary download link.
     *
     * @param string $contenthash
     * @param int $lifetime minimum lifetime in seconds
     * @return string|null file download URL
     */
    public function create_download_link(string $contenthash, int $lifetime = 3600): ?string {
        return null;
    }

    /**
     * Delete content file from store.
     *
     * @param string $contenthash
     * @return bool success
     */
    abstract public function delete_content(string $contenthash): bool;

    /**
     * List all contents in the cloud bucket.
     *
     * @return \Iterator|null returning content hashes null means error
     */
    abstract public function list_contents(): ?\Iterator;

    /**
     * Delete all files from bucket, this is intended for tests only!
     *
     * @internal
     * @return bool success
     */
    abstract public function clear_test_bucket(): bool;

    /**
     * Log problems to let admins know what is going on.
     *
     * @param \Throwable $ex
     * @param string $debuginfo
     */
    protected function log_exception(\Throwable $ex, string $debuginfo): void {
        $backtrace = $ex->getTrace();
        $from = format_backtrace($backtrace, true);
        $details = $ex->getMessage();
        error_log("cloudfiledir store error [{$this->idnumber}]: $debuginfo\n$details\n in \n $from");
    }

    /**
     * Object name used in cloud bucket.
     *
     * @param string $contenthash
     * @return string file path and name in the object store
     */
    protected function get_object_name(string $contenthash): string {
        $l1 = $contenthash[0].$contenthash[1];
        $l2 = $contenthash[2].$contenthash[3];
        if ($this->prefix === '') {
            return "$l1/$l2/$contenthash";
        } else {
            return "$this->prefix/$l1/$l2/$contenthash";
        }
    }

    /**
     * Creates a closure for parsing of contenthash from object name,
     * it is the opposite of get_object_name().
     *
     * @return callable
     */
    protected function get_object_name_parser(): callable {
        $prefix = $this->prefix;
        return function (string $name) use ($prefix) {
            if ($prefix === '') {
                $regex = '|^([0-9a-f][0-9a-f])/([0-9a-f][0-9a-f])/([0-9a-f]{40})$|';
            } else {
                $regex = '|^' . preg_quote($prefix, '|') . '/([0-9a-f][0-9a-f])/([0-9a-f][0-9a-f])/([0-9a-f]{40})$|';
            }
            if (!preg_match($regex, $name, $matches)) {
                return false;
            }
            if (substr($matches[3], 0, 2) !== $matches[1] || substr($matches[3], 2, 2) !== $matches[2]) {
                return false;
            }
            return $matches[3];
        };
    }
}

