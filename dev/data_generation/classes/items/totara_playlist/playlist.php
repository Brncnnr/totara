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
 */

namespace degeneration\items\totara_playlist;

use core\json_editor\node\paragraph;
use degeneration\App;
use degeneration\items\item;
use totara_engage\entity\engage_resource;
use totara_topic\topic_helper;

final class playlist extends item {
    /**
     * @var int
     */
    private $owner_id;

    /**
     * @var int
     */
    private $access;

    /**
     * @var array
     */
    private $topics;

    /**
     * @var int
     */
    private $context_id;

    /**
     * playlist constructor.
     *
     * @param int $owner_id
     * @param int $access
     * @param array $topics
     */
    public function __construct(int $owner_id, int $access, array $topics) {
        $this->owner_id = $owner_id;
        $this->access = $access;
        $this->topics = $topics;

        $context = \context_user::instance($this->owner_id);
        $this->context_id = $context->id;
    }

    /**
     * @param int $playlist_id
     * @param int $resource_id
     * @param int $actor_id
     * @param int $sort_order
     * @return array
     */
    public static function add_resource(int $playlist_id, int $resource_id, int $actor_id, int $sort_order) {

        return [
            'playlistid' => $playlist_id,
            'resourceid' => $resource_id,
            'userid' => $actor_id,
            'timecreated' => time(),
            'sortorder' => $sort_order,
        ];
    }

    /**
     * @param int $resource_id
     * @param int $count
     */
    public static function update_playlist_resource_count(int $resource_id, int $count) {
        $entity = new engage_resource($resource_id);
        $entity->countusage = $count;
        $entity->save();
    }

    /**
     * @return array
     */
    public function get_properties(): array {
        $faker = App::faker();

        return [
            'name' => $faker->sentence(5),
            'access' => $this->access,
            'userid' => $this->owner_id,
            'summary' => json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text($faker->sentence(20))
                ]
            ]),
            'summaryformat' => FORMAT_JSON_EDITOR,
            'contextid' => $this->context_id,
        ];
    }

    /**
     * @return bool
     */
    public function save(): bool {
        parent::save();

        $playlist_id = $this->get_data('id');
        foreach ($this->topics as $topic_id) {
            topic_helper::add_topic_usage($topic_id, 'totara_playlist', 'playlist', $playlist_id);
        }

        return true;
    }

    /**
     * @return array
     */
    public function get_playlist_info(): array {
        return [$this->get_data('id'), $this->access, $this->context_id, $this->owner_id];
    }

    /**
     * @return string|null
     */
    public function get_entity_class(): ?string {
        return \totara_playlist\entity\playlist::class;
    }
}