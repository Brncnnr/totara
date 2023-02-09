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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package criteria_coursecompletion
 * @subpackage test
 */

use core\webapi\query_resolver;
use criteria_coursecompletion\coursecompletion;
use criteria_coursecompletion\webapi\resolver\query\achievements;
use totara_criteria\criterion;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once $CFG->dirroot.'/totara/criteria/tests/course_achievements_testcase.php';

/**
 * Tests the query to fetch data for a coursecompletion criteria
 *
 * @group totara_competency
 */
class criteria_coursecompletion_webapi_query_achievements_testcase extends totara_criteria_course_achievements_testcase {

    /**
     * @return criterion
     */
    public function get_criterion(): criterion {
        return new coursecompletion();
    }

    /**
     * @return string|query_resolver
     */
    public function get_resolver_classname(): string {
        return achievements::class;
    }
}