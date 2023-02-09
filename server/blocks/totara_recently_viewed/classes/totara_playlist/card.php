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
 * @package block_totara_recently_viewed
 */

namespace block_totara_recently_viewed\totara_playlist;

use block_totara_recently_viewed\card as base_card;
use moodle_url;
use theme_config;
use totara_engage\link\builder;
use totara_engage\rating\rating_manager;
use totara_playlist\entity\playlist_resource;
use totara_playlist\local\image_processor;
use totara_playlist\playlist;
use totara_playlist\repository\playlist_resource_repository;

/**
 * Playlist card for the recently viewed block
 *
 */
class card implements base_card {
    /**
     * @var playlist
     */
    private $playlist;

    /**
     * @var \stdClass|null
     */
    private $user;

    /**
     * @param int $id
     * @return base_card
     */
    public static function from_id(int $id): base_card {
        $card = new static();
        $card->playlist = playlist::from_id($id);

        return $card;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->playlist->get_id();
    }

    /**
     * @param bool $is_dashboard
     * @return moodle_url
     */
    public function get_url(bool $is_dashboard): moodle_url {
        return builder::to(playlist::get_resource_type(), ['id' => $this->get_id()])
            ->from('block_totara_recently_viewed', ['dashboard' => $is_dashboard])
            ->url();
    }

    /**
     * @return string|null
     */
    public function get_title(): ?string {
        return format_string($this->playlist->get_name());
    }

    /**
     * @return \stdClass|null
     */
    private function get_user(): ?\stdClass {
        if (!$this->user && $this->get_user_id()) {
            $this->user = \core_user::get_user($this->get_user_id(),
                'id, firstname, middlename, lastname, firstnamephonetic, lastnamephonetic, alternatename'
            );
            $this->user->fullname = fullname($this->user);
        }

        return $this->user;
    }

    /**
     * @return string|null
     */
    public function get_subtitle(): ?string {
        $user = $this->get_user();
        $username = $user ? $user->fullname : '';

        return get_string('playlist_by', 'block_totara_recently_viewed', $username);
    }

    /**
     * @return int|null
     */
    public function get_user_id(): ?int {
        return $this->playlist->get_userid();
    }

    /**
     * @param bool $tile_view
     * @param theme_config $theme_config
     * @return moodle_url|null
     */
    public function get_image(bool $tile_view, theme_config $theme_config): ?\moodle_url {
        global $OUTPUT;
        $processor = image_processor::make();
        $file_image = $processor->get_image_for_playlist($this->playlist, !$tile_view);

        if (!$file_image) {
            // No image, use the default
            return $OUTPUT->image_url('default_collection' . (!$tile_view ? '_square' : ''), 'totara_playlist');
        }

        $image = $processor->get_image_url($file_image);
        $image->param('hash', $file_image->get_contenthash());

        return $image;
    }

    /**
     * @return array
     */
    public function get_extra_data(): array {
        /** @var playlist_resource_repository $repo */
        $repo = playlist_resource::repository();
        $rating = rating_manager::instance($this->get_id(), 'totara_playlist', 'playlist');

        // To make it simple for mustache, we'll create an array of the offset % for the 5 stars
        $star_rating = $rating->avg();
        $stars = [];
        for ($i = 0; $i < 5; $i++) {
            $stars[$i] = [
                'star' => '0%',
                'key' => 'pl_' . $this->get_id() . '_' . $i,
            ];

            if ($star_rating >= 1) {
                $stars[$i]['star'] = '100%';
                $star_rating--;
                continue;
            }
            if ($star_rating > 0) {
                $stars[$i]['star'] = '50%';
                $star_rating--;
                continue;
            }
        }

        return [
            'stars' => $stars,
            'image_overlay' => $repo->get_total_of_resources($this->get_id()),
        ];
    }

    /**
     * @return bool
     */
    public function is_library_card(): bool {
        return true;
    }
}