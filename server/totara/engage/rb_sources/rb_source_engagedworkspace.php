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

use container_workspace\discussion\discussion;
use container_workspace\totara_engage\share\recipient\library;
use totara_core\advanced_feature;

/**
 * Engage content is management interface for engage workspaces
 */
class rb_source_engagedworkspace extends rb_base_source {
    use \totara_reportbuilder\rb\source\report_trait;
    use \core_course\rb\source\report_trait;

    /**
     * rb_source_engagedworkspace constructor.
     * @param $groupid
     * @param rb_global_restriction_set|null $globalrestrictionset
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'user_id');

        $this->usedcomponents[] = 'totara_engage';
        $this->usedcomponents[] = 'container_workspace';

        $this->base = '{workspace}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_engagedworkspace');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_engagedworkspace');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_engagedworkspace');

        $this->sourcewhere = '(base.to_be_deleted = 0)';

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
        $joinlist = [];

        $this->add_context_tables($joinlist, 'base', 'course_id', CONTEXT_COURSE, 'INNER');
        $this->add_core_course_tables($joinlist, 'base', 'course_id', 'INNER');
        $this->add_core_user_tables($joinlist, 'base','user_id');

        return $joinlist;
    }

    /**
     * @return array
     */
    protected function define_columnoptions() {
        global $DB;
        $columnoptions = [];

        $this->add_core_user_columns($columnoptions);

        $columnoptions[] = new rb_column_option(
            'engagedworkspace',
            'title',
            get_string('title', 'rb_source_engagedworkspace'),
            'course.fullname',
            [
                'displayfunc' => 'engagedworkspace_titlelink',
                'dbdatatype' => 'char',
                'outputformat' => 'html',
                'joins' => 'course',
                'extrafields' => [
                    'workspace_id' => "base.course_id",
                    'course_visible' => "course.visible",
                    'course_audiencevisible' => "course.audiencevisible"
                ]
            ]
        );

        $columnoptions[] = new rb_column_option(
            'engagedworkspace',
            'discussions',
            get_string('discussions', 'rb_source_engagedworkspace'),
            '(SELECT COUNT(wd.id) FROM {workspace_discussion} wd
            WHERE wd.course_id = base.course_id)',
            [
                'displayfunc' => 'integer',
                'dbdatatype' => 'integer',
                'iscompound' => true,
                'issubquery' => true,
                'disableaggregation' => true
            ]
        );

