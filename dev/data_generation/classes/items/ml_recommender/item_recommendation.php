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
use ml_recommender\entity\recommended_item as entity;

final class item_recommendation extends item {
    /**
     * @var int
     */
    private static $counter = 0;

    /**
     * @var int
     */
    private $target_item_id;

    /**
     * @var string
     */
    private $target_component;

    /**
     * @var int|null
     */
    private $target_area;

    /**
     * @var int
     */
    private $recommended_item_id;

    /**
     * @var string
     */
    private $recommended_component;

    /**
     * @var string|null
     */
    private $recommended_area;

    /**
     * item_recommendation constructor.
     *
     * @param int $target_item_id
     * @param string $target_component
     * @param int|null $target_area
     * @param int $recommended_item_id
     * @param string $recommended_component
     * @param string|null $recommended_area
     */
    public function __construct(
        int $target_item_id,
        string $target_component,
        ?int $target_area,
        int $recommended_item_id,
        string $recommended_component,
        ?string $recommended_area
    ) {
        $this->target_item_id = $target_item_id;
        $this->target_component = $target_component;
        $this->target_area = $target_area;
        $this->recommended_item_id = $recommended_item_id;
        $this->recommended_component = $recommended_component;
        $this->recommended_area = $recommended_area;
    }

    /**
     * @return array
     */
    public function get_properties(): array {
        $id = static::$counter++;
        return [
            'unique_id' => "r_{$id}", // Value just has to be unique, doesn't matter otherwise
            'target_item_id' => $this->target_item_id,
            'target_component' => $this->target_component,
            'target_area' => $this->target_area,
            'item_id' => $this->recommended_item_id,
            'component' => $this->recommended_component,
            'area' => $this->recommended_area,
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