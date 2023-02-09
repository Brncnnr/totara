<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 */

defined('MOODLE_INTERNAL') || die();

class rb_competency_assignment_users_embedded extends rb_base_embedded {

    public function __construct() {
        $this->url = '/totara/competency/assignments/users.php';
        $this->source = 'competency_assignment_users';
        $this->shortname = 'competency_assignment_users';
        $this->fullname = get_string('sourcetitle', 'rb_source_competency_assignment_users');

        $this->columns = $this->define_columns();
        $this->filters = $this->define_filters();

        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        $this->defaultsortcolumn = 'id';
        $this->defaultsortorder = SORT_ASC;

        parent::__construct();
    }

    public function embedded_global_restrictions_supported() {
        return true;
    }

    protected function define_columns() {
        $columns = [
            [
                'type' => 'user',
                'value' => 'namelink',
                'heading' => null
            ],
            [
                'type' => 'competency',
                'value' => 'competencylink',
                'heading' => null
            ],
            [
                'type' => 'assignment',
                'value' => 'assignment_type',
                'heading' => null
            ],
            [
                'type' => 'assignment',
                'value' => 'user_group',
                'heading' => null
            ],
            [
                'type' => 'assignment_created_by',
                'value' => 'namelink',
                'heading' => get_string('label_assignment_created_by', 'rb_source_competency_assignment_users')
            ],
            [
                'type' => 'assignment',
                'value' => 'created_at',
                'heading' => null
            ]
        ];

        return $columns;
    }

    protected function define_filters() {
        $filters = [
            [
                'type' => 'user',
                'value' => 'fullname',
            ],
            [
                'type' => 'competency',
                'value' => 'fullname',
            ],
            [
                'type' => 'assignment',
                'value' => 'assignment_type',
            ],
            [
                'type' => 'assignment',
                'value' => 'created_at',
                'advanced' => 1
            ],
            [
                'type' => 'competency',
                'value' => 'type_id',
                'advanced' => 1
            ],
            [
                'type' => 'cohort',
                'value' => 'name',
                'advanced' => 1
            ],
            [
                'type' => 'cohort',
                'value' => 'idnumber',
                'advanced' => 1
            ],
            [
                'type' => 'position',
                'value' => 'type_id',
                'advanced' => 1
            ],
            [
                'type' => 'position',
                'value' => 'name',
                'advanced' => 1
            ],
            [
                'type' => 'position',
                'value' => 'idnumber',
                'advanced' => 1
            ],
            [
                'type' => 'organisation',
                'value' => 'type_id',
                'advanced' => 1
            ],
            [
                'type' => 'organisation',
                'value' => 'name',
                'advanced' => 1
            ],
            [
                'type' => 'organisation',
                'value' => 'idnumber',
                'advanced' => 1
            ],
        ];

        return $filters;
    }

    public function is_capable($reportfor, $report) {
        $context = context_system::instance();
        return (
            has_any_capability(['totara/competency:view_assignments', 'totara/competency:manage_assignments'], $context, $reportfor)
            && has_capability('moodle/user:viewdetails', $context, $reportfor)
        );
    }
}
