<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_playlist
 */
namespace totara_playlist\totara_engage\card;

use theme_config;
use totara_comment\loader\comment_loader;
use totara_engage\rating\rating_manager;
use totara_engage\card\card;
use totara_playlist\entity\playlist_resource;
use totara_playlist\local\image_processor;
use totara_playlist\playlist;
use totara_playlist\repository\playlist_resource_repository;
use totara_topic\provider\topic_provider;
use totara_tui\output\component;

final class playlist_card extends card {
    /**
     * @return component
     */
    public function get_tui_component(): component {
        return new component("totara_playlist/components/card/PlaylistCard");
    }

    /**
     * @param theme_config|null $theme_config
     * @return array
     */
    public function get_extra_data(?theme_config $theme_config = null): array {
        global $USER;

        /** @var playlist_resource_repository $repo */
        $repo = playlist_resource::repository();

        $rating = rating_manager::instance($this->instanceid, 'totara_playlist', 'playlist');
        $extra = [
            'image' => $this->get_card_image('totara_playlist_card', $theme_config)->out(false),
            // Default to false, but update this field base on the capability and access manager.
            'actions' => false,
            'resources' => $repo->get_total_of_resources($this->instanceid),
            'rating' => $rating->avg(),
            'ratingCount' => $rating->count(),
        ];

        // $userid is the owner of the playlist. We need to check if the $userid is the owner of the playlist or not.
        // If it is, then we show the actions.
        $userid = $this->get_userid();

        if ($USER->id == $userid) {
            $extra['actions'] = true;
        }

        return $extra;
    }

    /**
     * @return array
     */
    public function get_topics(): array {
        $id = $this->get_instanceid();
        return topic_provider::get_for_item($id, $this->component, 'playlist');
    }

    /**
     * @return int
     */
    public function get_total_comments(): int {
        return comment_loader::count_comments(
            $this->instanceid,
            'totara_playlist',
            'comment'
        );
    }

    /**
     * @param string|null $preview_mode
     * @param theme_config|null $theme_config
     * @return \moodle_url|null
     * @throws \coding_exception
     */
    public function get_card_image(?string $preview_mode = null, ?theme_config $theme_config = null): ?\moodle_url {
        global $OUTPUT, $PAGE;

        $playlist = playlist::from_id($this->get_instanceid());
        $processor = image_processor::make();
        $file_image = $processor->get_image_for_playlist($playlist);

        $image = $OUTPUT->image_url("default_collection", 'totara_playlist');
        if ($file_image) {
            $image = $processor->get_image_url($file_image);
            $image->params([
                'hash' => $file_image->get_contenthash(),
                'theme' => $PAGE->theme->name,
            ]);
        }

        if ($preview_mode) {
            $image->param('preview', $preview_mode);
        }

        return $image;
    }

    /**
     * @return component
     */
    public function get_card_image_component(): component {
        return new component('totara_playlist/components/card/PlaylistCardImage');
    }
}