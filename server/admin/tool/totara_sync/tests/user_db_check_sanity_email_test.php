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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/tests/source_database_testcase.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_user_database.php');

/**
 * Test to ensure sanity check of the email field is works correctly for database sources.
 *
 * We will be testing the following setups.
 *
 * |-----|--------|--------|------------------|---------------------|
 * | #   | Create | Update | Allow duplicates | Default email empty |
 * |-----|--------|--------|------------------|---------------------|
 * | 1   |   1    |    0   |         1        |          0          |
 * | 2   |   1    |    0   |         1        |          1          |
 * | 3   |   1    |    0   |         0        |          0          |
 * | 4   |   0    |    1   |         1        |          0          |
 * | 5   |   0    |    1   |         1        |          1          |
 * | 6   |   0    |    1   |         0        |          0          |
 * |-----|--------|--------|------------------|---------------------|
 *
 * @group tool_totara_sync
 */
class tool_totara_sync_user_db_check_email_sanity_testcase extends totara_sync_database_testcase {

    protected $config           = [];
    protected $configdatabase   = [];

    protected $elementname  = 'user';
    protected $sourcetable  = 'totara_sync_user_source';

    protected $sourcename   = 'totara_sync_source_user_database';
    protected $source       = null;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        global $CFG;

        require_once($CFG->dirroot . '/admin/tool/totara_sync/tests/source_database_testcase.php');
        require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_user_database.php');
    }

    public function tearDown(): void {
        $this->config           = null;
        $this->configdatabase   = null;
        $this->elementname      = null;
        $this->sourcetable      = null;
        $this->sourcename       = null;
        $this->source           = null;
        parent::tearDown();
    }

    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();

        $this->source = new $this->sourcename();

        set_config('element_user_enabled', 1, 'totara_sync');
        set_config('source_user', 'totara_sync_source_user_database', 'totara_sync');

        $this->configdatabase = [
            'import_deleted'            => '1',
            'import_firstname'          => '1',
            'import_idnumber'           => '1',
            'import_lastname'           => '1',
            'import_timemodified'       => '1',
            'import_username'           => '1',
            'import_email'              => '1',
        ];

        $this->config = [
            'allow_create'              => '0',
            'allow_delete'              => '0',
            'allow_update'              => '0',
            'allowduplicatedemails'     => '0',
            'defaultsyncemail'          => '',
            'forcepwchange'             => '0',
            'undeletepwreset'           => '0',
            'ignoreexistingpass'        => '0',
            'sourceallrecords'          => '0',
        ];

        // Create the database source.
        $sourcedata = [
            [
                'idnumber' => 'idnum001',
                'username' => 'user1',
                'email' => 'user1@email.com',
                'firstname' => 'user1-firstname',
                'lastname' => 'user1-lastname',
                'timemodified' => '0',
                'deleted' => '0',
            ],
            [
                'idnumber' => 'idnum002',
                'username' => 'user2',
                'email' => 'user2@email.com',
                'firstname' => 'user2-firstname',
                'lastname' => 'user2-lastname',
                'timemodified' => '0',
                'deleted' => '0',
            ],
            [
                'idnumber' => 'idnum003',
                'username' => 'user3',
                'email' => 'user3.email.com',
                'firstname' => 'user3-firstname',
                'lastname' => 'user3-lastname',
                'timemodified' => '0',
                'deleted' => '0',
            ],
            [
                'idnumber' => 'idnum004',
                'username' => 'user4',
                'email' => '',
                'firstname' => 'user4-firstname',
                'lastname' => 'user4-lastname',
                'timemodified' => '0',
                'deleted' => '0',
            ],
            [
                'idnumber' => 'idnum005',
                'username' => 'user5',
                'email' => null,
                'firstname' => 'user5-firstname',
                'lastname' => 'user5-lastname',
                'timemodified' => '0',
                'deleted' => '0',
            ],
        ];

        $this->create_external_db_table();

        foreach ($sourcedata as $data) {
            $this->ext_dbconnection->insert_record($this->dbtable, (object)$data);
        }
    }

    /**
     * Create table for external DB test
     */
    public function create_external_db_table() {
        $dbman = $this->ext_dbconnection->get_manager();
        $table = new xmldb_table($this->dbtable);

        // Drop table first, if it exists.
        if ($dbman->table_exists($this->dbtable)) {
            $dbman->drop_table($table);
        }

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('username', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1');
        $table->add_field('firstname', XMLDB_TYPE_CHAR, '255');
        $table->add_field('lastname', XMLDB_TYPE_CHAR, '255');
        $table->add_field('email', XMLDB_TYPE_CHAR, '100');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $dbman->create_table($table);
    }

    /**
     * Data provider for user creation
     */
    public function data_provider_user_creation() {
        $data = [
            [
                1, // Test number.
                [ // Element config.
                    'allow_create'          => '1',
                    'allowduplicatedemails' => '1',
                    'defaultsyncemail'      => '',
                ],
                [ // Expected results
                    3 => 'idnum003',
                    4 => 'idnum004',
                    5 => 'idnum005',
                ]
            ],
            [
                2, // Test number
                [ // Element config.
                    'allow_create'          => '1',
                    'allowduplicatedemails' => '1',
                    'defaultsyncemail'      => 'default@email.com',
                ],
                [] // Expected results.
            ],
            [
                3, // Test number.
                [ // Element config.
                    'allow_create'          => '1',
                    'allowduplicatedemails' => '0',
                    'defaultsyncemail'      => '',
                ],
                [ // Expected results.
                    1 => 'idnum001',
                    3 => 'idnum003',
                    4 => 'idnum004',
                    5 => 'idnum005',
                ],
            ]
        ];

        return $data;
    }

    /**
     * Data provider for user update
     */
    public function data_provider_user_update() {
        $data = [
            [
                4, // Test number.
                [ // Element config.
                    'allow_create'          => '1',
                    'allowduplicatedemails' => '1',
                    'defaultsyncemail'      => '',
                ],
                [ // Expected results
                    3 => 'idnum003',
                    4 => 'idnum004',
                ]
            ],
            [
                5, // Test number
                [ // Element config.
                    'allow_create'          => '1',
                    'allowduplicatedemails' => '1',
                    'defaultsyncemail'      => 'default@email.com',
                ],
                [] // Expected results.
            ],
            [
                6, // Test number.
                [ // Element config.
                    'allow_create'          => '1',
                    'allowduplicatedemails' => '0',
                    'defaultsyncemail'      => '',
                ],
                [ // Expected results.
                    1 => 'idnum001',
                    3 => 'idnum003',
                    4 => 'idnum004',
                ],
            ]
        ];

        return $data;
    }

    /**
     * @dataProvider data_provider_user_creation
     */
    public function test_email_field_user_creation($testnum, $elementconfig, $expectedresults) {
        // Create a user for duplicate email test.
        $this->getDataGenerator()->create_user(['email' => 'user1@email.com']);

        // Set the config.
        $this->set_source_config(array_merge($this->configdatabase));
        $this->set_element_config(array_merge($this->config, $elementconfig));

        // Test the sanity checks.
        $invalididnumbers = $this->check_sanity();
        ksort($invalididnumbers);

        $this->assertEquals($expectedresults, $invalididnumbers, 'Failed for test #' . $testnum);
    }

    /**
     * @dataProvider data_provider_user_update
     */
    public function test_email_field_user_update($testnum, $elementconfig, $expectedresults) {
        // Create a user for duplicate email test.
        $this->getDataGenerator()->create_user(['email' => 'user1@email.com']);

        // Create other users for the sanity check for updating users.
        $this->getDataGenerator()->create_user(['totarasync' => 1, 'idnumber' => 'idnum002', 'email' => 'user2@email.com']);
        $this->getDataGenerator()->create_user(['totarasync' => 1, 'idnumber' => 'idnum003', 'email' => 'user3@email.com']);
        $this->getDataGenerator()->create_user(['totarasync' => 1, 'idnumber' => 'idnum004', 'email' => 'user4@email.com']);
        $this->getDataGenerator()->create_user(['totarasync' => 1, 'idnumber' => 'idnum005', 'email' => 'user5@email.com']);

        // Set the config.
        $this->set_source_config(array_merge($this->configdatabase));
        $this->set_element_config(array_merge($this->config, $elementconfig));

        $invalididnumbers = $this->check_sanity();
        ksort($invalididnumbers);

        $this->assertEquals($expectedresults, $invalididnumbers, 'Failed for test #' . $testnum);
    }
}