        $area = discussion::AREA;
        $component = 'container_workspace';
        $columnoptions[] = new rb_column_option(
            'engagedworkspace',
            'commentsindiscussions',
            get_string('commentsindiscussions', 'rb_source_engagedworkspace'),
            "(
            SELECT COUNT(tc.id) FROM {totara_comment} tc
            INNER JOIN {workspace_discussion} wd
            ON wd.id = tc.instanceid WHERE wd.course_id = base.course_id
            AND (tc.area = '{$area}' OR tc.component = '{$component}')
            AND tc.parentid IS NULL
            )",
            [
                'displayfunc' => 'integer',
                'dbdatatype' => 'integer',
                'iscompound' => true,
                'issubquery' => true,
                'disableaggregation' => true
            ]
        );

        if (advanced_feature::is_enabled('engage_resources')) {
            $area = library::AREA;
            $columnoptions[] = new rb_column_option(
                'engagedworkspace',
                'playlists',
                get_string('playlists', 'rb_source_engagedworkspace'),
                "(
                SELECT COUNT(r.id)
                FROM {playlist} p
                INNER JOIN {engage_share} s
                ON p.id = s.itemid
                INNER JOIN {engage_share_recipient} r
                ON s.id = r.shareid
                WHERE r.instanceid = base.course_id
                AND (r.area = '{$area}'
                OR r.component = '{$component}')
                )",
                [
                    'displayfunc' => 'integer',
                    'dbdatatype' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                    'disableaggregation' => true
                ]
            );

            $columnoptions[] = new rb_column_option(
                'engagedworkspace',
                'resources',
                get_string('resources', 'rb_source_engagedworkspace'),
                "(
                SELECT COUNT(r.id)
                FROM {engage_resource} er
                INNER JOIN {engage_share} s
                ON er.id = s.itemid
                INNER JOIN {engage_share_recipient} r
                ON s.id = r.shareid
                WHERE r.instanceid = base.course_id
                AND (r.area = '{$area}'
                OR r.component = '{$component}')
                )",
                [
                    'displayfunc' => 'integer',
                    'dbdatatype' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                    'disableaggregation' => true
                ]
            );
        }

        $area = discussion::AREA;
        $comment_area = \totara_comment\comment::COMMENT_AREA;
        $reply_area = \totara_comment\comment::REPLY_AREA;
        $comment_component = 'totara_comment';
        $columnoptions[] = new rb_column_option(
            'engagedworkspace',
            'files',
            get_string('files', 'rb_source_engagedworkspace'),
            "(
            SELECT COUNT(*) FROM (
            SELECT wd.course_id FROM {files} f
            INNER JOIN {workspace_discussion} wd
            ON f.itemid = wd.id
            AND (f.component = '{$component}' OR f.filearea = '{$area}')
            AND (f.license IS NOT NULL OR f.source IS NOT NULL OR f.mimetype IS NOT NULL)
            UNION ALL
            SELECT wd.course_id FROM {files} f
            INNER JOIN {totara_comment}  tc
            ON tc.id = f.itemid
            INNER JOIN {workspace_discussion} wd
            ON tc.instanceid = wd.id
            WHERE f.component = '{$comment_component}' AND  (f.filearea = '{$comment_area}' OR f.filearea = '{$reply_area}')
            AND (f.license IS NOT NULL OR f.source IS NOT NULL OR f.mimetype IS NOT NULL)
            ) AS files WHERE files.course_id = base.course_id
            )",
            [
                'displayfunc' => 'integer',
                'dbdatatype' => 'integer',
                'iscompound' => true,
                'issubquery' => true,
                'disableaggregation' => true
            ]
        );

        $columnoptions[] = new rb_column_option(
            'engagedworkspace',
            'visibility',
            get_string('visibility', 'rb_source_engagedworkspace'),
            'CASE
            WHEN course.visible = 0 AND base.private = 1 THEN 2
            WHEN base.private = 1 AND course.visible = 1 THEN 1
            ELSE 0
            END',
            [
                'displayfunc' => 'engagedworkspace_visibility',
                'dbdatatype' => 'integer',
                'outputformat' => 'text',
                'joins' => 'course',
                'nosort' => true
            ]
        );

        $usednamefields = totara_get_all_user_name_fields_join('auser');
        $columnoptions[] = new \rb_column_option(
            'engagedworkspace',
            'owner',
            get_string('owner', 'rb_source_engagedworkspace'),
            $DB->sql_concat_join("' '", $usednamefields),
            [
                'joins' => 'auser',
                'displayfunc' => 'workspace_ownerlink',
                'extrafields' => array_merge(['id' => 'base.user_id', 'deleted' => "auser.deleted"], $usednamefields),
            ]
        );

        $columnoptions[] = new rb_column_option(
            'engagedworkspace',
            'members',
            get_string('members', 'rb_source_engagedworkspace'),
            '(
            SELECT COUNT(ue.userid) FROM {user_enrolments} ue
            INNER JOIN {enrol} e ON ue.enrolid = e.id
            INNER JOIN {user} u ON u.id = ue.userid
            WHERE e.courseid = base.course_id
            )',
            [
                'displayfunc' => 'integer',
                'dbdatatype' => 'integer',
                'iscompound' => true,
                'issubquery' => true,
                'disableaggregation' => true
            ]
        );

        return $columnoptions;
    }

    /**
     * @return array|string[]
     */
    protected function get_source_joins(): array {
        return ['auser'];
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
                'type' => 'engagedworkspace',
                'value' => 'title',
                'heading' => get_string('title', 'rb_source_engagedworkspace')
            ],
            [
                'type' => 'engagedworkspace',
                'value' => 'discussions',
                'heading' => get_string('discussions', 'rb_source_engagedworkspace')
            ],
            [
                'type' => 'engagedworkspace',
                'value' => 'commentsindiscussions',
                'heading' => get_string('commentsindiscussions', 'rb_source_engagedworkspace')
            ],
            [
                'type' => 'engagedworkspace',
                'value' => 'files',
                'heading' => get_string('files', 'rb_source_engagedworkspace')
            ],
            [
                'type' => 'engagedworkspace',
                'value' => 'owner',
                'heading' => get_string('owner', 'rb_source_engagedworkspace')
            ],
            [
                'type' => 'engagedworkspace',
                'value' => 'members',
                'heading' => get_string('members', 'rb_source_engagedworkspace')
            ],
        ];

        if (advanced_feature::is_enabled('engage_resources')) {
            $cols[] = [
                'type' => 'engagedworkspace',
                'value' => 'playlists',
                'heading' => get_string('playlists', 'rb_source_engagedworkspace')
            ];

            $cols[] = [
                'type' => 'engagedworkspace',
                'value' => 'resources',
                'heading' => get_string('resources', 'rb_source_engagedworkspace')
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
        $contentoptions = array();

        $contentoptions[] = new rb_content_option(
            'course_visibility',
            get_string('course_visibility', 'totara_reportbuilder'),
            'base.course_id',
            ['ctx', 'course']
        );

        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    /**
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = [];

        $filteroptions[] = new rb_filter_option(
            'engagedworkspace',
            'visibility',
            get_string('visibility', 'rb_source_engagedworkspace'),
            'multicheck',
            [
                'selectfunc' => 'visibility',
                'simplemode' => true
            ]
        );

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
            [
                'type' => 'engagedworkspace',
                'value' => 'visibility',
                'advanced' => 0,
            ]
        ];
    }

    /**
     * Source specific filter display methods.
     *
     * @return array
     */
    public function rb_filter_visibility(): array {
        return [
            0 => get_string('visibility_public', 'rb_source_engagedworkspace'),
            1 => get_string('visibility_private', 'rb_source_engagedworkspace'),
            2 => get_string('visibility_hidden', 'rb_source_engagedworkspace')
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
        return 0;
    }

    /**
     * Report is not usable without engage features
     * @return bool
     */
    public static function is_source_ignored() {
        return advanced_feature::is_disabled('container_workspace');
    }
}
