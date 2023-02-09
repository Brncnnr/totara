<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @package totara
 * @subpackage reportbuilder
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');

class rb_source_program extends rb_base_source {
    use \core_course\rb\source\report_trait;
    use \totara_cohort\rb\source\report_trait;
    use \totara_program\rb\source\program_trait;

    public function __construct() {
        $this->base = '{prog}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_program');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_program');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_program');
        list($this->sourcewhere, $this->sourceparams, $this->sourcejoins) = $this->define_source_args();
        $this->usedcomponents[] = "totara_program";
        $this->usedcomponents[] = 'totara_cohort';

        $this->cacheable = false;

        parent::__construct();
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return !advanced_feature::is_enabled('programs');
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return false;
    }

    protected function define_joinlist() {

        $joinlist = array(
            new rb_join(
                'ctx',
                'INNER',
                '{context}',
                'ctx.instanceid = base.id AND ctx.contextlevel = ' . CONTEXT_PROGRAM,
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
        );

        $this->add_core_course_category_tables($joinlist, 'base', 'category');
        $this->add_totara_cohort_program_tables($joinlist, 'base', 'id');

        return $joinlist;
    }

    protected function define_columnoptions() {

        // include some standard columns
        $this->add_totara_program_columns($columnoptions, 'base');
        $this->add_core_course_category_columns($columnoptions, 'course_category', 'base', 'programcount');
        $this->add_totara_cohort_program_columns($columnoptions);

        return $columnoptions;
    }


    protected function define_filteroptions() {
        $filteroptions = array();

        // include some standard filters
        $this->add_totara_program_filters($filteroptions);
        $this->add_core_course_category_filters($filteroptions);
        $this->add_totara_cohort_program_filters($filteroptions);

        return $filteroptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'programid',
                'base.id'
            ),
            new rb_param_option(
                'visible',
                'base.visible'
            ),
            new rb_param_option(
                'category',
                'base.category'
            ),
        );
        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'prog',
                'value' => 'proglinkicon',
            ),
            array(
                'type' => 'course_category',
                'value' => 'namelink',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'prog',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'course_category',
                'value' => 'path',
                'advanced' => 0,
            ),
        );
        return $defaultfilters;
    }

    /**
     * Generates the totara visibility where SQL and params.
     *
     * There get stored in private variables, and should only be accessed after calling this method.
     */
    protected function define_source_args() {
        list($sql, $params) =  totara_visibility_where(
            null, // Current user.
            'base.id',
            'base.visible',
            'base.audiencevisible',
            'base',
            'program',
            false
        );
        $joins = ['ctx'];

        // Remove certifications.
        $sql = "(base.certifid IS NULL) AND ({$sql})";

        return [$sql, $params, $joins];
    }

    protected function define_sourcewhere() {
        // There is no way to nicely get past this.
        // The source has fundamentally changed and if you had extended it then you will need to remake your customisation.
        throw new \coding_exception('\rb_source_program::define_sourcewhere is deprecated please upgrade your code to use define_source_args');
    }

}
