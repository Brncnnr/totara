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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @author David Curry <david.curry@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver\query;

use context_coursecat;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;

class courses_by_parent_category extends query_resolver {

    public static function resolve(array $args, execution_context $ec) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        if (!empty($args['sort'])) {
            $sortstr = mb_strtolower($args['sort']);
            $sortorder = [
                $sortstr => 1
            ];
            $options = ['sort' => $sortorder];
        } else {
            $options = [];
        }

        $options['idonly'] = true;
        $category = \coursecat::get($args['categoryid']);
        $ec->set_relevant_context(context_coursecat::instance($category->id));

        $courseids = $category->get_courses($options);
        if (empty($courseids)) {
            // There are no courses in this category.
            return [];
        } else {
            list($insql, $inparams) = $DB->get_in_or_equal($courseids);
            $courses = $DB->get_records_select('course', "id {$insql}", $inparams);
            foreach ($courses as $course) {
                $course->image = course_get_image($course);
            }

            return $courses;
        }
    }

    public static function get_middleware(): array {
        return [
            require_login::class
        ];
    }

}
