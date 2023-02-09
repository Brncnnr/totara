<?php
/**
 *
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 *
 */

use core\entity\user;
use mod_perform\rb\util;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/rb_sources/rb_source_user.php');

/**
 * Performance reporting user report.
 *
 * This is an extension of the rb_source_user source but with additional capability checks applied.
 *
 * Class rb_source_perform_response_user
 */
class rb_source_perform_response_user extends rb_source_user {

    protected $conditions_added = false;

    /**
     * Constructor.
     *
     * @param mixed $groupid
     * @param rb_global_restriction_set|null $globalrestrictionset
     * @throws coding_exception
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        global $CFG;
        parent::__construct($groupid, $globalrestrictionset);

        // This source is not available for user selection - it is used by the embedded report only.
        $this->selectable = false;

        $this->sourcetitle = get_string('sourcetitle', 'rb_source_perform_response_user');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_perform_response_user');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_perform_response_user');

        $this->usedcomponents[] = 'mod_perform';

        // Remove guest user from this source.
        $guest_user_id = $CFG->siteguest;
        $this->sourcewhere = "base.id <> :guest_user_id";
        $this->sourceparams = ['guest_user_id' => $guest_user_id];
    }

    protected function define_columnoptions() {
        global $DB;
        $columnoptions = parent::define_columnoptions();

        $usednamefields = totara_get_all_user_name_fields_join('base', null, true);
        $allnamefields = totara_get_all_user_name_fields_join('base');

        $columnoptions[] = new rb_column_option(
            'user',
            'name_linked_to_performance_reporting',
            get_string('name_linked_to_performance_reporting', 'rb_source_perform_response_user'),
            $DB->sql_concat_join("' '", $usednamefields),
            [
                'displayfunc' => 'name_linked_to_performance_reporting',
                'defaultheading' => get_string('userfullname', 'totara_reportbuilder'),
                'extrafields' => array_merge(
                    [
                        'id' => "base.id",
                        'deleted' => "base.deleted"
                    ],
                    $allnamefields
                ),
            ]
        );
        $columnoptions[] = new rb_column_option(
            'user',
            'user_performance_reporting_actions',
            null,
            "base.id",
            [
                'displayfunc' => 'user_performance_reporting_actions',
                'noexport' => true,
                'nosort' => true,
            ]
        );
        $columnoptions[] = new rb_column_option(
            'user',
            'user_performance_emailunobscured',
            get_string('user_email_unobscured_no_cap_checks', 'mod_perform'),
            'base.email',
            array(
                'displayfunc' => 'user_email_unobscured',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
            )
        );

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = parent::define_filteroptions();

        $filteroptions[] = new rb_filter_option(
            'user',
            'user_performance_emailunobscured',
            get_string('user_email_unobscured_no_cap_checks', 'mod_perform'),
            'text'
        );

        return $filteroptions;
    }

    public function post_config(reportbuilder $report) {
        // Make sure the conditions are only added once
        if ($this->conditions_added) {
            return;
        }

        [$cap_where, $cap_params] = util::get_report_on_subjects_sql(
            $report->reportfor,
            "base.id",
            "user_context"
        );

        $sql = "
            EXISTS (
                SELECT si.id
                FROM {perform_subject_instance} si
                JOIN {context} user_context ON si.subject_user_id = user_context.instanceid 
                    AND user_context.contextlevel = ".CONTEXT_USER."
                WHERE si.subject_user_id = base.id
                    AND {$cap_where} 
            )
        ";

        $this->sourcewhere .= " AND {$sql}";
        $this->sourceparams = array_merge($this->sourceparams, $cap_params);

        $this->conditions_added = true;
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return advanced_feature::is_disabled('performance_activities');
    }

    /**
     * Inject column_test data into database.
     *
     * @param totara_reportbuilder_column_testcase $testcase
     */
    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
        }

        $perform_generator = \mod_perform\testing\generator::instance();
        $perform_generator->create_subject_instance([
            'activity_name' => 'Weekly catchup',
            'subject_is_participating' => true,
            'subject_user_id' => user::repository()->get()->last()->id,
        ]);
    }
}
