<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace pathway_criteria_group;


use totara_competency\pathway;
use totara_competency\pathway_evaluator;
use totara_competency\pathway_evaluator_user_source;
use totara_criteria\criterion;
use totara_criteria\evaluators\item_evaluator;
use totara_criteria\evaluators\item_evaluator_user_source;

class criteria_group_evaluator extends pathway_evaluator {

    /** @var item_evaluator_user_source $item_evaluator_user_source */
    private $item_evaluator_user_source;

    /**
     * @param pathway $pathway
     * @param pathway_evaluator_user_source $user_id_source
     */
    public function __construct(pathway $pathway, pathway_evaluator_user_source $user_id_source) {
        $user_id_source = new criteria_group_evaluator_user_source(
            $user_id_source->get_source(),
            $user_id_source->is_full_user_set()
        );
        parent::__construct($pathway, $user_id_source);

        $this->item_evaluator_user_source = $this->get_item_evaluator_user_source($user_id_source);
    }

    /**
     * Evaluate the value achieved for each assigned user
     *
     * @param int $aggregation_time
     */
    protected function evaluate_user_achievements(int $aggregation_time) {
        // First update all criteria item_records for assigned users.
        /** @var criterion $criterion */
        foreach ($this->pathway->get_criteria() as $criterion) {
            /** @var item_evaluator $item_evaluator */
            $item_evaluator_class = $criterion::item_evaluator();
            if (!empty($item_evaluator_class)) {
                $item_evaluator = new $item_evaluator_class($this->item_evaluator_user_source);
                $item_evaluator->update_completion($criterion);
            }
        }
    }

    /**
     * Instantiate and return a item_evaluator_user_source using the same user_id_source as the pathway
     *
     * @param pathway_evaluator_user_source $user_id_source
     * @return item_evaluator_user_source
     */
    private function get_item_evaluator_user_source(pathway_evaluator_user_source $user_id_source): item_evaluator_user_source {
        return new item_evaluator_user_source(
            $user_id_source->get_source(),
            $user_id_source->is_full_user_set()
        );
    }
}
