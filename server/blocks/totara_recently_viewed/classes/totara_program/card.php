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

namespace block_totara_recently_viewed\totara_program;

use block_totara_recently_viewed\card as base_card;
use moodle_url;
use theme_config;

/**
 * Program/Certification card for the recently viewed block
 *
 */
class card implements base_card {
    /**
     * @var \program
     */
    private $program;

    /**
     * @param int $id
     * @return base_card
     */
    public static function from_id(int $id): base_card {
        $card = new static();
        $card->program = new \program($id);

        return $card;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->program->id;
    }

    /**
     * @param bool $is_dashboard
     * @return moodle_url
     */
    public function get_url(bool $is_dashboard): moodle_url {
        return new moodle_url('/totara/program/view.php', ['id' => $this->get_id()]);
    }

    /**
     * @return string|null
     */
    public function get_title(): ?string {
        return format_string($this->program->fullname);
    }

    /**
     * @return string|null
     */
    public function get_subtitle(): ?string {
        return null;
    }

    /**
     * @return int|null
     */
    public function get_user_id(): ?int {
        return null;
    }

    /**
     * @param bool $tile_view
     * @param theme_config $theme_config
     * @return moodle_url|null
     */
    public function get_image(bool $tile_view, theme_config $theme_config): ?\moodle_url {
        $image = $this->program->get_image();

        return $image ? new moodle_url($image) : null;
    }

    /**
     * @return array
     */
    public function get_extra_data(): array {
        global $CFG;
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        $show_progress = true;
        $percent = round($this->program->get_progress($this->get_user_id()));

        return [
            'is_certification' => $this->program->is_certif(),
            'show_progress' => $show_progress,
            'progress' => $percent . '%',
            'progress_small' => $percent < 30,
        ];
    }

    /**
     * @return bool
     */
    public function is_library_card(): bool {
        return false;
    }
}