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
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use totara_core\advanced_feature;
use totara_job\rb\source\report_trait;
use totara_engage\access\access;
use totara_playlist\playlist;
use totara_comment\comment;
use core_user\totara_engage\share\recipient\user;

/**
 * Playlist engagement is management interface for playlist
 */
class rb_source_playlistengagement extends rb_base_source {
    use report_trait;

    /**
     * rb_source_playlistengagement constructor.
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
        $this->add_global_report_restriction_join('base', 'userid');

        $this->usedcomponents[] = 'totara_playlist';

        $this->base = "{playlist}";

        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_playlistengagement');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_playlistengagement');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_playlistengagement');

        parent::__construct();
    }


    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return  advanced_feature::is_disabled('engage_resources');
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
            'taglist',
            'LEFT',
            '(
                SELECT ti.itemid,
                ' . $DB->sql_group_concat('t.name', ' , ') . ' AS tagname
                FROM {tag_instance} ti
                INNER JOIN {tag} t ON t.id = ti.tagid
                WHERE itemtype = \'playlist\' GROUP BY ti.itemid
            )',
            'taglist.itemid = base.id',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );

        $this->add_core_user_tables($joinlist, 'base','userid');

        return $joinlist;
    }

    /**
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = [];
        $columnoptions[] = new rb_column_option(
            'playlistengagement',
            'title',
            get_string('title', 'rb_source_playlistengagement'),
            "base.name",
            [
                'displayfunc' => 'playlistengagement_titlelink',
                'dbdatatype' => 'char',
                'outputformat' => 'html',
                'joins' => 'auser',
                'extrafields' => [
                    'id' => 'base.id',
                    'deleted' => 'auser.deleted',
                    'suspended' => 'auser.suspended'
                ]
            ]
        );

        $columnoptions[] = new rb_column_option(
            'playlistengagement',
            'visibility',
            get_string('visibility', 'rb_source_playlistengagement'),
            'base.access',
            [
                'displayfunc' => 'playlistengagement_visibility',
                'dbdatatype' => 'integer',
                'outputformat' => 'text',
                'nosort' => true
            ]
        );

        $columnoptions[] = new rb_column_option(
            'playlistengagement',
            'resources',
            get_string('resourceinplaylist', 'rb_source_playlistengagement'),
            '(SELECT COUNT(*) FROM {playlist_resource} pr
            WHERE pr.playlistid = base.id)',
            [
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'text',
                'iscompound' => true,
            ]
        );

        $playlist_component = playlist::get_resource_type();
        $area = playlist::RATING_AREA;
        $columnoptions[] = new rb_column_option(
            'playlistengagement',
            'rating',
            get_string('rating', 'rb_source_playlistengagement'),
            "(SELECT COUNT(*) FROM {engage_rating} er
            WHERE (er.area = '{$area}' OR er.component = '{$playlist_component}')
            AND er.instanceid = base.id)",
            [
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'text',
                'iscompound' => true,
            ]
        );

        $comment_area = comment::COMMENT_AREA;
        $columnoptions[] = new rb_column_option(
            'playlistengagement',
            'comments',
            get_string('comments', 'rb_source_playlistengagement'),
            "(SELECT COUNT(*) FROM {totara_comment} tc
            WHERE tc.instanceid = base.id AND (tc.component = '{$playlist_component}'
            OR tc.area = '{$comment_area}') AND tc.parentid IS NULL)",
            [
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'text',
                'iscompound' => true,
            ]
        );

        $area = user::AREA;
        $component = 'core_user';
        $columnoptions[] = new rb_column_option(
            'playlistengagement',
            'shares',
            get_string('shares', 'rb_source_playlistengagement'),
            "(SELECT COUNT(*) FROM {engage_share_recipient} sr
            INNER JOIN {engage_share} s ON s.id = sr.shareid
            WHERE sr.component = '{$component}' AND sr.area = '{$area}'
            AND s.itemid = base.id AND s.component = '{$playlist_component}')",
            [
                'displayfunc' => 'plaintext',
                'dbdatatype' => 'text',
                'iscompound' => true,
            ]
        );

        if (advanced_feature::is_enabled('container_workspace')) {
            $area = 'library';
            $component = 'container_workspace';
            $columnoptions[] = new rb_column_option(
                'playlistengagement',
                'workspaces',
                get_string('shares', 'rb_source_playlistengagement'),
                "(SELECT COUNT(*) FROM {engage_share_recipient} sr
                INNER JOIN {engage_share} s ON s.id = sr.shareid
                WHERE sr.component = '{$component}' AND sr.area = '{$area}'
                AND s.itemid = base.id AND s.component = '{$playlist_component}')",
                [
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'text',
                    'iscompound' => true,
                ]
            );
        }

        $columnoptions[] = new rb_column_option(
            'playlistengagement',
            'create_date',
            get_string('create_date', 'rb_source_playlistengagement'),
            "base.timecreated",
            [
                'outputformat' => 'text',
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            ]
        );

        $columnoptions[] = new rb_column_option(
            'playlistengagement',
            'update_date',
            get_string('update_date', 'rb_source_playlistengagement'),
            "base.timemodified",
            [
                'outputformat' => 'text',
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            ]
        );

        if (advanced_feature::is_enabled('ml_recommender')) {
            $component = totara_playlist\playlist::get_resource_type();
            $columnoptions[] = new rb_column_option(
                'playlistengagement',
                'views',
                get_string('views', 'rb_source_playlistengagement'),
                "(
                SELECT COUNT(*) FROM {ml_recommender_interactions} mrt
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
            'playlistengagement',
            'topics',
            get_string('topics', 'rb_source_playlistengagement'),
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
                'type' => 'playlistengagement',
                'value' => 'title',
                'heading' => get_string('title', 'rb_source_playlistengagement')
            ],
            [
                'type' => 'playlistengagement',
                'value' => 'visibility',
                'heading' => get_string('visibility', 'rb_source_playlistengagement')
            ],
            [
                'type' => 'playlistengagement',
                'value' => 'resources',
                'heading' => get_string('resourceinplaylist', 'rb_source_playlistengagement')
            ],
            [
                'type' => 'playlistengagement',
                'value' => 'rating',
                'heading' => get_string('rating', 'rb_source_playlistengagement')
            ],
            [
                'type' => 'playlistengagement',
                'value' => 'comments',
                'heading' => get_string('comments', 'rb_source_playlistengagement')
            ],
            [
                'type' => 'playlistengagement',
                'value' => 'shares',
                'heading' => get_string('shares', 'rb_source_playlistengagement')
            ],
            [
                'type' => 'user',
                'value' => 'namelink',
                'heading' => get_string('creator', 'rb_source_playlistengagement'),
            ],
            [
                'type' => 'playlistengagement',
                'value' => 'create_date',
                'heading' => get_string('create_date', 'rb_source_playlistengagement')
            ],
            [
                'type' => 'playlistengagement',
                'value' => 'update_date',
                'heading' => get_string('update_date', 'rb_source_playlistengagement')
            ],
            [
                'type' => 'playlistengagement',
                'value' => 'topics',
                'heading' => get_string('topics', 'rb_source_playlistengagement')
            ],
        ];

        if (advanced_feature::is_enabled('ml_recommender')) {
            $cols[] = [
                'type' => 'playlistengagement',
                'value' => 'views',
                'heading' => get_string('views', 'rb_source_playlistengagement')
            ];
        }

        if (advanced_feature::is_enabled('container_workspace')) {
            $cols[] = [
                'type' => 'playlistengagement',
                'value' => 'workspaces',
                'heading' => get_string('workspaces', 'rb_source_playlistengagement')
            ];
        }

        return $cols;
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
    protected function define_filteroptions() {
        $filteroptions = [];

        $filteroptions[] = new rb_filter_option(
            'playlistengagement',
            'visibility',
            get_string('visibility', 'rb_source_playlistengagement'),
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
                'type' => 'playlistengagement',
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
            access::PRIVATE => get_string('visibility_private', 'rb_source_playlistengagement'),
            access::PUBLIC => get_string('visibility_public', 'rb_source_playlistengagement'),
            access::RESTRICTED => get_string('visibility_restricted', 'rb_source_playlistengagement')
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
}