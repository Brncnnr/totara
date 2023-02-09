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
namespace block_totara_recently_viewed;

use theme_config;

/**
 * A card that's rendered by this block must implement this interface and provide the defined data.
 * Any additional data can be provided in
 *
 * @package block_totara_recently_viewed\card
 */
interface card {
    /**
     * Create a new instance of this element
     *
     * @param int $id
     * @return card
     */
    public static function from_id(int $id): card;

    /**
     * The instance id - this differs depending on the specific card implementation
     *
     * @return int
     */
    public function get_id(): int;

    /**
     * Clickable URL to view more details on the card
     *
     * @param bool $is_dashboard
     * @return \moodle_url
     */
    public function get_url(bool $is_dashboard): \moodle_url;

    /**
     * Title of the card
     *
     * @return string|null
     */
    public function get_title(): ?string;

    /**
     * Subtitle fo the card
     *
     * @return string|null
     */
    public function get_subtitle(): ?string;

    /**
     * Creator/Author id of the item
     *
     * @return int|null
     */
    public function get_user_id(): ?int;

    /**
     * Image URL of the card. Expected to return a default image if none exists.
     * If null is returned, there is no image on this card (eg survey).
     *
     * @param bool $tile_view If true, the image should be in the rectangular ratio
     * @param theme_config $theme_config
     * @return \moodle_url|null
     */
    public function get_image(bool $tile_view, theme_config $theme_config): ?\moodle_url;

    /**
     * Return any additional data to be passed directly to the card template.
     *
     * @return array
     */
    public function get_extra_data(): array;

    /**
     * Indicate if this target page is an engage library page or not
     *
     * @return bool
     */
    public function is_library_card(): bool;
}