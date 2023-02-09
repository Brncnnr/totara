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
 * @package ml_recommender
 */

namespace ml_recommender\testing;

use stdClass, coding_exception;

use ml_recommender\repository\interaction_type_repository;
use ml_recommender\repository\component_repository;

/**
 * Generator for ml_recommenders. Allows mocking of the recommender engine.
 */
final class generator extends \core\testing\component_generator {
    /**
     * Create the user recommender record
     *
     * @param int $user_id
     * @param int $item_id
     * @param string $component
     * @param string|null $area
     * @param float|null $score
     * @param int $seen
     * @return int
     */
    public function create_user_recommendation(
        int $user_id,
        int $item_id,
        string $component,
        ?string $area = null,
        ?float $score = null,
        int $seen = 0
    ): int {
        global $DB;

        return $DB->insert_record('ml_recommender_users', [
            'user_id' => $user_id,
            'unique_id' => $component . $item_id,
            'item_id' => $item_id,
            'component' => $component,
            'area' => $area,
            'score' => $score ?? 1.0,
            'seen' => $seen,
            'time_created' => time(),
        ]);
    }

    /**
     * Create the item recommender record.
     * Target columns are the item this recommendation applies to
     *
     * @param int $target_item_id
     * @param int $item_id
     * @param string $component
     * @param string|null $area
     * @param float|null $score
     * @return int
     */
    public function create_item_recommendation(
        int $target_item_id,
        int $item_id,
        string $component,
        ?string $area = null,
        ?float $score = null
    ): int {
        global $DB;

        return $DB->insert_record('ml_recommender_items', [
            'unique_id' => $component . $target_item_id . $component . $item_id,
            'target_item_id' => $target_item_id,
            'target_component' => $component,
            'target_area' => $area,
            'item_id' => $item_id,
            'component' => $component,
            'area' => $area,
            'score' => $score ?? 1.0,
            'time_created' => time(),
        ]);
    }

    /**
     * @param int $user_id
     * @param int $item_id
     * @param string $component
     * @param string|null $interaction
     * @param int|null $rating
     * @param string|null $area
     * @return int
     */
    public function create_recommender_interaction(
        int $user_id,
        int $item_id,
        string $component,
        ?string $interaction = 'view',
        ?int $rating = 1,
        ?string $area = null
    ):int {
        global $DB;

        $component_repo = \ml_recommender\entity\component::repository();
        $type_repo = \ml_recommender\entity\interaction_type::repository();

        return $DB->insert_record('ml_recommender_interactions', [
            'user_id' => $user_id,
            'item_id' => $item_id,
            'component_id' => $component_repo->ensure_id($component, $area),
            'interaction_type_id' => $type_repo->ensure_id($interaction),
            'rating' => $rating,
            'time_created' => time()
        ]);
    }

    /**
     * Create the trending recommendation record
     *
     * @param int $item_id
     * @param string $component
     * @param string|null $area
     * @param int|null $counter
     * @return int
     */
    public function create_trending_recommendation(int $item_id, string $component, ?string $area = null, int $counter = null): int {
        global $DB;

        return $DB->insert_record('ml_recommender_trending', [
            'unique_id' => $component . $item_id,
            'item_id' => $item_id,
            'component' => $component,
            'area' => $area,
            'counter' => $counter ?? 1,
            'time_created' => time(),
        ]);
    }

    /**
     * Create the user recommendation based on the provided params.
     * Used by behat.
     *
     * @param array $parameters
     */
    public function create_user_recommendation_from_params(array $parameters): void {
        if (empty($parameters['username']) || empty($parameters['component']) || empty($parameters['name'])) {
            throw new coding_exception(
                'Username, component & component name are required'
            );
        }

        $user = \core_user::get_user_by_username($parameters['username']);

        $name = $parameters['name'];
        $component = $parameters['component'];
        $area = $parameters['area'] ?? null;
        $score = $parameters['score'] ?? 1.0;
        $item_id = $this->get_component_id($name, $component, $area);

        // Insert it (no current model for it)
        $this->create_user_recommendation($user->id, $item_id, $component, $area, $score);
    }

    /**
     * Create the item recommendation based on the provided params.
     * Used by behat.
     *
     * @param array $parameters
     */
    public function create_item_recommendation_from_params(array $parameters): void {
        if (empty($parameters['component']) || empty($parameters['target_name']) || empty($parameters['name'])) {
            throw new coding_exception(
                'component & target_name & name are all required'
            );
        }

        $name = $parameters['name'];
        $target_name = $parameters['target_name'];
        $component = $parameters['component'];
        $area = $parameters['area'] ?? null;
        $score = $parameters['score'] ?? 1.0;
        $item_id = $this->get_component_id($name, $component, $area);
        $target_item_id = $this->get_component_id($target_name, $component, $area);

        // Insert it (no current model for it)
        $this->create_item_recommendation($item_id, $target_item_id, $component, $area, $score);
    }

    /**
     * Create the trending recommendation based on the provided params.
     * Used by behat.
     *
     * @param array $parameters
     */
    public function create_trending_recommendation_from_params(array $parameters): void {
        if (empty($parameters['component']) || empty($parameters['name'])) {
            throw new coding_exception(
                'Component & component name are required'
            );
        }

        $name = $parameters['name'];
        $component = $parameters['component'];
        $area = $parameters['area'] ?? null;
        $item_id = $this->get_component_id($name, $component, $area);
        $counter = !empty($parameters['counter']) ? $parameters['counter'] : null;

        // Insert it (no current model for it)
        $this->create_trending_recommendation($item_id, $component, $area, $counter);
    }

    /**
     * Lookup the item_id of the specific component based on name.
     * Where possible we keep it simple, as ml_recommender cannot be introduced as a dependency
     * in other components
     *
     * @param string $name
     * @param string $component
     * @param string|null $area
     * @return int|null
     */
    private function get_component_id(string $name, string $component, ?string $area = null): ?int {
        global $DB;

        // Go really low-level where possible to get the ids, it's just for testing
        switch ($component) {
            case 'engage_article':
            case 'engage_survey':
                return $DB->get_field('engage_resource', 'id', ['name' => $name, 'resourcetype' => $component]);

            case 'totara_playlist':
                return $DB->get_field('playlist', 'id', ['name' => $name]);

            case 'container_workspace':
                return $DB->get_field('course', 'id', ['shortname' => strtolower($name)]);
        }

        throw new coding_exception("Component '{$component}' is not supported by the ml_recommender generator");
    }
}