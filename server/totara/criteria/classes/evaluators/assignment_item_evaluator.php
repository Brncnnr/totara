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

class assignment_item_evaluator extends item_evaluator {

    protected function update_criterion_completion(criterion $criterion, int $now) {
        // For assignment there is nothing to update after records of non-assigned users are
        // deleted and new records are added for newly assigned users.
        // This is already done by the paren method
    }

    /**
     * Default value to insert into criterion_met for new item records
     * @return int|null
     */
    protected function get_default_criteria_met(): ?int {
        return 1;
    }

}
