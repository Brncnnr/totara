<?php
/*
 * This file is part of Totara Learn
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
 * @author Petr Skoda <petr.skoda@totaralearn.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

/**
 * A report builder source for the the saved searches.
 */
class rb_source_saved_report_searches extends rb_base_source {

    use \totara_reportbuilder\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;

    /**
     * Constructor
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $this->base = '{report_builder_saved}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();

        // Pull in all report related info via a trait.
        $this->add_report(new rb_join(
            'report',
            'INNER',
            "{report_builder}",
            'base.reportid = report.id',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        ));

        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = [];
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_saved_report_searches');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_saved_report_searches');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_saved_report_searches');

        $this->cacheable = false;

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    /**
     * Creates the array of rb_join objects required for this->joinlist
     *
     * @return array
     */
    protected function define_joinlist() {
        $joinlist = [];

        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');

        return $joinlist;
    }

    /**
     * Creates the array of rb_column_option objects required for
     * $this->columnoptions
     *
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = [];

        $columnoptions[] = new \rb_column_option(
            'saved',
            'name',
            get_string('searchname', 'totara_reportbuilder'),
            "base.name",
            array(
                'displayfunc' => 'format_string',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
            )
        );

        $columnoptions[] = new \rb_column_option(
            'saved',
            'ispublic',
            get_string('shared', 'totara_reportbuilder'),
            "base.ispublic",
            array(
                'displayfunc' => 'yes_or_no',
            )
        );

        $columnoptions[] = new \rb_column_option(
            'saved',
            'timemodified',
            get_string('timemodified', 'totara_reportbuilder'),
            "base.timemodified",
            array(
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            )
        );

        $columnoptions[] = new \rb_column_option(
            'saved',
            'scheduledcount',
            get_string('numscheduled', 'totara_reportbuilder'),
            "(SELECT COUNT('x')
                FROM {report_builder_schedule}
               WHERE {report_builder_schedule}.reportid = base.reportid AND {report_builder_schedule}.savedsearchid = base.id)",
            array(
                'issubquery' => true,
                'displayfunc' => 'integer'
            )
        );

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions
     * @return array
     */
    protected function define_filteroptions() {
        global $CFG;
        $filteroptions = [];

        $filteroptions[] = new \rb_filter_option(
            'saved',
            'name',
            get_string('searchname', 'totara_reportbuilder'),
            'text'
        );

        $filteroptions[] = new \rb_filter_option(
            'saved',
            'ispublic',
            get_string('shared', 'totara_reportbuilder'),
            'select',
            array(
                'selectchoices' => array(0 => get_string('no'), 1 => get_string('yes')),
                'simplemode' => true,
            )
        );

        $filteroptions[] = new \rb_filter_option(
            'saved',
            'timemodified',
            get_string('timemodified', 'totara_reportbuilder'),
            'date',
            array(
                'includetime' => true,
                'includenotset' => true,
            )
        );

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions);

        return $filteroptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = [
            [
                'type' => 'saved',
                'value' => 'name'
            ],
            [
                'type' => 'saved',
                'value' => 'ispublic'
            ],
            [
                'type' => 'report',
                'value' => 'namelinkview'
            ],
            [
                'type' => 'saved',
                'value' => 'scheduledcount'
            ],
            [
                'type' => 'user',
                'value' => 'fullname'
            ],
        ];

        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = [
            [
                'type' => 'saved',
                'value' => 'name'
            ],
            [
                'type' => 'saved',
                'value' => 'ispublic'
            ],
            [
                'type' => 'report',
                'value' => 'name'
            ],
            [
                'type' => 'user',
                'value' => 'fullname'
            ],
        ];

        return $defaultfilters;
    }

    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = [];

        $contentoptions[] = new rb_content_option(
            'report_access',
            get_string('access', 'totara_reportbuilder'),
            'report.id',
            'report'
        );

        $contentoptions[] = new rb_content_option(
            'saved_search_access',
            get_string('savedsearchaccess', 'totara_reportbuilder'),
            'base.id',
            'base'
        );

        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    protected function define_paramoptions() {
        return [];
    }

    /**
     * Returns expected result for column_test.
     *
     * @codeCoverageIgnore
     * @param rb_column_option $columnoption
     * @return int
     */
    public function phpunit_column_test_expected_count($columnoption) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_expected_count() cannot be used outside of unit tests');
        }
        return 0;
    }
}
