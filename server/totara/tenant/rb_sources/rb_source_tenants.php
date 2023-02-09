<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_tenant
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Reportbuildersource for tenants.
 */
final class rb_source_tenants extends rb_base_source {

    public function __construct() {
        $this->usedcomponents[] = 'totara_tenant';
        $this->base = '{tenant}';
        $this->joinlist = $this->define_joinlist();
        $this->add_core_user_tables($this->joinlist, 'base', 'usercreated', 'usercreated');

        $this->columnoptions = $this->define_columnoptions();
        $this->add_core_user_columns($this->columnoptions, 'usercreated', 'usercreated', true);

        $this->filteroptions = $this->define_filteroptions();
        $this->add_core_user_filters($this->filteroptions, 'usercreated', true);

        $this->contentoptions = array();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters =  $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_tenants');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_tenants');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_tenants');

        $this->cacheable = false;

        parent::__construct();
    }

    /**
     * Are global restrictions implemented?
     * @return null|bool
     */
    public function global_restrictions_supported() {
        // Not easy because deleted users cannot be cohort members.
        return false;
    }

    protected function define_joinlist() {
        return array();
    }

    protected function define_columnoptions() {
        $columnoptions = [];

        $columnoptions[] = new \rb_column_option(
            'tenant',
            'id',
            'ID',
            "base.id",
            array(
                'displayfunc' => 'integer'
            )
        );

        $columnoptions[] = new \rb_column_option(
            'tenant',
            'name',
            get_string('name'),
            "base.name",
            array(
                'dbdatatype' => 'char',
                'displayfunc' => 'format_string',
                'outputformat' => 'text',
            )
        );

        $columnoptions[] = new \rb_column_option(
            'tenant',
            'name_link',
            get_string('tenant', 'totara_tenant'),
            "base.name",
            array(
                'dbdatatype' => 'char',
                'displayfunc' => 'tenant_name_link',
                'outputformat' => 'text',
                'extrafields' => array(
                    'id' => "base.id",
                )
            )
        );

        $columnoptions[] = new \rb_column_option(
            'tenant',
            'login_link',
            get_string('loginlink', 'totara_tenant'),
            "base.idnumber",
            array(
                'dbdatatype' => 'char',
                'displayfunc' => 'tenant_login_link',
                'outputformat' => 'html',
            )
        );


        $columnoptions[] = new \rb_column_option(
            'tenant',
            'idnumber',
            get_string('tenantidnumber', 'totara_tenant'),
            "base.idnumber",
            array(
                'dbdatatype' => 'char',
                'displayfunc' => 'plaintext',
                'outputformat' => 'text',
            )
        );

        $columnoptions[] = new \rb_column_option(
            'tenant',
            'description',
            get_string('description'),
            "base.description",
            array(
                'dbdatatype' => 'text',
                'outputformat' => 'text',
                'displayfunc' => 'editor_textarea',
                'extrafields' => array(
                    'format' => "base.descriptionformat",
                )
            )
        );

        $columnoptions[] = new \rb_column_option(
            'tenant',
            'suspended',
            get_string('suspended', 'totara_tenant'),
            "base.suspended",
            array(
                'displayfunc' => 'yes_or_no',
            )
        );

        $columnoptions[] = new \rb_column_option(
            'tenant',
            'timecreated',
            get_string('timecreated', 'rb_source_tenants'),
            "base.timecreated",
            array(
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            )
        );

        $columnoptions[] = new \rb_column_option(
            'tenant',
            'category',
            get_string('category'),
            "base.categoryid",
            array(
                'nosort' => true,
                'displayfunc' => 'tenant_category',
            )
        );

        $columnoptions[] = new \rb_column_option(
            'tenant',
            'participantcount',
            get_string('participantcount', 'totara_tenant'),
            "(SELECT COUNT('x') FROM {cohort_members} cm WHERE cm.cohortid = base.cohortid)",
            array(
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer',
                'issubquery' => true,
            )
        );

        $columnoptions[] = new \rb_column_option(
            'tenant',
            'membercount',
            get_string('membercount', 'totara_tenant'),
            "(SELECT COUNT('x') FROM {user} u WHERE u.tenantid = base.id AND u.deleted = 0)",
            array(
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer',
                'issubquery' => true,
            )
        );

        $columnoptions[] = new \rb_column_option(
            'tenant',
            'participants',
            get_string('participants', 'totara_tenant'),
            "(SELECT COUNT('x') FROM {cohort_members} cm WHERE cm.cohortid = base.cohortid)",
            array(
                'displayfunc' => 'tenant_participants',
                'issubquery' => true,
                'iscompound' => true,
                'extrafields' => array(
                    'id' => "base.id",
                )
            )
        );

        $columnoptions[] = new \rb_column_option(
            'tenant',
            'usermanagers',
            get_string('usermanagers', 'totara_tenant'),
            "base.id",
            array(
                'displayfunc' => 'tenant_user_managers',
            )
        );

        $columnoptions[] = new \rb_column_option(
            'tenant',
            'domainmanagers',
            get_string('domainmanagers', 'totara_tenant'),
            "base.id",
            array(
                'displayfunc' => 'tenant_domain_managers',
            )
        );

        $columnoptions[] = new \rb_column_option(
            'tenant',
            'actions',
            get_string('actions', 'totara_reportbuilder'),
            "base.id",
            array(
                'displayfunc' => 'tenant_actions',
                'nosort' => true,
                'noexport' => true,
                'capability' => ['totara/tenant:config'],
            )
        );

        return $columnoptions;
    }

    protected function define_filteroptions() {
        return array();
    }

    protected function define_defaultcolumns() {
        return array(
            array('type' => 'tenant', 'value' => 'name'),
            array('type' => 'tenant', 'value' => 'idnumber'),
            array('type' => 'tenant', 'value' => 'suspended'),
            array('type' => 'tenant', 'value' => 'category'),
            array('type' => 'tenant', 'value' => 'participants'),
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
