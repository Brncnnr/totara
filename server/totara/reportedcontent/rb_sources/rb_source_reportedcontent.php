<?php
/**
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_reportedcontent
 */
use totara_core\advanced_feature;
use totara_reportedcontent\review;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Reported content is management interface for inappropriate content reports
 */
class rb_source_reportedcontent extends rb_base_source {
    use \totara_job\rb\source\report_trait;

    /**
     * rb_source_reportedcontent constructor.
     *
     * @param $groupid
     * @param rb_global_restriction_set|null $globalrestrictionset
     * @throws ReportBuilderException
     * @throws coding_exception
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        global $DB;

        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'reviewer_id');

        $this->usedcomponents[] = 'totara_reportedcontent';

        $this->base = '{totara_reportedcontent}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_reportedcontent');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_reportedcontent');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_reportedcontent');

        // Filter results based on advanced features
        $exclude = [];
        if (advanced_feature::is_disabled('engage_resources')) {
            $exclude[] = 'engage_article';
            $exclude[] = 'engage_survey';
            $exclude[] = 'totara_playlist';
        }
        if (advanced_feature::is_disabled('container_workspace')) {
            $exclude[] = 'container_workspace';
        }

        if (!empty($exclude)) {
            [$sql, $params] = $DB->sql_not_in($exclude);
            $this->sourcewhere = 'component ' . $sql;
            $this->sourceparams = $params;
        }

        $this->cacheable = false;

        parent::__construct();
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return advanced_feature::is_disabled('engage_resources') &&
            advanced_feature::is_disabled('container_workspace');
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    /**
     * @return array
     */
    protected function define_joinlist() {
        $joinlist = [];

        $this->add_core_user_tables($joinlist, 'base', 'target_user_id', 'auser');
        $this->add_core_user_tables($joinlist, 'base', 'complainer_id', 'cuser');
        $this->add_core_user_tables($joinlist, 'base', 'reviewer_id', 'ruser');

        $this->add_totara_job_tables($joinlist, 'base', 'target_user_id');

        return $joinlist;
    }

    /**
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = [];

        // Include some standard columns.
        $this->add_core_user_columns($columnoptions, 'auser', 'creator');
        $this->add_totara_job_columns($columnoptions);

        // Report specific columns.
        $columnoptions[] = new rb_column_option(
            'reportedcontent',
            'time_created',
            get_string('timecreated', 'rb_source_reportedcontent'),
            'base.time_created',
            [
                'joins' => 'base',
                'outputformat' => 'text',
                'displayfunc' => 'reportedcontent_datetime',
                'dbdatatype' => 'timestamp',
            ]
        );

        $columnoptions[] = new rb_column_option(
            'reportedcontent',
            'time_content',
            get_string('timecontent', 'rb_source_reportedcontent'),
            'base.time_content',
            [
                'joins' => 'base',
                'outputformat' => 'text',
                'displayfunc' => 'reportedcontent_datetime',
                'dbdatatype' => 'timestamp',
            ]
        );

        $columnoptions[] = new rb_column_option(
            'reportedcontent',
            'time_reviewed',
            get_string('timereviewed', 'rb_source_reportedcontent'),
            'base.time_reviewed',
            [
                'joins' => 'base',
                'outputformat' => 'text',
                'displayfunc' => 'reportedcontent_datetime',
                'dbdatatype' => 'timestamp',
            ]
        );

        $columnoptions[] = new rb_column_option(
            'reportedcontent',
            'content',
            get_string('content', 'rb_source_reportedcontent'),
            'base.content',
            [
                'nosort' => true,
                'dbdatatype' => 'char',
                'outputformat' => 'html',
                'displayfunc' => 'reportedcontent_content',
                'extrafields' => array(
                    'format' => 'base.format',
                    'context_id' => 'base.context_id',
                    'component' => 'base.component',
                    'area' => 'base.area',
                    'item_id' => 'base.item_id',
                ),
            ]
        );

        $columnoptions[] = new rb_column_option(
            'reportedcontent',
            'url',
            get_string('url', 'rb_source_reportedcontent'),
            'base.url',
            [
                'nosort' => true,
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'reportedcontent_link'
            ]
        );

        // Status is shown with the actions/decision column, but still used for filtering
        $columnoptions[] = new rb_column_option(
            'reportedcontent',
            'status',
            get_string('status', 'rb_source_reportedcontent'),
            "base.status",
            [
                'hidden' => 1,
                'selectable' => false,
            ]
        );

        $columnoptions[] = new rb_column_option(
            'reportedcontent',
            'action',
            get_string('action', 'rb_source_reportedcontent'),
            'base.id',
            array(
                'displayfunc' => 'reportedcontent_actions',
                'nosort' => true,
                'noexport' => true,
                'extrafields' => array(
                    'status' => "base.status",
                ),
            )
        );

        $this->add_core_user_columns($columnoptions, 'cuser', 'complainer');
        $this->add_core_user_columns($columnoptions, 'ruser', 'reviewer');

        return $columnoptions;
    }

    /**
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array();

        $this->add_core_user_filters($filteroptions, 'creator');
        $this->add_core_user_filters($filteroptions, 'complainer');
        $this->add_core_user_filters($filteroptions, 'reviewer');
        $this->add_totara_job_filters($filteroptions, 'base', 'target_user_id');

        $filteroptions[] = new rb_filter_option(
            'reportedcontent',
            'status',
            get_string('status', "rb_source_reportedcontent"),
            'select',
            array(
                'selectfunc' => 'status',
                'attributes' => rb_filter_option::select_width_limiter(),
                'simplemode' => false,
                'noanychoice' => false,
            )
        );

        return $filteroptions;
    }

    /**
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    /**
     * @return array
     */
    protected function define_paramoptions() {
        return array();
    }

    /**
     * @return array
     */
    protected function define_defaultcolumns() {
        $defaultcolumns = array();
        $defaultcolumns[] = array('type' => 'creator', 'value' => 'fullname');
        $defaultcolumns[] = array('type' => 'reportedcontent', 'value' => 'content');
        $defaultcolumns[] = array('type' => 'reportedcontent', 'value' => 'url');
        $defaultcolumns[] = array('type' => 'reportedcontent', 'value' => 'time_created');
        $defaultcolumns[] = array('type' => 'reportedcontent', 'value' => 'action');
         return $defaultcolumns;
    }

    /**
     * @return array
     */
    protected function define_defaultfilters() {
        return array(
            array(
                'type' => 'creator',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'reportedcontent',
                'value' => 'status',
                'advanced' => 0,
            ),
        );
    }

    /**
     * @return array
     */
    protected function define_requiredcolumns() {
        return array();
    }

    /**
     * @return array
     */
    public function get_required_jss() {
        global $PAGE;
        $PAGE->requires->js_call_amd('totara_reportedcontent/report', 'init');
        return [];
    }

    /**
     * Source specific filter display methods.
     *
     * @return array
     */
    public function rb_filter_status() {
        return array(
            review::DECISION_PENDING => get_string('status_pending', 'rb_source_reportedcontent'),
            review::DECISION_REMOVE => get_string('status_removed', 'rb_source_reportedcontent'),
            review::DECISION_APPROVE => get_string('status_approved', 'rb_source_reportedcontent'),
        );
    }

    /**
     * Returns expected result for column_test.
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
