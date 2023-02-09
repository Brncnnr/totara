<?php
/*
 *  This file is part of Totara TXP
 *
 *  Copyright (C) 2022 onwards Totara Learning Solutions LTD
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @author Simon Coggins <simon.coggins@totaralearning.com>
 *
 */

namespace totara_api\cli;

class metadata_helper {
    /** @var array $metadata_array */
    private array $metadata_array = [];

    /**
     * Add a file to the list of metadata files after some basic validation.
     *
     * @param string $filename Name of file to add.
     * @return bool True if the file was added.
     */
    public function add_file(string $filename): bool {
        if (!is_readable($filename)) {
            return false;
        }
        $contents = file_get_contents($filename);
        if ($contents === false) {
            return false;
        }
        return $this->add_file_contents($contents);
    }

    /**
     * Merge the specified file contents with existing stored metadata.
     * Merge is done via a recursive strategy via key comparison.
     *
     * @param string $contents Contents of the file to merge. Should be a json string.
     * @return bool True if the file was merged successfully, false otherwise.
     */
    private function add_file_contents(string $contents): bool {
        try {
            $json = json_decode($contents, true, 512, JSON_INVALID_UTF8_IGNORE|JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            return false;
        }
        $this->merge_metadata($json);
        return true;
    }

    /**
     * Merge some structured associative array data with the existing stored metadata.
     *
     * @param array $metadata Metadata as an associative array of data.
     * @return void
     */
    private function merge_metadata(array $metadata) {
        $this->metadata_array = array_merge_recursive($this->metadata_array, $metadata);
    }

    /**
     * Return an associative array of currently stored metadata.
     *
     * @return array
     */
    public function get_metadata_as_array(): array {
        return $this->metadata_array;
    }

    /**
     * Return the currently stored metadata as a json
     * @return string
     */
    public function get_metadata_as_json(): string {
        return json_encode($this->metadata_array, JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);
    }
}