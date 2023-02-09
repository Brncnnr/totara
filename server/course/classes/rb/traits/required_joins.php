<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package core_course
 */

namespace core_course\rb\traits;

defined('MOODLE_INTERNAL') || die();

trait required_joins {

    /**
     * Add audience visibility required joins.
     * NOTE: add post_config() function to your report, see rb_source_facetoface_events::post_config() as a sample
     *
     * @param $requiredjoins array
     * @return bool
     */
    protected function add_audiencevisibility_joins(&$requiredjoins) {
        $requiredjoins[] = new \rb_join(
            'ctx',
            'INNER',
            '{context}',
            'ctx.instanceid = course.id AND ctx.contextlevel = ' . CONTEXT_COURSE,
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            'course'
        );

        return true;
    }
}