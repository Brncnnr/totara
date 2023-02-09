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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\task\service\data;

use mod_perform\entity\activity\activity;
use totara_core\entity\relationship;

/**
 * Instance creation Activity configuration.
 * Memorizes computed checks used in the participant instance creation process.
 *
 * @package mod_perform\task\service\data
 */
class subject_instance_activity {

    /**
     * Activity entity.
     *
     * @var activity
     */
    private $activity;

    /**
     * Indicates if the activity has a manual relationship.
     * @var bool
     */
    private $has_manual_relationship;

    /**
     * List of core relationships used in activity.
     *
     * @var array
     */
    private $activity_relationships;

    /**
     * List of section relationship configs.
     *
     * @var array
     */
    private $section_relationships;

    /**
     * List of section relationships grouped by relationship id.
     *
     * @var array
     */
    private $section_relationships_grouped_by_relationship_id;

    /**
     * subject_instance_activity constructor.
     *
     * @param activity $activity
     */
    public function __construct(activity $activity) {
        $this->activity = $activity;
    }

    /**
     * Checks if activity has a manual relationship.
     *
     * @return bool
     */
    public function has_manual_relationship(): bool {
        if (is_null($this->has_manual_relationship)) {
            $this->compute_has_manual_relationship();
        }

        return $this->has_manual_relationship;
    }

    /**
     * Computes if the activity has a manual relationship and memorizes the result.
     *
     * @return void
     */
    private function compute_has_manual_relationship(): void {
        $this->has_manual_relationship = $this->activity->sections->has(function ($section) {
            return $section->section_relationships->has(function ($section_relationship) {
                return (int) $section_relationship->core_relationship->type === relationship::TYPE_MANUAL;
            });
        });
    }

    /**
     * Get all core relationships used in activity.
     *
     * @return array
     */
    public function get_activity_relationships(): array {
        if (is_null($this->activity_relationships)) {
            $this->compute_activity_relationships();
        }

        return $this->activity_relationships;
    }

    /**
     * Computes all the core relationships used in the activity and memorizes result.
     *
     * @return void
     */
    private function compute_activity_relationships(): void {
        $this->activity_relationships = [];

        foreach ($this->activity->sections as $section) {
            foreach ($section->section_relationships as $section_relationship) {
                $core_relationship_id = $section_relationship->core_relationship_id;

                if (empty($this->activity_relationships[$core_relationship_id])) {
                    $this->activity_relationships[$core_relationship_id] = $section_relationship->core_relationship;
                }
            }
        }
    }

    /**
     * Get all section relationships in the activity.
     *
     * @return array
     */
    public function get_section_relationships(): array {
        if (is_null($this->section_relationships)) {
            $this->compute_section_relationship();
        }

        return $this->section_relationships;
    }

    /**
     * Computes all the section relationships in the activity and memorizes result.
     *
     * @return void
     */
    private function compute_section_relationship(): void {
        $this->section_relationships = [];
        foreach ($this->activity->sections as $section) {
            foreach ($section->section_relationships as $section_relationship) {
                $section_relationship->relate('section', $section);
                $this->section_relationships[] = $section_relationship;
            }
        }
    }

    /**
     * Get section relationships owned by the relationship id.
     *
     * @param int $relationship_id
     * @return array
     */
    public function get_section_relationships_owned_by_relationship_id(int $relationship_id): array {
        if (!isset($this->section_relationships_grouped_by_relationship_id[$relationship_id])) {
            $this->compute_section_relationships_owned_by_relationship_ids();
        }

        return $this->section_relationships_grouped_by_relationship_id[$relationship_id] ?? [];
    }

    /**
     * Group section relationships by relationship_id and memorizes result.
     *
     * @return void
     */
    private function compute_section_relationships_owned_by_relationship_ids(): void {
        $section_relationships = $this->get_section_relationships();
        $this->section_relationships_grouped_by_relationship_id = [];

        foreach ($section_relationships as $section_relationship) {
            $core_relationship_id = $section_relationship->core_relationship_id;
            $this->section_relationships_grouped_by_relationship_id[$core_relationship_id][] = $section_relationship;
        }
    }
}
