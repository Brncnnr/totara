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

namespace totara_playlist\local\image_processor;

use moodle_url;
use stored_file;
use totara_playlist\playlist;

/**
 * Contract for creating a playlist image processor.
 */
interface contract {
    /**
     * @param playlist $playlist
     * @param bool $squared If true, the square version of the image should be returned
     * @return stored_file|null
     */
    public function get_image_for_playlist(playlist $playlist, bool $squared = false): ?stored_file;

    /**
     * @param playlist $playlist
     * @return array
     */
    public function get_images_for_playlist(playlist $playlist): array;

    /**
     * @param stored_file $image
     * @return moodle_url
     */
    public function get_image_url(stored_file $image): moodle_url;

    /**
     * Grabs the attached resources, works out what we need to do &
     * generate two images. A landscape, and a square.
     *
     * @param playlist $playlist
     */
    public function update_playlist_images(playlist $playlist): void;

    /**
     * Perform a lookup to find images used to make the playlist image.
     *
     * @param int $playlist_id
     * @param int $max_images
     * @return array
     */
    public function get_source_images_for_playlist(int $playlist_id, int $max_images = 4): array;
}