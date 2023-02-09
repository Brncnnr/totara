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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core_course
 */

namespace core_course\entity\filter;

use Closure;
use core\orm\query\builder;
use totara_core\entity\filter\user_learning_progress_filter;

/**
 * Convenience filters to use with the entities.
 */
class course_progress extends user_learning_progress_filter {

    /**
     * @inheritDoc
     */
    protected function completed(): Closure {
        return function(builder $builder) {
            $builder->where_not_null('cc.status')
                ->where('cc.status', COMPLETION_STATUS_COMPLETE);
        };
    }

    /**
     * @inheritDoc
     */
    protected function in_progress(): Closure {
        return function(builder $builder) {
            $builder->where_not_null('cc.status')
                ->where('cc.status', COMPLETION_STATUS_INPROGRESS);
        };
    }

    /**
     * @inheritDoc
     */
    protected function not_started(): Closure {
        return function(builder $builder) {
            $builder->where_not_null('cc.status')
                ->where('cc.status', COMPLETION_STATUS_NOTYETSTARTED);
        };
    }

    /**
     * @inheritDoc
     */
    protected function not_tracked(): Closure {
        return function(builder $builder) {
            $builder->where_null('cc.status')
                ->or_where('cc.status', COMPLETION_TRACKING_NONE);
        };
    }

    /**
     * @inheritDoc
     */
    public function apply() {
        global $CFG, $USER;

        // Include the completion status constants.
        require_once($CFG->dirroot.'/lib/completionlib.php');
        require_once($CFG->dirroot.'/completion/completion_completion.php');

        // Setup the filter.
        $progress = $this->value['progress'];
        $user_id = $this->value['user_id'] ?? $USER->id;

        $this->builder
            ->left_join(['course_completions', 'cc'], function (builder $joining) use ($progress, $user_id) {
                $joining->where_raw('cc.course = course.id');
                if (!empty($user_id)) {
                    $joining->where('userid', $user_id);
                }
            });
        $this->builder->where($this->get_status($progress));
    }

}
