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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_engage
 */

defined('MOODLE_INTERNAL') || die();

use container_workspace\totara_engage\share\recipient\library;
use totara_core\advanced_feature;
use totara_engage\access\access;

/**
 * Engaged users is management interface for engaged users
 */
class rb_source_engagedusers extends rb_base_source {
    use totara_job\rb\source\report_trait;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        global $CFG;
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'id');

        $this->usedcomponents[] = 'totara_engage';

        $this->base = '(SELECT id FROM {user})';

        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_engagedusers');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_engagedusers');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_engagedusers');

        $this->sourcewhere = 'base.id <> :userid';
        // Get rid of guest user.
        $this->sourceparams = ['userid' => $CFG->siteguest];
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
     * @return array
     */
    protected function define_joinlist() {
        global $DB;
        $joinlist = [];

        $joinlist[] = new rb_join(
            'workspacelist',
            'LEFT',
            '(
                SELECT
                ue.userid,
                ' . $DB->sql_group_concat($DB->sql_cast_2char('c.fullname'), ", <br/>") . ' AS workspaces,
                ' . $DB->sql_group_concat('c.id', ',') . ' AS courseids
                FROM {user_enrolments} ue
                LEFT JOIN {enrol} e ON ue.enrolid = e.id
                LEFT JOIN {course} c ON c.id = e.courseid
                WHERE ue.status = 0 AND e.status = 0
                GROUP BY ue.userid
            )',
            'workspacelist.userid = base.id',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );

        $this->add_core_user_tables($joinlist, 'base','id');

        return $joinlist;
    }

    /**
     * @return array
     */
    protected function define_columnoptions() {
        global $DB;
        $columnoptions = [];

        $has_resources = advanced_feature::is_enabled('engage_resources');
        $has_workspaces = advanced_feature::is_enabled('container_workspace');

        $this->add_core_user_columns($columnoptions);

        $usednamefields = totara_get_all_user_name_fields_join('auser');
        $columnoptions[] = new \rb_column_option(
            'engagedusers',
            'creator',
            get_string('creator', 'rb_source_engagedusers'),
            $DB->sql_concat_join("' '", $usednamefields),
            [
                'joins' => 'auser',
                'displayfunc' => 'engagedusers_namelink',
                'extrafields' => array_merge(['id' => "base.id", 'deleted' => "auser.deleted"], $usednamefields),
                'outputformat' => 'html'
            ]
        );

        if ($has_resources) {
            $columnoptions[] = new rb_column_option(
                'engagedusers',
                'created_resource',
                get_string('createdresource', 'rb_source_engagedusers'),
                "(SELECT COUNT(r.id) FROM {engage_resource} r
                WHERE r.userid = base.id)",
                [
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'text',
                    'iscompound' => true
                ]
            );

            $public = access::PUBLIC;
            $columnoptions[] = new rb_column_option(
                'engagedusers',
                'public_resource',
                get_string('publicresource', 'rb_source_engagedusers'),
                "(SELECT COUNT(r.id) FROM {engage_resource} r
                WHERE r.userid = base.id AND r.access = '{$public}')",
                [
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'text',
                    'iscompound' => true
                ]
            );

            $private = access::PRIVATE;
            $columnoptions[] = new rb_column_option(
                'engagedusers',
                'private_resource',
                get_string('privateresource', 'rb_source_engagedusers'),
                "(SELECT COUNT(r.id) FROM {engage_resource} r
                WHERE r.userid = base.id AND r.access = '{$private}')",
                [
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'text',
                    'iscompound' => true
                ]
            );

            $restricted = access::RESTRICTED;
            $columnoptions[] = new rb_column_option(
                'engagedusers',
                'restricted_resource',
                get_string('restrictedresource', 'rb_source_engagedusers'),
                "(SELECT COUNT(r.id) FROM {engage_resource} r
                WHERE r.userid = base.id AND r.access = '{$restricted}')",
                [
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'text',
                    'iscompound' => true
                ]
            );

            $playlist = 'totara_playlist';
            $artcle = 'engage_article';
            $columnoptions[] = new rb_column_option(
                'engagedusers',
                'created_comment',
                get_string('createdcomment', 'rb_source_engagedusers'),
                "(
                SELECT COUNT(c.id) FROM {totara_comment} c
                INNER JOIN {engage_resource} r ON c.instanceid = r.id
                WHERE r.userid <> base.id AND c.userid = base.id
                AND
                (c.component = '{$playlist}'OR c.component = '{$artcle}')
                )",
                [
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'text',
                    'iscompound' => true
                ]
            );

            $columnoptions[] = new rb_column_option(
                'engagedusers',
                'created_playlist',
                get_string('createdplaylist', 'rb_source_engagedusers'),
                "(
                SELECT COUNT(p.id) FROM {playlist} p
                WHERE p.userid = base.id
                )",
                [
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'text',
                    'iscompound' => true
                ]
            );
        }

        if ($has_workspaces && $has_resources) {
            $area = library::AREA;
            $component = 'container_workspace';
            $columnoptions[] = new rb_column_option(
                'engagedusers',
                'resource_in_workspace',
                get_string('resourceinworkspace', 'rb_source_engagedusers'),
                "(SELECT COUNT(r.id)
                FROM {engage_share_recipient} r
                INNER JOIN {engage_share} s
                ON s.id = r.shareid
                INNER JOIN {workspace} wo ON wo.course_id = r.instanceid
                AND wo.to_be_deleted = 0
                WHERE r.sharerid = base.id
                AND (r.area = '{$area}'
                OR r.component = '{$component}'))",
                [
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'text',
                    'iscompound' => true
                ]
            );
        }

        if ($has_workspaces) {
            $columnoptions[] = new rb_column_option(
                'engagedusers',
                'created_workspace',
                get_string('createdworkspace', 'rb_source_engagedusers'),
                "(SELECT COUNT(c.id) FROM {course} c
                INNER JOIN {workspace} o
                ON c.id = o.course_id
                AND o.to_be_deleted = 0
                WHERE o.user_id = base.id)",
                [
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'text',
                    'iscompound' => true
                ]
            );

            $columnoptions[] = new rb_column_option(
                'engagedusers',
                'memberofworkspace',
                get_string('memberworkspaces', 'rb_source_engagedusers'),
                'workspacelist.workspaces',
                [
                    'joins' => 'workspacelist',
                    'displayfunc' => 'engagedusers_workspacelink',
                    'dbdatatype' => 'text',
                    'outputformat' => 'html',
                    'extrafields' => [
                        'ids' => 'workspacelist.courseids'
                    ]
                ]
            );
        }

        return $columnoptions;
    }

    /**
     * Define the default columns for this report.
     *
     * @return array
     */
    protected function define_defaultcolumns() {
        return self::get_default_columns();
    }

    /**
     * The default columns for this and embedded reports.
     *
     * @return array
     */
    public static function get_default_columns() {
        $cols = [
            [
                'type' => 'engagedusers',
                'value' => 'creator',
                'heading' => get_string('creator', 'rb_source_engagedusers'),
            ]
        ];

        $has_resources = advanced_feature::is_enabled('engage_resources');
        $has_workspaces = advanced_feature::is_enabled('container_workspace');

        if ($has_resources) {
            $cols[] = [
                'type' => 'engagedusers',
                'value' => 'created_resource',
                'heading' => get_string('createdresource', 'rb_source_engagedusers')
            ];

            $cols[] = [
                'type' => 'engagedusers',
                'value' => 'public_resource',
                'heading' => get_string('publicresource', 'rb_source_engagedusers')
            ];

            $cols[] = [
                'type' => 'engagedusers',
                'value' => 'private_resource',
                'heading' => get_string('privateresource', 'rb_source_engagedusers')
            ];

            $cols[] = [
                'type' => 'engagedusers',
                'value' => 'restricted_resource',
                'heading' => get_string('restrictedresource', 'rb_source_engagedusers')
            ];

            $cols[] = [
                'type' => 'engagedusers',
                'value' => 'created_comment',
                'heading' => get_string('createdcomment', 'rb_source_engagedusers')
            ];

            $cols[] = [
                'type' => 'engagedusers',
                'value' => 'created_playlist',
                'heading' => get_string('createdplaylist', 'rb_source_engagedusers')
            ];
        }

        if ($has_workspaces && $has_resources) {
            $cols[] = [
                'type' => 'engagedusers',
                'value' => 'resource_in_workspace',
                'heading' => get_string('resourceinworkspace', 'rb_source_engagedusers')
            ];
        }

        if ($has_workspaces) {
            $cols[] = [
                'type' => 'engagedusers',
                'value' => 'created_workspace',
                'heading' => get_string('createdworkspace', 'rb_source_engagedusers')
            ];
        }

        return $cols;
    }

    /**
     * Define the available content options for this report.
     *
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = [];

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);
        return $contentoptions;
    }

    /**
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = [];

        $this->add_core_user_filters($filteroptions);
        return $filteroptions;
    }


    /**
     * @return array
     */
    protected function define_paramoptions() {
        return [];
    }

    /**
     * @return array
     */
    protected function define_requiredcolumns() {
        return [];
    }

    /**
     * @return array
     */
    protected function define_defaultfilters() {
        return self::get_default_filters();
    }

    /**
     * The default filters for this and embedded reports.
     *
     * @return array
     */
    public static function get_default_filters() {
        return [
            [
                'type' => 'user',
                'value' => 'fullname',
                'advanced' => 0
            ],
        ];
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
        return 1;
    }

    /**
     * Report is not usable without engage features
     * @return bool
     */
    public static function is_source_ignored() {
        return advanced_feature::is_disabled('engage_resources') &&
            advanced_feature::is_disabled('container_workspace');
    }
}