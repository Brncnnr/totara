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

namespace degeneration\items\totara_comment;

use core\json_editor\node\paragraph;
use core\orm\query\builder;
use degeneration\App;
use degeneration\items\item;
use degeneration\performance_testing;

final class comment extends item {
    /**
     * @var int
     */
    private $instance_id;

    /**
     * @var int
     */
    private $user_id;

    /**
     * @var string
     */
    private $component;

    /**
     * @var string
     */
    private $area;

    /**
     * @var int|null
     */
    private $parent_id = null;

    /**
     * comment constructor.
     *
     * @param int $instance_id
     * @param int $user_id
     * @param string $component
     * @param string $area
     * @param int|null $parent_id
     */
    public function __construct(int $instance_id, int $user_id, string $component, string $area, ?int $parent_id = null) {
        $this->instance_id = $instance_id;
        $this->user_id = $user_id;
        $this->component = $component;
        $this->area = $area;
        $this->parent_id = $parent_id;
    }

    /**
     * @return array
     */
    public function get_properties(): array {
        $faker = App::faker();
        $text = $faker->paragraph;

        return [
            'instanceid' => $this->instance_id,
            'component' => $this->component,
            'area' => $this->area,
            'format' => FORMAT_JSON_EDITOR,
            'content' => json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text($text)
                ]
            ]),
            'userid' => $this->user_id,
            'timecreated' => $faker->unixTime,
            'contenttext' => $text,
            'parentid' => $this->parent_id,
        ];
    }

    /**
     * @param int $count
     * @param array $users
     * @param $progress_done
     * @param int $progress_total
     * @return $this
     */
    public function add_replies(int $count, array $users, &$progress_done, $progress_total = 0) {
        $comment_id = $this->get_data('id');
        $bulk = [];
        for ($i = 0; $i < $count; $i++) {
            $user_id = $this->user_id;
            if (!empty($users)) {
                $user_id = $users[array_rand($users, 1)];
            }
            // Don't random guess multiple times, just use the comment owner if we picked the guest user
            if (isguestuser($user_id)) {
                $user_id = $this->user_id;
            }

            $bulk[] = (new static(
                $this->instance_id,
                $user_id,
                $this->component,
                $this->area,
                $comment_id
            ))->create_for_bulk();
            if (count($bulk) >= BATCH_INSERT_MAX_ROW_COUNT) {
                builder::get_db()->insert_records(\totara_comment\entity\comment::TABLE, $bulk);
                $bulk = [];
                performance_testing::show_progress($progress_done, $progress_total);
            }
        }
        if (!empty($bulk)) {
            builder::get_db()->insert_records(\totara_comment\entity\comment::TABLE, $bulk);
            performance_testing::show_progress($progress_done, $progress_total);
        }
        return $this;
    }

    /**
     * @return string|null
     */
    public function get_entity_class(): ?string {
        return \totara_comment\entity\comment::class;
    }

    /**
     * @return array
     */
    public function create_for_bulk(): array {
        $properties = [];

        foreach ($this->get_properties() as $key => $property) {
            $properties[$key] = $this->evaluate_property($property);
        }

        return $properties;
    }
}