<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

use totara_useraction\entity\scheduled_rule_history as scheduled_rule_history_entity;


class rb_source_useraction_history extends rb_base_source {
    /**
     * @param $groupid
     * @param rb_global_restriction_set|null $globalrestrictionset
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;
        $this->add_global_report_restriction_join('base', 'user_id');

        $this->usedcomponents[] = 'totara_useraction';

        $this->base = '{totara_useraction_scheduled_rule_history}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_useraction_history');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_useraction_history');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_useraction_history');

        parent::__construct();
    }

    /**
     * When we add tenant support we'll add content restrictions.
     *
     * @return boolean
     */
    public function global_restrictions_supported(): bool {
        return false;
    }

    /**
     * @return array
     */
    protected function define_columnoptions(): array {
        $columnoptions = [];

        $columnoptions[] = new rb_column_option(
            'useraction_history',
            'created',
            get_string('created', 'rb_source_useraction_history'),
            'base.created',
            [
                'displayfunc' => 'nice_datetime_seconds',
                'dbdatatype' => 'timestamp',
                'outputformat' => 'text',
            ]
        );

        $columnoptions[] = new rb_column_option(
            'useraction_history',
            'success',
            get_string('success', 'rb_source_useraction_history'),
            'base.success',
            [
                'dbdatatype' => 'integer',
                'displayfunc' => 'yes_or_no',
            ]
        );

        $columnoptions[] = new rb_column_option(
            'useraction_history',
            'scheduled_rule',
            get_string('scheduled_rule', 'rb_source_useraction_history'),
            'scheduled_rule.id',
            [
                'displayfunc' => 'scheduled_rule_name',
                'dbdatatype' => 'text',
                'outputformat' => 'html',
                'joins' => 'scheduled_rule',
                'extrafields' => [
                    'name' => 'scheduled_rule.name',
                ]
            ]
        );

        $columnoptions[] = new rb_column_option(
            'useraction_history',
            'action',
            get_string('action', 'rb_source_useraction_history'),
            'base.action',
            [
                'displayfunc' => 'scheduled_rule_action',
                'dbdatatype' => 'text',
                'outputformat' => 'text'
            ]
        );

        $columnoptions[] = new rb_column_option(
            'useraction_history',
            'message',
            get_string('message', 'rb_source_useraction_history'),
            'base.message',
            [
                'dbdatatype' => 'text',
                'outputformat' => 'plaintext',
                'displayfunc' => 'plaintext'
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
        return [
            ['type' => 'useraction_history', 'value' => 'created'],
            ['type' => 'useraction_history', 'value' => 'scheduled_rule'],
            ['type' => 'useraction_history', 'value' => 'action'],
            ['type' => 'user', 'value' => 'namelink'],
            ['type' => 'useraction_history', 'value' => 'success'],
            ['type' => 'useraction_history', 'value' => 'message'],
        ];
    }

    /**
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = [];

        $filteroptions[] = new rb_filter_option(
            'useraction_history',
            'created',
            get_string('created', 'rb_source_useraction_history'),
            'date',
            [
                'includetime' => true,
            ]
        );

        $filteroptions[] = new rb_filter_option(
            'useraction_history',
            'success',
            get_string('success', 'rb_source_useraction_history'),
            'multicheck',
            [
                'simplemode' => true,
                'selectfunc' => 'yesno_list',
            ]
        );

        $this->add_core_user_filters($filteroptions);
        return $filteroptions;
    }

    /**
     * @return array
     */
    protected function define_joinlist(): array {
        $joinlist = [];

        $this->add_core_user_tables($joinlist, 'base', 'user_id');

        $joinlist[] = new rb_join(
            'scheduled_rule',
            'LEFT',
            "{totara_useraction_scheduled_rule}",
            'scheduled_rule.id = base.scheduled_rule_id',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );

        return $joinlist;
    }

    /**
     * @return rb_param_option[]
     */
    protected function define_paramoptions() {
        return [
            new rb_param_option('userid', 'base.user_id'),
            new rb_param_option('rule_id', 'base.scheduled_rule_id'),
        ];
    }

    /**
     * @param totara_reportbuilder_column_testcase $testcase
     * @return void
     */
    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
        }

        $gen = \totara_useraction\testing\generator::instance();
        $rule = $gen->create_scheduled_rule();

        $entity = new scheduled_rule_history_entity();

        $entity->scheduled_rule_id = $rule->id;
        $entity->user_id = 2;
        $entity->action = get_class($rule->action);
        $entity->success = true;
        $entity->message = 'test';
        $entity->created = time();

        $entity->save();
    }
}
