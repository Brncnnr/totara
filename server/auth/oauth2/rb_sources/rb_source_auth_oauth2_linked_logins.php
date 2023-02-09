<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *
 * @package auth_oauth2
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_auth_oauth2_linked_logins extends rb_base_source {
    public function __construct() {
        $this->usedcomponents[] = 'auth_oauth2';
        $this->base = '{auth_oauth2_linked_login}';
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_auth_oauth2_linked_logins');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_auth_oauth2_linked_logins');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_auth_oauth2_linked_logins');

        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();

        // Add the user info.
        $this->add_core_user_tables($this->joinlist, 'base', 'userid', 'auser');
        $this->add_core_user_columns($this->columnoptions, 'auser', 'user', true);
        $this->add_core_user_filters($this->filteroptions, 'user', true);

        // No caching, we always need the latest data!
        $this->cacheable = false;

        parent::__construct();
    }

    protected function define_joinlist() {
        return [
            new rb_join(
                'issuer',
                'INNER',
                '{oauth2_issuer}',
                'base.issuerid = issuer.id'
            ),
        ];
    }

    protected function define_columnoptions() {
        return [
            new rb_column_option(
                'issuer',
                'name',
                get_string('issuer', 'auth_oauth2'),
                "issuer.name",
                array(
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'joins' => 'issuer',
                )
            ),
            new rb_column_option(
                'issuer',
                'enabled',
                get_string('enabled', 'totara_core'),
                "issuer.enabled",
                array(
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'bool',
                    'joins' => 'issuer',
                )
            ),
            new rb_column_option(
                'issuer',
                'showonloginpage',
                get_string('issuershowonloginpage', 'tool_oauth2'),
                "issuer.showonloginpage",
                array(
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'bool',
                    'joins' => 'issuer',
                )
            ),
            new rb_column_option(
                'linkedlogin',
                'username',
                get_string('issuerusername', 'auth_oauth2'),
                "base.username",
                array(
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                )
            ),
            new rb_column_option(
                'linkedlogin',
                'email',
                get_string('issueremail', 'auth_oauth2'),
                "base.email",
                array(
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                )
            ),
            new rb_column_option(
                'linkedlogin',
                'confirmed',
                get_string('confirmed', 'core_admin'),
                "base.confirmed",
                array(
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'bool',
                )
            ),
            new rb_column_option(
                'linkedlogin',
                'timecreated',
                get_string('timecreated', 'totara_reportbuilder'),
                "base.timecreated",
                array(
                    'displayfunc' => 'nice_datetime',
                    'dbdatatype' => 'timestamp',
                )
            ),
            new rb_column_option(
                'linkedlogin',
                'timemodified',
                get_string('timemodified', 'totara_reportbuilder'),
                "base.timemodified",
                array(
                    'displayfunc' => 'nice_datetime',
                    'dbdatatype' => 'timestamp',
                )
            ),
            new rb_column_option(
                'linkedlogin',
                'actions',
                get_string('actions'),
                "base.id",
                array(
                    'displayfunc' => 'linked_login_actions',
                    'extrafields' => ['issuerid' => "base.issuerid", 'userid' => "base.userid", 'confirmed' => "base.confirmed"],
                    'nosort' => true,
                    'noexport' => true,
                )
            ),
        ];
    }

    protected function define_filteroptions() {
        return array(
            new rb_filter_option(
                'issuer',
                'name',
                get_string('requeststatus', 'auth_approved'),
                'multicheck',
                array(
                    'selectfunc' => 'login_issuers',
                    'simplemode' => true
                )
            ),
            new rb_filter_option(
                'issuer',
                'enabled',
                get_string('enabled', 'totara_core'),
                'multicheck',
                array(
                    'selectchoices' => array('0' => get_string('no'), '1' => get_string('yes')),
                    'simplemode' => true
                )
            ),
            new rb_filter_option(
                'issuer',
                'showonloginpage',
                get_string('issuershowonloginpage', 'tool_oauth2'),
                'multicheck',
                array(
                    'selectchoices' => array('0' => get_string('no'), '1' => get_string('yes')),
                    'simplemode' => true
                )
            ),
            new rb_filter_option(
                'linkedlogin',
                'username',
                get_string('issuerusername', 'auth_oauth2'),
                'text'
            ),
            new rb_filter_option(
                'linkedlogin',
                'email',
                get_string('issueremail', 'auth_oauth2'),
                'text'
            ),
            new rb_filter_option(
                'linkedlogin',
                'confirmed',
                get_string('confirmed', 'core_admin'),
                'multicheck',
                array(
                    'selectchoices' => array('0' => get_string('no'), '1' => get_string('yes')),
                    'simplemode' => true
                )
            ),
            new rb_filter_option(
                'linkedlogin',
                'timecreated',
                get_string('timecreated', 'totara_reportbuilder'),
                'date',
                array(
                    'includetime' => true,
                )
            ),
            new rb_filter_option(
                'linkedlogin',
                'timemodified',
                get_string('timemodified', 'totara_reportbuilder'),
                'date',
                array(
                    'includetime' => true,
                )
            ),
        );
    }

    public function rb_filter_login_issuers(): array {
        return \auth_oauth2\api::get_login_issuers_menu();
    }

    protected function define_defaultcolumns() {
        return [
            ['type' => 'issuer', 'value' => 'name'],
            ['type' => 'issuer', 'value' => 'enabled'],
            ['type' => 'issuer', 'value' => 'showonloginpage'],
            ['type' => 'linkedlogin', 'value' => 'username'],
            ['type' => 'linkedlogin', 'value' => 'email'],
            ['type' => 'linkedlogin', 'value' => 'confirmed'],
            ['type' => 'user', 'value' => 'fullname'],
            ['type' => 'user', 'value' => 'deleted'],
            ['type' => 'user', 'value' => 'auth'],
            ['type' => 'linkedlogin', 'value' => 'actions'],
        ];
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return false;
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
