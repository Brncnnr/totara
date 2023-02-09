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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\template;

class learner_course_completion extends base {

    /**
     * @var array The report content settings.
     */
    public $contentsettings;

    public function __construct() {
        parent::__construct();

        $this->fullname        = get_string('template:leaner_course_completion:title', 'rb_source_course_completion');
        $this->shortname       = 'learner_course_completion';
        $this->summary         = get_string('template:leaner_course_completion:summary', 'rb_source_course_completion');
        $this->source          = 'course_completion';
        $this->label           = get_string('template:leaner_course_completion:label', 'rb_source_course_completion');
        $this->contentmode     = REPORT_BUILDER_CONTENT_MODE_ALL;
    }

    /**
     * The defined columns
     *
     * @return array
     */
    protected function define_columns() : array {
        $columns = array(
            array(
                'type' => 'course_completion',
                'value' => 'status',
                'heading' => get_string('template:leaner_course_completion:status', 'rb_source_course_completion'),
                'customheading' => 1,
            ),
            array(
                'type' => 'course',
                'value' => 'id',
                'heading' => get_string('template:leaner_course_completion:numberofcourses', 'rb_source_course_completion'),
                'customheading' => 1,
                'aggregate' => 'countdistinct',
            ),
        );

        return $columns;
    }

    /**
     * The defined content settings
     *
     * @return array
     */
    protected function define_contentsettings() : array {
        $contentsettings = array(
            'user' => array(
                'enable' => 1,
                'who' => \totara_reportbuilder\rb\content\user::USER_OWN
            ),
        );

        return $contentsettings;
    }

    /**
     * Define the graph data
     *
     * @return array
     */
    protected function define_graph() : array {
        return array(
            'type' => 'progress',
            'category' => 'course_completion-status',
            'series' => json_encode(array('course-id')),
        );
    }
}
