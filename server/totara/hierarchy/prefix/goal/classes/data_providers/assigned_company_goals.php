<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package hierarchy_goal
 */

namespace hierarchy_goal\data_providers;

use Closure;
use core\orm\entity\repository;
use hierarchy_goal\entity\company_goal;
use hierarchy_goal\entity\company_goal_assignment;

/**
 * Handles company goal assignments.
 */
class assigned_company_goals {
    public const SORT_ASSIGNMENT_ID = 'assignment_id';
    public const SORT_USER_ID = 'user_id';
    public const SORT_GOAL_ID = 'goal_id';
    public const SORT_GOAL_NAME = 'goal_name';
    public const SORT_TARGET_DATE = 'target_date';

    // Mapping of sort field display names to physical entity _columns_.
    public const SORT_FIELDS = [
        self::SORT_ASSIGNMENT_ID => 'id',
        self::SORT_USER_ID => 'userid',
        self::SORT_GOAL_ID => 'goalid',
        self::SORT_GOAL_NAME => company_goal::TABLE . '.fullname',
        self::SORT_TARGET_DATE => company_goal::TABLE . '.targetdate'
    ];

    /**
     * Creates an instance of the data provider.
     *
     * @return goal_data_provider the dataprovider.
     */
    public static function create(): goal_data_provider {
        return new goal_data_provider(
            company_goal_assignment::class,
            self::SORT_FIELDS,
            'hierarchy_goal\entity\filters\company_goal_assignment_filters::for',
            Closure::fromCallable([self::class, 'repo_factory'])
        );
    }

    /**
     * Company goal assignment repository factory.
     *
     * @return repository the repository.
     */
    private static function repo_factory(): repository {
        // The join with the goal table is needed so that results can be filtered
        // and ordered by goal names.
        $goal = company_goal::TABLE;

        return company_goal_assignment::repository()
            ->join($goal, 'goalid', '=', 'id')
            ->select(company_goal_assignment::TABLE . '.*')
            ->add_select("$goal.targetdate as goal_targetdate")
            ->add_select("$goal.fullname as goal_fullname");
    }
}
