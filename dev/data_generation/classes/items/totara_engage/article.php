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

namespace degeneration\items\totara_engage;

use core\json_editor\node\paragraph;
use degeneration\App;
use degeneration\items\item;
use engage_article\entity\article as article_entity;
use totara_engage\access\access;
use totara_engage\entity\engage_resource;

final class article extends item {
    /**
     * @var int
     */
    private $owner_id;

    /**
     * @var int
     */
    private $access;

    /**
     * @var int
     */
    private $time_view;

    /**
     * @var array
     */
    private $topics;

    /**
     * @var int
     */
    private $context_id;

    /**
     * @var int
     */
    private $resource_id;

    /**
     * article constructor.
     *
     * @param int $owner_id
     * @param int $access
     * @param null|int $time_view
     * @param array $topics
     */
    public function __construct(int $owner_id, int $access, ?int $time_view, array $topics) {
        $this->owner_id = $owner_id;
        $this->access = $access;
        $this->time_view = $time_view;
        $this->topics = $topics;
    }

    /**
     * @return array
     */
    public function get_properties(): array {
        $faker = App::faker();
        $text = $faker->paragraph(20) . PHP_EOL . $faker->paragraph(20);

        return [
            'name' => $faker->sentence(5),
            'content' => json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text($text)
                ]
            ]),
            'format' => FORMAT_JSON_EDITOR,
            'userid' => $this->owner_id,
            'access' => $this->access,
            'timeview' => $this->access != access::PRIVATE ? $this->time_view : null,
            'topics' => $this->topics,
        ];
    }

    /**
     * @return bool
     */
    public function save(): bool {
        // The generator/create function stresses out when creating large collections of surveys.
        // To avoid crashing the generator we will create the base article resource here.
        $properties = $this->get_properties();

        $article_entity = new article_entity();
        $article_entity->content = $properties['content'];
        $article_entity->format = $properties['format'];
        $article_entity->timeview = $properties['timeview'];
        $article_entity->save();

        $record = new engage_resource();
        $record->resourcetype = 'engage_article';
        $record->name = $properties['name'];
        $record->userid = $properties['userid'];
        $record->extra = json_encode([
            'timeview' => $properties['timeview'],
            'image' => null,
        ]);

        $context = \context_user::instance($properties['userid']);
        $record->contextid = $context->id;
        $record->access = $properties['access'];
        $this->context_id = $context->id;

        $record->instanceid = $article_entity->id;
        $record->save();

        $this->resource_id = $record->id;

        // Set the topics. Again, for speed we bypass the topic helper as the resolver lookups
        // get heavy on larger runs
        foreach ($properties['topics'] as $topic_raw_name) {
            \core_tag_tag::add_item_tag(
                'engage_survey',
                'engage_resource',
                $record->id,
                $context,
                $topic_raw_name,
                $properties['userid']
            );
        }

        return true;
    }

    /**
     * @return array
     */
    public function get_article_info(): array {
        return [$this->resource_id, $this->context_id, $this->owner_id, $this->time_view];
    }

    /**
     * @return int
     */
    public function get_resource_id(): int {
        return $this->resource_id;
    }
}