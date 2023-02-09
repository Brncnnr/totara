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

namespace degeneration\items\container_workspace;

use container_workspace\entity\workspace_discussion;
use core\json_editor\node\paragraph;
use degeneration\App;
use degeneration\items\item;

final class discussion extends item {
    /**
     * @var int
     */
    private $course_id;

    /**
     * @var int
     */
    private $user_id;

    /**
     * discussion constructor.
     *
     * @param int $course_id
     * @param int $user_id
     */
    public function __construct(int $course_id, int $user_id) {
        $this->course_id = $course_id;
        $this->user_id = $user_id;
    }

    /**
     * @return array
     */
    public function get_properties(): array {
        $faker = App::faker();
        $text = $faker->paragraph;

        return [
            'course_id' => $this->course_id,
            'content_format' => FORMAT_JSON_EDITOR,
            'content' => json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text($text)
                ]
            ]),
            'content_text' => $text,
            'user_id' => $this->user_id,
            'time_created' => time(),
            'timestamp' => time(),
        ];
    }

    /**
     * @return string|null
     */
    public function get_entity_class(): ?string {
        return workspace_discussion::class;
    }
}