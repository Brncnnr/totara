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

namespace block_totara_recently_viewed\engage_article;

use block_totara_recently_viewed\card as base_card;
use engage_article\totara_engage\resource\article;
use moodle_url;
use theme_config;
use totara_engage\link\builder;
use totara_engage\timeview\time_view;
use totara_reaction\loader\reaction_loader;

/**
 * Article/Resource card for the recently viewed block
 *
 */
class card implements base_card {
    /**
     * @var article
     */
    private $article;

    /**
     * @param int $id
     * @return base_card
     */
    public static function from_id(int $id): base_card {
        $card = new static();
        $card->article = article::from_resource_id($id);

        return $card;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->article->get_id();
    }

    /**
     * @param bool $is_dashboard
     * @return moodle_url
     */
    public function get_url(bool $is_dashboard): moodle_url {
        return builder::to(article::get_resource_type(), ['id' => $this->get_id()])
            ->from('block_totara_recently_viewed', ['dashboard' => $is_dashboard])
            ->url();
    }

    /**
     * @return string|null
     */
    public function get_title(): ?string {
        return format_string($this->article->get_name());
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
        return $this->article->get_userid();
    }

    /**
     * @param bool $tile_view
     * @param theme_config $theme_config
     * @return moodle_url|null
     */
    public function get_image(bool $tile_view, theme_config $theme_config): ?\moodle_url {
        global $OUTPUT;
        $image = $this->article->get_image();
        if (!$image) {
            // No image, use the default
            return $OUTPUT->image_url('default', 'engage_article');
        }

        return $image;
    }

    /**
     * @return array
     */
    public function get_extra_data(): array {
        $timeview_label = '';
        switch ($this->timeview()) {
            case time_view::LESS_THAN_FIVE:
                $timeview_label = get_string('timelessthanfive', 'engage_article');
                break;

            case time_view::FIVE_TO_TEN:
                $timeview_label = get_string('timefivetoten', 'engage_article');
                break;

            case time_view::MORE_THAN_TEN:
                $timeview_label = get_string('timemorethanten', 'engage_article');
                break;
        }

        return [
            'timetoview' => $timeview_label,
            'likes' => $this->reactions(),
        ];
    }

    /**
     * @return int|null
     */
    private function timeview(): ?int {
        return $this->article->get_timeview();
    }

    /**
     * @return int|null
     */
    private function reactions(): ?int {
        $paginator = reaction_loader::get_paginator('engage_article', 'media', $this->get_id());
        return $paginator->get_total();
    }

    /**
     * @return bool
     */
    public function is_library_card(): bool {
        return true;
    }
}