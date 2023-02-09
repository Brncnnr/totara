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

namespace block_totara_recently_viewed\engage_survey;

use block_totara_recently_viewed\card as base_card;
use engage_survey\totara_engage\resource\survey;
use moodle_url;
use theme_config;
use totara_engage\link\builder;

/**
 * Survey card for the recently viewed block
 *
 */
class card implements base_card {
    /**
     * @var survey
     */
    private $survey;

    /**
     * @param int $id
     * @return base_card
     */
    public static function from_id(int $id): base_card {
        $card = new static();
        $card->survey = survey::from_resource_id($id);

        return $card;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->survey->get_id();
    }

    /**
     * @param bool $is_dashboard
     * @return moodle_url
     */
    public function get_url(bool $is_dashboard): moodle_url {
        return builder::to(survey::get_resource_type(), ['id' => $this->get_id()])
            ->from('block_totara_recently_viewed', ['dashboard' => $is_dashboard])
            ->url();
    }

    /**
     * @return string|null
     */
    public function get_title(): ?string {
        // this will have to be updated later when
        // multiple questions are added to surveys
        return format_string($this->survey->get_name());
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
        return $this->survey->get_userid();
    }

    /**
     * @param bool $tile_view
     * @param theme_config $theme_config
     * @return moodle_url|null
     */
    public function get_image(bool $tile_view, theme_config $theme_config): ?\moodle_url {
        return null;
    }

    /**
     * @return array
     */
    public function get_extra_data(): array {
        $time_expired = $this->survey->get_timeexpired();

        $format = get_string('strftimedate', 'langconfig');
        $time = userdate($time_expired, $format);

        return [
            'footer' => $time_expired ? get_string('expiredat', 'engage_survey', $time) : '',
        ];
    }

    /**
     * @return bool
     */
    public function is_library_card(): bool {
        return true;
    }
}