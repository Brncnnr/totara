<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_recommender
 */

namespace ml_recommender\local\export;

use ml_recommender\local\exporter;
use ml_recommender\local\flag;
use ml_service\auth\token_manager;

/**
 * Helper class to validate a download request for
 * one of the recommender content files.
 */
class content_downloader {
    /**
     * @var string
     */
    private $file_area;

    /**
     * @var array
     */
    private $args;

    /**
     * @var string|null
     */
    private $data_path;

    /**
     * @param string $file_area
     * @param array $args
     * @param string|null $data_path
     */
    private function __construct(string $file_area, array $args, ?string $data_path) {
        $this->file_area = $file_area;
        $this->args = $args;
        $this->data_path = $data_path;
    }

    /**
     * @param string $file_area
     * @param array $args
     * @param string|null $data_path
     * @return content_downloader
     */
    public static function make(string $file_area, array $args, ?string $data_path = null): content_downloader {
        return new static($file_area, $args, $data_path);
    }

    /**
     * Validate against the request, to see if a valid token is provided
     * and if the request is formed correctly.
     *
     * @return bool
     */
    public function is_request_allowed(): bool {
        // Simple validation of the args, we know what we expect to see.
        if ($this->file_area !== 'export' || 1 !== count($this->args)) {
            return false;
        }

        // We expect to see both a time & a token passed in via the headers
        [$request_time, $request_token] = token_manager::extract_request_time_token();
        if (empty($request_time) || empty($request_token)) {
            return false;
        }

        // The provided token must be acceptable (and recent)
        return token_manager::valid_token($request_time, $request_token);
    }

    /**
     * Find the matching requested downloadable file path.
     * Returns a null if nothing could be found.
     *
     * This does not confirm the file actually exists, only that it's a valid option
     * for downloading.
     *
     * @return string|null
     */
    public function find_matching_file(): ?string {
        $request_filename = current($this->args);
        if (empty($request_filename)) {
            return null;
        }

        // We know the list of files available for download, so check if the requested one is in that list.
        // The provided filename is only used for this discovery, nothing else.
        $known_files = exporter::get_list_of_files($this->data_path);
        $found_file = null;
        foreach ($known_files as $known_file) {
            if (basename($known_file['path']) === $request_filename) {
                $found_file = $known_file;
                break;
            }
        }
        if (empty($found_file)) {
            return null;
        }

        return $found_file['path'];
    }

    /**
     * Check if the file exists or an export has occurred (and isn't currently running).
     *
     * @param string $file_path
     * @return bool
     */
    public function is_file_available(string $file_path): bool {
        if (!flag::is_complete(flag::EXPORT) || !file_exists($file_path)) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if this is a request for the tenants file without multitenancy.
     *
     * @param string $file_path
     * @return bool
     */
    public function is_tenants_request_without_multitenancy(string $file_path): bool {
        global $CFG;
        if ($CFG->tenantsenabled) {
            return false;
        }

        return basename($file_path) === 'tenants.csv';
    }
}