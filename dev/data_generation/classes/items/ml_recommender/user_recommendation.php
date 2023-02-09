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

namespace degeneration\items\ml_recommender;

use degeneration\items\item;
use ml_recommender\entity\recommended_user_item as entity;

final class user_recommendation extends item {
    /**
     * @var int
     */
    private static $counter = 0;

    /**
     * @var int
     */
    private $user_id;

    /**
     * @var int
     */
    private $item_id;

    /**
     * @var string
     */
    private $component;

    /**
     * @var string|null
     */
    private $area;

    /**
     * user_recommendation constructor.
     *
     * @param int $user_id
     * @param int $item_id
     * @param string $component
     * @param string|null $area
     */
    public function __construct(int $user_id, int $item_id, string $component, ?string $area) {
        $this->user_id = $user_id;
        $this->item_id = $item_id;
        $this->component = $component;
        $this->area = $area;
    }

    /**
     * @return array
     */
    public function get_properties(): array {
        $id = static::$counter++;
        return [
            'unique_id' => "r_{$id}", // Value just has to be unique, doesn't matter otherwise
            'user_id' => $this->user_id,
            'item_id' => $this->item_id,
            'component' => $this->component,
            'area' => $this->area,
            'time_created' => time(),
            'score' => rand(1, 10),
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