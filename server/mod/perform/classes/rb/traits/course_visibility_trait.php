<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package mod_perform
 */

namespace mod_perform\rb\traits;

use rb_column;
use rb_join;
use reportbuilder;
use totara_reportbuilder\rb\source\report_trait;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/totara/reportbuilder/lib.php");

/**
 * Trait activity_trait
 */
trait course_visibility_trait {

    use report_trait;

    /**
     * Add required columns and joins for course visibility
     *
     * @param string $activity_join name of the join for the perform table
     */
    protected function add_course_visibility(string $activity_join): void {
        $this->requiredjoins[] = new rb_join(
            'course',
            'INNER',
            '{course}',
            'perform.course = course.id',
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            $activity_join
        );

        $this->add_context_tables($this->requiredjoins, 'course', 'id', CONTEXT_COURSE, 'INNER');
    }

    /**
     * Add the columns required for the visibility checks
     *
     * @return array
     */
    protected function define_requiredcolumns() {
        return array();
    }

    /**
     * Create the restrictions for course visibility to be used in post_config. Can pass already existing
     * restrictions and both will be combined.
     *
     * @param reportbuilder $report
     * @param array $restrictions
     * @return array
     */
    protected function create_course_visibility_restrictions(reportbuilder $report, array $restrictions = []): array {
        [$visibility_sql, $visibility_params] = $report->post_config_visibility_where('course', 'course');
        if (empty($restrictions)) {
            return [$visibility_sql, $visibility_params];
        }

        [$other_restrictions_sql, $other_restrictions_params] = $restrictions;

        $where_sql = "({$visibility_sql}) AND ({$other_restrictions_sql})";
        $where_params = array_merge($visibility_params, $other_restrictions_params);

        return [$where_sql, $where_params];
    }

}
