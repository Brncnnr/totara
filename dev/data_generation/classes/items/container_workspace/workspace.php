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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 */
namespace degeneration\items\container_workspace;

use container_workspace\workspace as model;
use degeneration\items\item;
use degeneration\App;
use core\json_editor\node\paragraph;

final class workspace extends item {
    /**
     * @return array
     */
    public function get_properties(): array {
        global $USER;
        $faker = App::faker();

        return [
            'name' => $faker->text(60),
            'description' => json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text(
                        $faker->text
                    )
                ]
            ]),
            'description_format' => FORMAT_JSON_EDITOR,
            'owner' => $USER->id
        ];
    }

    /**
     * @return bool
     */
    public function save(): bool {
        $this->create_workspace();
        return true;
    }

    /**
     * @param int|null $owner_id
     * @return model
     */
    public function create_workspace(?int $owner_id = null): model {
        $generator = App::generator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $properties = $this->get_properties();
        return $workspace_generator->create_workspace(
            $properties['name'],
            $properties['description'],
            $properties['description_format'],
            $owner_id ?? $properties['owner']
        );
    }
}