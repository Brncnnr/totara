<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_topic
 */
defined('MOODLE_INTERNAL') || die();

final class rb_source_topic extends rb_base_source {
    /**
     * rb_source_topic constructor.
     */
    public function __construct() {
        global $CFG;

        // This is needed for output display class.
        $this->usedcomponents[] = 'totara_topic';
        $this->usedcomponents[] = 'core_tag';

        $this->base = '{tag}';

        // Columns
        $this->columnoptions = $this->define_columnoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();

        // Filters
        $this->defaultfilters = $this->define_defaultfilters();
        $this->filteroptions = $this->define_filteroptions();

        $this->joinlist = $this->define_joinlist();
        $this->contentoptions = $this->define_contentoptions();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->paramoptions = $this->define_paramoptions();

        $this->sourcetitle = get_string('sourcetitle', 'rb_source_topic');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_topic');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_topic');

        $this->sourcewhere = 'base.tagcollid = :tagcollectionid';

        // Always use the param named for the report builder query.
        $this->sourceparams = ['tagcollectionid' => $CFG->topic_collection_id];
        $this->cacheable = false;

        parent::__construct();
    }

    /**
     * @return rb_join[]
     */
    protected function define_joinlist(): array {
        return [
            new rb_join(
                'topic_usage',
                'LEFT',
                '(SELECT COUNT(ti.itemid) AS totalusage, ti.tagid FROM {tag_instance} ti GROUP BY ti.tagid)',
                'base.id = topic_usage.tagid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            )
        ];
    }

    /**
     * @return array
     */
    protected function define_columnoptions(): array {
        return [
            new rb_column_option(
                'topic',
                'value',
                get_string('topic', 'totara_topic'),
                'base.rawname',
                [
                    'displayfunc' => 'format_string',
                    'outputformat' => 'text',
                    'dbdatatype' => 'char'
                ]
            ),

            new rb_column_option(
                'topic',
                'timemodified',
                get_string('timemodified', 'totara_topic'),
                'base.timemodified',
                [
                    'displayfunc' => 'nice_datetime',
                    'dbdatatype' => 'timestamp'
                ]
            ),

            new rb_column_option(
                'topic',
                'actions',
                get_string('actions', 'rb_source_topic'),
                'base.id',
                [
                    'displayfunc' => 'actions',
                    'graphable' => false,
                    'noexport' => true,
                    'nosort' => true,
                    'joins' => ['topic_usage'],
                    'extrafields' => [
                        'totalusage' => 'topic_usage.totalusage',
                        'topic_value' => 'base.rawname'
                    ]
                ]
            )
        ];
    }

    /**
     * @return array
     */
    protected function define_defaultcolumns(): array {
        return [
            [
                'type' => 'topic',
                'value' => 'value'
            ],
            [
                'type' => 'topic',
                'value' => 'timemodified'
            ]
        ];
    }

    /**
     * @return array
     */
    protected function define_filteroptions(): array {
        return [
            new rb_filter_option(
                'topic',
                'value',
                get_string('topic', 'totara_topic'),
                'text'
            ),

            new rb_filter_option(
                'topic',
                'timemodified',
                get_string('timemodified', 'totara_topic'),
                'date'
            )
        ];
    }

    /**
     * @return array
     */
    protected function define_defaultfilters(): array {
        return [
            [
                'type' => 'topic',
                'value' => 'value'
            ]
        ];
    }

    /**
     * @return bool
     */
    public function global_restrictions_supported() {
        return true;
    }

    /**
     * @param rb_column_option $columnoption
     * @return int
     */
    public function phpunit_column_test_expected_count($columnoption) {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            $fn = __FUNCTION__;
            throw new coding_exception("The function '{$fn}' should not be used outside of the unit test");
        }

        return 0;
    }

    /**
     * @return array
     */
    public function get_required_jss() {
        return [];
    }
}