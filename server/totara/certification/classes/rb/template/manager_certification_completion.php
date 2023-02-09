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
 * @package totara_certification
 */

namespace totara_certification\rb\template;
use \totara_reportbuilder\rb\template\base;

class manager_certification_completion extends base {

    public function __construct() {
        parent::__construct();

        $this->fullname        = get_string('template:manager_certification_completion:title', 'rb_source_certification_completion');
        $this->shortname       = 'manager_certification_completion';
        $this->summary         = get_string('template:manager_certification_completion:summary', 'rb_source_certification_completion');
        $this->source          = 'certification_completion';
        $this->label           = get_string('template:manager_certification_completion:label', 'rb_source_certification_completion');
        $this->contentmode     = REPORT_BUILDER_CONTENT_MODE_ALL;
        $this->accessmode      = REPORT_BUILDER_ACCESS_MODE_ANY;
    }

    /**
     * The defined columns
     *
     * @return array
     */
    protected function define_columns() : array {
        $columns = array(
            array(
                'type' => 'certcompletion',
                'value' => 'status',
                'heading' => get_string('template:manager_certification_completion:status', 'rb_source_certification_completion'),
                'customheading' => 1,
            ),
            array(
                'type' => 'certif',
                'value' => 'id',
                'heading' => get_string('template:manager_certification_completion:numberofcertifications', 'rb_source_certification_completion'),
                'customheading' => 1,
                'aggregate' => 'countdistinct',
            ),
        );

        return $columns;
    }

    /**
     * The defined filters
     *
     * @return array
     */
    protected function define_filters() : array {
        $filters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'certif',
                'value' => 'fullname',
                'advanced' => 0,
            ),
        );

        return $filters;
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
                'who' => \totara_reportbuilder\rb\content\user::USER_DIRECT_REPORTS + \totara_reportbuilder\rb\content\user::USER_TEMP_REPORTS
            ),
        );

        return $contentsettings;
    }

    /**
     * The defined content settings
     *
     * @return array
     */
    protected function define_accesssettings() : array {
        global $CFG;

        $accesssettings = array(
            'role' => array(
                'enable' => 1,
                'activeroles' => $CFG->managerroleid,
                'context' => 'any',
            ),
        );

        return $accesssettings;
    }

    /**
     * Define the graph data
     *
     * @return array
     */
    protected function define_graph() : array {
        return array(
            'type' => 'doughnut',
            'category' => 'certcompletion-status',
            'series' => json_encode(array('certif-id')),
        );
    }
}
