<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Ning Zhou <ning.zhou@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\helpers\copy_pathway;

use pathway_criteria_group\entity\criteria_group;
use totara_competency\entity\pathway;
use totara_competency\helpers\result as resolver_result;
use totara_hierarchy\entity\competency;

/**
 * Holds an execution result.
 *
 * This is just an {@link https://blog.logrocket.com/javascript-either-monad-error-handling Either}
 * monad that explicitly treats a totara_competency\helpers\error as a failed
 * result.
 *
 * @property-read resolver_result $value
 * @method
 */
class result extends resolver_result {

    /**
     * return the total number count of the collection
     *
     * @return int
     */
    public function get_total_count(): int {
        if ($this->is_failed()) {
            return 0;
        }

        return $this->value->targets->count();
    }

    /**
     * return the count of item with criteria base pathway
     *
     * @return int
     * @throws \coding_exception
     */
    public function get_review_count(): int {
        // check if the query run successfully
        if ($this->is_failed()) {
            return 0;
        }

        // check if the source competency have criteria-based achievement path
        if (!$this->check_if_criteria_path($this->value->source)) {
            return 0;
        }

        return $this->value->targets->filter(function (competency $competency) {
            foreach ($competency->active_pathways as $pathway_entity) {
                /**
                 * @var pathway $pathway_entity
                 */
                if ($pathway_entity->path_type === pathway::TYPE_CRITERIA) {
                    return true;
                }
            }
            return false;
        })->count();
    }

    /**
     * check if the competency have critera-based achievement path with
     *
     * @param competency $competency
     * @return bool
     * @throws \coding_exception
     */
    private function check_if_criteria_path(competency $competency): bool {
        $criterion_types = [
            'linkedcourses',
            'coursecompletion',
            'childcompetency',
            'othercompetency'
        ];
        foreach ($competency->active_pathways as $pathway) {
            /**
             * @var pathway $pathway
             */
            if ($pathway->path_type === pathway::TYPE_CRITERIA) {
                /**
                 * @var criteria_group $path_instance
                 */
                $path_instance = criteria_group::repository()->find_or_fail($pathway->path_instance_id);
                foreach ($path_instance->criterions as $criterion) {
                    if (in_array($criterion->criterion_type, $criterion_types)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

}