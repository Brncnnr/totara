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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

namespace totara_criteria\evaluators;

use totara_criteria\criterion;

/**
 * Class item_evaluator
 * Item evaluators are responsible for evaluating which assigned users have satisfied criteria conditions.
 * Results are stored in the totara_criteria_item_record table.
 *
 * @package totara_criteria
 */
abstract class item_evaluator {

    /** @var item_evaluator_user_source $user_source */
    protected $user_source = null;

    /**
     * Constructor.
     * @param item_evaluator_user_source $user_source Source containing the user ids
     */
    final public function __construct(item_evaluator_user_source $user_source) {
        $this->user_source = $user_source;
    }

    /**
     * Evaluate criteria completion / satisfaction for users in the source
     * and save the last time the item was evaluated
     *
     * This function should ideally not be overridden, but not making it final
     * to allow test classes to override it and simplify the testing process
     *
     * @param criterion $criterion
     */
    public function update_completion(criterion $criterion) {
        if (!$criterion->is_valid()) {
            return;
        }

        // Getting the time at the start. This is used as last_evaluated time to
        // ensure we don't miss items updating via observers during the marking process
        $now = time();
        $criterion_id = $criterion->get_id();

        $this->user_source->delete_item_records($criterion_id);
        $this->user_source->create_item_records($criterion_id, $this->get_default_criteria_met(), $now);

        $this->update_criterion_completion($criterion, $now);

        // Now we mark all the users that changed since the last time the criterion was evaluated
        $last_evaluated = $criterion->get_last_evaluated() ?? 0;
        $this->user_source->mark_updated_assigned_users($criterion->get_id(), $last_evaluated);

        $criterion->set_last_evaluated($now)
            ->save_last_evaluated();
    }

    /**
     * Evaluate criteria completion / satisfaction for users in the source
     *
     * @param criterion $criterion
     * @param int $now
     */
    abstract protected function update_criterion_completion(criterion $criterion, int $now);

    /**
     * Default value to insert into criterion_met for new item records
     * @return int|null
     */
    protected function get_default_criteria_met(): ?int {
        return 0;
    }

}
