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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package core_completion
 */

namespace core_completion\model;

use core\entity\course_completion as course_completion_entity;
use core\orm\entity\model;
use core_completion\helper as course_completion_helper;
use core_course\model\course as course_model;

/**
 * Class course_completion
 * This is a model that represent a course_completion record
 *
 * @package core_completion\model
 */
class course_completion extends model {

    /**
     * constructor. It's here for the purpose of type-hint
     *
     * @param course_completion_entity $entity
     */
    public function __construct(course_completion_entity $entity) {
        parent::__construct($entity);
    }

    protected static function get_entity_class(): string {
        return course_completion_entity::class;
    }

    /**
     * Recalculate and update the due date
     */
    public function update_duedate(): void {
        /** @var course_completion_entity $course_completion */
        $course_completion = $this->entity;
        /** @var course_model $course */
        $course_model = course_model::load_by_entity($course_completion->course_instance);

        $updated_duedate = $course_model->calculate_duedate_from_time($course_completion->timeenrolled);
        if ($course_completion->duedate != $updated_duedate) {
            $course_completion->duedate = $updated_duedate;
            $course_completion->save();

            course_completion_helper::save_completion_log(
                $course_completion->course,
                $course_completion->userid,
                "Due date updated in update_duedate to " . course_completion_helper::format_log_date($updated_duedate)
            );
        }
    }
}
