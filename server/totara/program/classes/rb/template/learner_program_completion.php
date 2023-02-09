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
 * @package totara_program
 */

namespace totara_program\rb\template;
use \totara_reportbuilder\rb\template\base;

class learner_program_completion extends base {

    public function __construct() {
        parent::__construct();

        $this->fullname        = get_string('template:learner_program_completion:title', 'rb_source_program_completion');
        $this->shortname       = 'learner_program_completion';
        $this->summary         = get_string('template:learner_program_completion:summary', 'rb_source_program_completion');
        $this->source          = 'program_completion';
        $this->label           = get_string('template:learner_program_completion:label', 'rb_source_program_completion');
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
                'type' => 'progcompletion',
                'value' => 'status',
                'heading' => get_string('template:learner_program_completion:status', 'rb_source_program_completion'),
                'customheading' => 1,
            ),
            array(
                'type' => 'prog',
                'value' => 'id',
                'heading' => get_string('template:learner_program_completion:numberofprograms', 'rb_source_program_completion'),
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
            'type' => 'doughnut',
            'category' => 'progcompletion-status',
            'series' => json_encode(array('prog-id')),
        );
    }
}
