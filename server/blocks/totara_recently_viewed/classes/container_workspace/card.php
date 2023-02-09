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

namespace block_totara_recently_viewed\container_workspace;

use block_totara_recently_viewed\card as base_card;
use container_workspace\loader\member\loader;
use container_workspace\workspace;
use moodle_url;
use theme_config;

/**
 * Workspace card for the recently viewed block
 *
 */
class card implements base_card {
    /**
     * @var workspace
     */
    private $workspace;

    /**
     * @param int $id
     * @return base_card
     */
    public static function from_id(int $id): base_card {
        $card = new static();
        $card->workspace = workspace::from_id($id);

        return $card;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->workspace->get_id();
    }

    /**
     * @param bool $is_dashboard
     * @return moodle_url
     */
    public function get_url(bool $is_dashboard): moodle_url {
        return $this->workspace->get_view_url();
    }

    /**
     * @return string|null
     */
    public function get_title(): ?string {
        return format_string($this->workspace->get_name());
    }

    /**
     * @return string|null
     */
    public function get_subtitle(): ?string {
        $members = loader::count_members($this->workspace->get_id());
        $members_string = get_string('members', 'block_totara_recently_viewed', $members);
        $member_string = get_string('member', 'block_totara_recently_viewed', $members);

        return $members === 1 ? $member_string : $members_string;
    }

    /**
     * @return int|null
     */
    public function get_user_id(): ?int {
        return $this->workspace->get_user_id();
    }

    /**
     * @param bool $tile_view
     * @param theme_config $theme_config
     * @return moodle_url|null
     */
    public function get_image(bool $tile_view, theme_config $theme_config): ?\moodle_url {
        global $OUTPUT;
        $image = $this->workspace->get_image($theme_config);
        if (!$image) {
            // No image, use the default
            return $OUTPUT->image_url('default_space', 'engage_workspace');
        }

        return $image;
    }

    /**
     * @return array
     */
    public function get_extra_data(): array {
        return [
            'members' => $this->get_subtitle(),
        ];
    }

    /**
     * @return bool
     */
    public function is_library_card(): bool {
        return false;
    }
}