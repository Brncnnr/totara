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
use ml_recommender\entity\component;
use ml_recommender\entity\interaction as entity;
use ml_recommender\entity\interaction_type;
use ml_recommender\repository\component_repository;
use ml_recommender\repository\interaction_type_repository;

final class interaction extends item {
    /**
     * @var array
     */
    private static $component_cache = [];

    /**
     * @var array
     */
    private static $interaction_cache = [];

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
     * @var string
     */
    private $interaction;

    /**
     * @var int
     */
    private $rating;

    /**
     * interaction constructor.
     *
     * @param int $user_id
     * @param int $item_id
     * @param string $component
     * @param string $interaction
     * @param int $rating
     */
    public function __construct(int $user_id, int $item_id, string $component, string $interaction, int $rating) {
        $this->user_id = $user_id;
        $this->item_id = $item_id;
        $this->component = $component;
        $this->interaction = $interaction;
        $this->rating = $rating;
    }

    /**
     * @param string $component
     * @return int
     */
    public static function get_component_id(string $component): int {
        if (!isset(static::$component_cache[$component])) {
            /** @var component_repository $component_repo */
            $component_repo = component::repository();
            static::$component_cache[$component] = $component_repo->ensure_id($component);
        }

        return static::$component_cache[$component];
    }

    /**
     * @param string $interaction
     * @return int
     */
    public static function get_interaction_id(string $interaction): int {
        if (!isset(static::$interaction_cache[$interaction])) {
            /** @var interaction_type_repository $interaction_type_repo */
            $interaction_type_repo = interaction_type::repository();
            static::$interaction_cache[$interaction] = $interaction_type_repo->ensure_id($interaction);
        }

        return static::$interaction_cache[$interaction];
    }

    /**
     * @return array
     */
    public function get_properties(): array {
        // 6 months
        $period = 6 * 30 * 24 * 60 * 60;
        return [
            'user_id' => $this->user_id,
            'item_id' => $this->item_id,
            'component_id' => static::get_component_id($this->component),
            'interaction_type_id' => static::get_interaction_id($this->interaction),
            'rating' => $this->rating,
            'time_created' => rand(time() - $period, time()),
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