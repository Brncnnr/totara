<?php
/**
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 * @group paginator
 */

namespace core\pagination;

use coding_exception;

/**
 * This contains all common methods and properties which is true for all cursors.
 * Individual cursors can extend and override the validation for example and can add
 * their own additional cursor properties.
 */
abstract class base_cursor {

    /**
     * Extracted cursor
     *
     * @var array
     */
    protected $cursor = [
        'limit' => null
    ];

    /**
     * @param array|null $cursor pass null to create empty default cursor
     */
    public function __construct(array $cursor = null) {
        if (!is_null($cursor)) {
            $this->validate($cursor);
            $this->cursor = $cursor;
        }
    }

    /**
     * Creates a new cursor instance based on the data passed to it
     *
     * @param array|null $cursor
     * @return base_cursor|$this
     */
    public static function create(array $cursor = null): self {
        return new static($cursor);
    }

    /**
     * Takes the given encoded cursor string and returns an instance of the cursor
     * object to continue working with
     *
     * @param string $cursor
     * @return base_cursor|$this
     */
    public static function decode(string $cursor): self {
        $cursor = static::extract($cursor);
        return new static($cursor);
    }

    /**
     * Extract the cursor data from the encoded version, will throw exceptions
     * if the structure is not the expected base64 encoded json string
     *
     * @param string $cursor
     * @return array
     */
    protected static function extract(string $cursor): array {
        $extracted_cursor = base64_decode($cursor, true);
        if ($extracted_cursor === false || !is_string($extracted_cursor)) {
            throw new coding_exception('Invalid cursor given, expected base64 encoded string');
        }

        $extracted_cursor = json_decode($extracted_cursor, true);
        if (!is_array($extracted_cursor)) {
            throw new coding_exception('Invalid cursor given, expected array encoded as json and base64.');
        }

        return $extracted_cursor;
    }

    /**
     * Validate if the cursor has the required information
     *
     * @param array $cursor
     * @return void
     */
    protected function validate(array $cursor): void {
        global $CFG;

        if (empty($cursor)) {
            throw new coding_exception('Empty cursor given, please provide a cursor with at least one value.');
        }

        if (!array_key_exists('limit', $cursor)) {
            throw new coding_exception('You must provide a limit within your cursor.');
        }

        if (isset($CFG->graphqlcursorlimitmax) && $cursor['limit'] > $CFG->graphqlcursorlimitmax) {
            throw new coding_exception('Cursor limit has been set above maximum allowed');
        }
    }

    /**
     * Encodes the cursor as base64 encoded json string
     *
     * @return string
     */
    public function encode(): string {
        return base64_encode(json_encode($this->cursor));
    }

    /**
     * The current unencoded cursor
     * @return array
     */
    public function get_cursor(): array {
        return $this->cursor;
    }

    /**
     * Returns the current limit defined by this cursor
     *
     * @return int|null
     */
    public function get_limit(): ?int {
        return $this->cursor['limit'];
    }

    /**
     * Sets the limit for this cursor
     *
     * @param int|null $limit
     * @return $this
     */
    public function set_limit(?int $limit) {
        $this->cursor['limit'] = $limit;
        return $this;
    }

}
