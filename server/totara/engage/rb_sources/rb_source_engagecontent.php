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
 * Engage content is management interface for engage resources
 */
class rb_source_engagecontent extends rb_base_source {
    use totara_job\rb\source\report_trait;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $this->usedcomponents[] = 'totara_engage';

        $this->base = "{engage_resource}";

        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_engagecontent');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_engagecontent');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_engagecontent');

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

        $this->add_core_user_tables($joinlist, 'base','userid');

        $joinlist[] = new rb_join(
            'taglist',
            'LEFT',
            '(
                SELECT ti.itemid,
                ' . $DB->sql_group_concat('t.name', ' , ') . ' AS tagname
                FROM {tag_instance} ti
                INNER JOIN {tag} t ON t.id = ti.tagid
                WHERE itemtype = \'engage_resource\' GROUP BY ti.itemid
            )',
            'taglist.itemid = base.id',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );

        return $joinlist;
    }

    /**
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = [];

        $columnoptions[] = new rb_column_option(
            'engagecontent',
            'resource_name',
            get_string('resourcename', 'rb_source_engagecontent'),
            'base.name',
            [
                'displayfunc' => 'engagecontent_name',
                'dbdatatype' => 'char',
                'outputformat' => 'html',
                'extrafields' => [
                    'resourceid' => 'base.id',
                    'component' => 'base.resourcetype'
                ]
            ]
        );

        $columnoptions[] = new rb_column_option(
            'engagecontent',
            'likes',
            get_string('likes', 'rb_source_engagecontent'),
            '(SELECT COUNT(r.instanceid) FROM {reaction} r
            WHERE r.instanceid = base.id AND r.component = base.resourcetype)',
            [
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'text',
                'iscompound' => true,
            ]
        );

        $columnoptions[] = new rb_column_option(
            'engagecontent',
            'playlists',
            get_string('playlists', 'rb_source_engagecontent'),
            "base.countusage",
            [
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'text',
                'iscompound' => true,
            ]
        );

        $columnoptions[] = new rb_column_option(
            'engagecontent',
            'comments',
            get_string('comments', 'rb_source_engagecontent'),
            "(SELECT COUNT(c.instanceid) FROM {totara_comment} c
            WHERE c.instanceid = base.id AND c.component = base.resourcetype)",
            [
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'text',
                'iscompound' => true,
            ]
        );

        $area = library::AREA;
        $columnoptions[] = new rb_column_option(
            'engagecontent',
            'shares',
            get_string('shares', 'rb_source_engagecontent'),
            "(
            SELECT COUNT(r.shareid)
            FROM {engage_share_recipient} r
            INNER JOIN {engage_share} s
            ON s.id = r.shareid
            WHERE
            s.itemid = base.id
            AND
            (r.area <> '{$area}' OR r.component <> 'container_workspace')
            )",
            [
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'text',
                'iscompound' => true,
            ]
        );

        if (advanced_feature::is_enabled('container_workspace')) {
            $columnoptions[] = new rb_column_option(
                'engagecontent',
                'workspaces',
                get_string('workspaces', 'rb_source_engagecontent'),
                "(
                SELECT COUNT(r.instanceid)
                FROM {engage_share_recipient} r
                INNER JOIN {engage_share} s
                ON s.id = r.shareid
                WHERE
                s.itemid = base.id
                AND
                (r.area = '{$area}' OR r.component = 'container_workspace')
                )",
                [
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'text',
                    'iscompound' => true,
                ]
            );
        }

        $columnoptions[] = new rb_column_option(
            'engagecontent',
            'visibility',
            get_string('visibility', 'rb_source_engagecontent'),
            'base.access',
            [
                'displayfunc' => 'engagecontent_visibility',
                'dbdatatype' => 'integer',
                'outputformat' => 'text',
                'nosort' => true
            ]
        );

        $columnoptions[] = new rb_column_option(
            'engagecontent',
            'create_date',
            get_string('create_date', 'rb_source_engagecontent'),
            'base.timecreated',
            [
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
                'outputformat' => 'text',
            ]
        );


        if (advanced_feature::is_enabled('ml_recommender')) {
            $component = engage_article\totara_engage\resource\article::get_resource_type();
            $columnoptions[] = new rb_column_option(
                'engagecontent',
                'views',
                get_string('views', 'rb_source_engagecontent'),
                "(
                SELECT COUNT(mrt.id)
                FROM {ml_recommender_interactions} mrt
                INNER JOIN {ml_recommender_components} mrc ON (mrc.id = mrt.component_id)
                INNER JOIN {ml_recommender_interaction_types} mrit ON (mrit.id = mrt.interaction_type_id)
                WHERE mrt.item_id = base.id
                AND mrt.user_id <> base.userid
                AND mrc.component = '{$component}'
                AND mrc.area IS NULL
                AND mrit.interaction = 'view'
                )",
                [
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'text',
                    'iscompound' => true,
                ]
            );
        }

        $columnoptions[] = new rb_column_option(
            'engagecontent',
            'topics',
            get_string('topics', 'rb_source_engagecontent'),
            "taglist.tagname",
            [
                'joins' => 'taglist',
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'text',
            ]
        );

        $this->add_core_user_columns($columnoptions);
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
                'type' => 'user',
                'value' => 'namelink',
                'heading' => get_string('creator', 'rb_source_engagecontent'),
            ],
            [
                'type' => 'engagecontent',
                'value' => 'resource_name',
                'heading' => get_string('resourcename', 'rb_source_engagecontent')
            ],
            [
                'type' => 'engagecontent',
                'value' => 'playlists',
                'heading' => get_string('playlists', 'rb_source_engagecontent')
            ],
            [
                'type' => 'engagecontent',
                'value' => 'likes',
                'heading' => get_string('likes', 'rb_source_engagecontent')
            ],
            [
                'type' => 'engagecontent',
                'value' => 'comments',
                'heading' => get_string('comments', 'rb_source_engagecontent')
            ],
            [
                'type' => 'engagecontent',
                'value' => 'shares',
                'heading' => get_string('shares', 'rb_source_engagecontent')
            ],
            [
                'type' => 'engagecontent',
                'value' => 'visibility',
                'heading' => get_string('visibility', 'rb_source_engagecontent')
            ],
            [
                'type' => 'engagecontent',
                'value' => 'create_date',
                'heading' => get_string('create_date', 'rb_source_engagecontent')
            ],
            [
                'type' => 'engagecontent',
                'value' => 'topics',
                'heading' => get_string('topics', 'rb_source_engagecontent')
            ],
        ];

        if (advanced_feature::is_enabled('ml_recommender')) {
            $cols[] =             [
                'type' => 'engagecontent',
                'value' => 'views',
                'heading' => get_string('views', 'rb_source_engagecontent')
            ];
        }

        if (advanced_feature::is_enabled('container_workspace')) {
            $cols[] = [
                'type' => 'engagecontent',
                'value' => 'workspaces',
                'heading' => get_string('workspaces', 'rb_source_engagecontent')
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

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    /**
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
            'engagecontent',
            'visibility',
            get_string('visibility', 'rb_source_engagecontent'),
            'select',
            [
                'selectfunc' => 'visibility',
                'attributes' => rb_filter_option::select_width_limiter(),
                'selectable' => false,
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
                'type' => 'engagecontent',
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
            access::PRIVATE => get_string('visibility_private', 'rb_source_engagecontent'),
            access::PUBLIC => get_string('visibility_public', 'rb_source_engagecontent'),
            access::RESTRICTED => get_string('restricted', 'totara_engage')
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
        return advanced_feature::is_disabled('engage_resources');
    }
}