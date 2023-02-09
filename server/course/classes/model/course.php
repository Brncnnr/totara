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
 * @package core_course
 */

namespace core_course\model;

use core\entity\course as course_entity;
use container_course\course as course_container;
use core\orm\entity\model;

global $CFG;
require_once $CFG->dirroot.'/completion/completion_aggregation.php';

/**
 * Class course
 * This is a model that represent a course
 *
 * @package core\models
 */
class course extends model {

    /**
     * constructor. It's here for the purpose of type-hint
     *
     * @param course_entity $entity
     */
    public function __construct(course_entity $entity) {
        parent::__construct($entity);
    }

    protected static function get_entity_class(): string {
        return course_entity::class;
    }

    /**
     * Calculate the due date from the given time from the course's duedate settings
     * Note: We are not checking here whether the global 'enablecompletion' setting is set or not,
     *       simply calculate the duedate according to the course settings
     *
     * @return int|null due_date
     */
    public function calculate_duedate_from_time(int $start_time): ?int {
        /** @var course_entity $course */
        $course = $this->entity;

        if ($course->enablecompletion != COMPLETION_ENABLED || (!$course->duedate && !$course->duedateoffsetunit)) {
            return null;
        }

        if ($course->duedate) {
            return $course->duedate;
        }

        $amount = $course->duedateoffsetamount;
        switch ($course->duedateoffsetunit) {
            case course_container::DUEDATEOFFSETUNIT_DAYS:
                $offset = 'days';
                break;

            case course_container::DUEDATEOFFSETUNIT_WEEKS:
                $offset = 'weeks';
                break;

            case course_container::DUEDATEOFFSETUNIT_MONTHS:
                $offset = 'months';
                break;

            case course_container::DUEDATEOFFSETUNIT_YEARS:
                $offset = 'years';
                break;

            default:
                throw new \coding_exception('Invalid due date offset unit');
        }

        return strtotime("+{$amount} {$offset}", $start_time);
    }

}