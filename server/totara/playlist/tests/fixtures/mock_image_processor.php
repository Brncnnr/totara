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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_playlist
 */

use totara_playlist\local\image_processor\contract as image_processor_contract;
use totara_playlist\playlist;

/**
 * A mock image processor. Will track what methods are called,
 * but not actually do anything. Used for unit testing only.
 */
class mock_playlist_image_processor implements image_processor_contract {
    /**
     * Keep track of the methods that have been invoked
     *
     * @var array
     */
    private $call_counts = [];

    /**
     * mock_playlist_image_processor constructor.
     */
    public function __construct() {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            throw new coding_exception('Attempting to use mock_playlist_image_processor outside of a unit test.');
        }
    }

    /**
     * Track the methods that have been invoked
     *
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments) {
        if (!isset($this->call_counts[$name])) {
            $this->reset($name);
        }
        $this->call_counts[$name]++;
    }

    /**
     * Return the number of times the method has been called
     *
     * @param string $name
     * @return int
     */
    public function count(string $name): int {
        return $this->call_counts[$name] ?? 0;
    }

    /**
     * Reset the counter back to 0
     *
     * @param string $name
     */
    public function reset(string $name): void {
        $this->call_counts[$name] = 0;
    }

    /**
     * @inheritDoc
     */
    public function update_playlist_images(playlist $playlist): void {
        $this->__call('update_playlist_images', func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function get_image_for_playlist(playlist $playlist, bool $squared = false): ?stored_file {
        // Shouldn't be called, so if it is something weird has happened
        throw new coding_exception('Unexpected call to get_image_for_playlist with mock image processor.');
    }

    /**
     * @inheritDoc
     */
    public function get_images_for_playlist(playlist $playlist): array {
        // Shouldn't be called, so if it is something weird has happened
        throw new coding_exception('Unexpected call to get_images_for_playlist with mock image processor.');
    }

    /**
     * @inheritDoc
     */
    public function get_image_url(stored_file $image): moodle_url {
        // Shouldn't be called, so if it is something weird has happened
        throw new coding_exception('Unexpected call to get_image_url with mock image processor.');
    }

    /**
     * @inheritDoc
     */
    public function get_source_images_for_playlist(int $playlist_id, int $max_images = 4): array {
        // Shouldn't be called, so if it is something weird has happened
        throw new coding_exception('Unexpected call to get_source_images_for_playlist with mock image processor.');
    }
}