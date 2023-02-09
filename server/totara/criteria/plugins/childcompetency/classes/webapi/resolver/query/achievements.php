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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Marco Song <marco.song@totaralearning.com>
 * @package criteria_childcompetency
 */

namespace criteria_childcompetency\webapi\resolver\query;

use core\orm\collection;
use core\orm\entity\repository;
use criteria_childcompetency\childcompetency;
use totara_criteria\criterion;
use totara_criteria\webapi\resolver\query\competency_achievements;
use totara_competency\entity\competency_achievement;
use totara_hierarchy\entity\competency;

/**
 * Fetches all achievments for the childcompetency criteria type
 */
class achievements extends competency_achievements {

    protected static function fetch_criterion(int $criterion_id): criterion {
        return childcompetency::fetch($criterion_id);
    }

    /**
     * get competencies together with related achievements and availabilities
     *
     * @param criterion $completion_criteria
     * @param int       $user_id
     *
     * @return collection
     */
    protected static function get_competencies(criterion $completion_criteria, int $user_id): collection {
        return competency::repository()
            ->where('parentid', $completion_criteria->get_competency_id())
            ->with('availability')
            ->with(
                [
                    'achievement' => function (repository $repository) use ($user_id) {
                        $repository->where('user_id', $user_id)
                            ->where('proficient', 1)
                            ->where('status', competency_achievement::ACTIVE_ASSIGNMENT)
                            ->with('value');
                    },
                ]
            )
            ->order_by('sortthread')
            ->get();
    }
}
