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

use degeneration\items\item;
use totara_reaction\entity\reaction as entity;

final class reaction extends item {
    /**
     * @var string int
     */
    private $component;

    /**
     * @var int
     */
    private $instance_id;

    /**
     * @var int
     */
    private $context_id;

    /**
     * @var int
     */
    private $actor_id;

    /**
     * reaction constructor.
     *
     * @param string $component
     * @param int $instance_id
     * @param int $context_id
     * @param int $actor_id
     */
    public function __construct(string $component, int $instance_id, int $context_id, int $actor_id) {
        $this->component = $component;
        $this->instance_id = $instance_id;
        $this->context_id = $context_id;
        $this->actor_id = $actor_id;
    }

    /**
     * @return array
     */
    public function get_properties(): array {
        return [
            'userid' => $this->actor_id,
            'timecreated' => time(),
            'component' => $this->component,
            'area' => 'media',
            'instanceid' => $this->instance_id,
            'contextid' => $this->context_id,
        ];
    }

    /**
     * @return string|null
     */
    public function get_entity_class(): ?string {
        return entity::class;
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