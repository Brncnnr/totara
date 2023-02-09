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
 * @package engage_article
 */

namespace totara_playlist\totara_engage\link;

use core\format;
use moodle_url;
use totara_engage\link\destination_generator;
use totara_playlist\formatter\playlist_formatter;
use totara_playlist\playlist;

/**
 * Class playlist_destination
 *
 * @package totara_playlist\totara_engage\link
 */
final class playlist_destination extends destination_generator {
    /**
     * @var array
     */
    protected $auto_populate = ['id'];

    /**
     * @return string
     */
    public function label(): string {
        $playlist = playlist::from_id($this->attributes['id']);
        $playlist_formatter = new playlist_formatter($playlist);
        return get_string(
            'back_button',
            'totara_playlist',
            $playlist_formatter->format('name', format::FORMAT_PLAIN),
        );
    }

    /**
     * @return moodle_url
     */
    protected function base_url(): moodle_url {
        return new moodle_url('/totara/playlist/index.php');
    }

    /**
     * @param array $attributes
     * @param moodle_url $url
     */
    protected function add_custom_url_params(array $attributes, moodle_url $url): void {
        // Attach our library view if we want it
        if (!empty($attributes['library'])) {
            $url->param('libraryView', 1);
        }
    }
}