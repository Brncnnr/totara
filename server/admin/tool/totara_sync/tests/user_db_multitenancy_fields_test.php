<?php
/*
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package tool_totara_sync
 */

use totara_tenant\local\util;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/tests/source_database_testcase.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_user_database.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_user_db_multitenancy_fields_testcase extends totara_sync_database_testcase {

    protected $config           = [];
    protected $configdatabase   = [];

    protected $elementname      = 'user';
    protected $sourcetable      = 'totara_sync_user_source';

    protected $sourcename       = 'totara_sync_source_user_database';
    protected $source           = null;

    /** @var \totara_tenant\testing\generator $tenantgenerator */
    protected $tenantgenerator  = null;

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
        $this->tenantgenerator  = null;
        parent::tearDown();
    }

    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $this->tenantgenerator = \totara_tenant\testing\generator::instance();
        $this->tenantgenerator->enable_tenants();

        // Set the source.
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
            'import_tenantmember'     => '0',
            'import_tenantparticipant' => '0',
        ];

        $this->config = [
            'allow_create'              => '1',
            'allow_delete'              => '1',
            'allow_update'              => '1',
            'allowduplicatedemails'     => '0',
            'defaultsyncemail'          => '',
            'forcepwchange'             => '0',
            'undeletepwreset'           => '0',
            'ignoreexistingpass'        => '0',
            'sourceallrecords'          => '0',
        ];
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
        $table->add_field('tenantmember', XMLDB_TYPE_CHAR, '100');
        $table->add_field('tenantparticipant', XMLDB_TYPE_TEXT);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $dbman->create_table($table);
    }

    /**
     * Create a user array to be used when creating users
     *
     * @return array
     */
    public function user($num) {
        return [
            'idnumber'          => 'idnum' . $num,
            'username'          => 'user' . $num,
            'email'             => 'user' . $num . '@email.com',
            'firstname'         => 'user' . $num . '-firstname',
            'lastname'          => 'user' . $num . '-lastname',
            'timemodified'      => '0',
            'deleted'           => '0',
        ];
    }

    /**
     * Search the given $sync_log for the given $info
     *
     * @param string $info
     * @param array $sync_log
     * @return bool
     */
    private function sync_log_contains ($info, $sync_log) {
        foreach($sync_log as $logrecord) {
            if ($logrecord->info === $info) {
                return true;
            }
        }

        return false;
    }

    /**
     * Test the sanity check get_invalid_tenantmember function
     */
    public function test_get_invalid_tenantmember() {
        global $DB;

        // Set the config.
        $this->set_source_config(array_merge($this->configdatabase, ['import_tenantmember' => '1']));
        $this->set_element_config(array_merge($this->config));

        // Create some tenants.
        $this->tenantgenerator->create_tenant(['idnumber' => 'tenantid1']);
        $this->tenantgenerator->create_tenant(['idnumber' => 'tenantid2']);
        $this->tenantgenerator->create_tenant(['idnumber' => 'tenantid3']);

        // Create the database source.
        $sourcedata = [];
        $sourcedata[] = array_merge($this->user('1'), ['tenantmember' => null]);
        $sourcedata[] = array_merge($this->user('2'), ['tenantmember' => '']);
        $sourcedata[] = array_merge($this->user('3'), ['tenantmember' => 'badidnumber']); // Fail.
        $sourcedata[] = array_merge($this->user('4'), ['tenantmember' => 'tenantid2']);

        $this->create_external_db_table();
        foreach ($sourcedata as $data) {
            $this->ext_dbconnection->insert_record($this->dbtable, (object)$data);
        }

        // Test the sanity check.
        $invalididnumbers = $this->check_sanity();
        self::assertCount(1, $invalididnumbers);

        // Check the sync log.
        $sync_log = $DB->get_records('totara_sync_log');
        self::assertCount(1, $sync_log);
        self::assertTrue(self::sync_log_contains('Tenant idnumber badidnumber does not exist. Skipped user idnum3', $sync_log));
    }

    /**
     * Test the sanity check check_tenant_member_and_participant_set function
     */
    public function test_check_tenant_member_and_participant_set() {
        global $DB;

        // Set the config.
        $this->set_source_config(array_merge($this->configdatabase, ['import_tenantmember' => '1', 'import_tenantparticipant' => '1']));
        $this->set_element_config(array_merge($this->config));

        // Create some tenants.
        $this->tenantgenerator->create_tenant(['idnumber' => 'tenantid1']);
        $this->tenantgenerator->create_tenant(['idnumber' => 'tenantid2']);
        $this->tenantgenerator->create_tenant(['idnumber' => 'tenantid3']);

        // Create the database source.
        $sourcedata = [];
        $sourcedata[] = array_merge($this->user('1'), ['tenantmember' => null, 'tenantparticipant' => null]);
        $sourcedata[] = array_merge($this->user('2'), ['tenantmember' => null, 'tenantparticipant' => '']);
        $sourcedata[] = array_merge($this->user('3'), ['tenantmember' => '', 'tenantparticipant' => null]);
        $sourcedata[] = array_merge($this->user('4'), ['tenantmember' => '', 'tenantparticipant' => '']);
        $sourcedata[] = array_merge($this->user('5'), ['tenantmember' => 'tenantid2', 'tenantparticipant' => null]);
        $sourcedata[] = array_merge($this->user('6'), ['tenantmember' => 'tenantid2', 'tenantparticipant' => '']);
        $sourcedata[] = array_merge($this->user('7'), ['tenantmember' => null, 'tenantparticipant' => 'tenantid2']);
        $sourcedata[] = array_merge($this->user('8'), ['tenantmember' => '', 'tenantparticipant' => 'tenantid2']);
        $sourcedata[] = array_merge($this->user('9'), ['tenantmember' => 'tenantid2', 'tenantparticipant' => 'tenantid2']); // Fail.

        $this->create_external_db_table();
        foreach ($sourcedata as $data) {
            $this->ext_dbconnection->insert_record($this->dbtable, (object)$data);
        }

        // Test the sanity check.
        $invalididnumbers = $this->check_sanity();
        self::assertCount(1, $invalididnumbers);

        // Check the sync log.
        $sync_log = $DB->get_records('totara_sync_log');
        self::assertCount(1, $sync_log);
        self::assertTrue(self::sync_log_contains('Only a tenant member or participant can be set, not both, for user idnum9', $sync_log));
    }


    /**
     * Test the sanity check check_and_process_tenantparticipant function
     */
    public function test_check_and_process_tenantparticipant() {
        global $DB;

        // Set the config.
        $this->set_source_config(array_merge($this->configdatabase, ['import_tenantparticipant' => '1']));
        $this->set_element_config(array_merge($this->config));

        // Create some tenants.
        $tenant1 = $this->tenantgenerator->create_tenant(['idnumber' => 'tenantid1']);
        $tenant2 = $this->tenantgenerator->create_tenant(['idnumber' => 'tenantid2']);
        $tenant3 = $this->tenantgenerator->create_tenant(['idnumber' => 'tenantid3']);

        // Create the database source.
        $sourcedata = [];
        $sourcedata[] = array_merge($this->user('1'), ['tenantparticipant' => null]);
        $sourcedata[] = array_merge($this->user('2'), ['tenantparticipant' => '']);
        $sourcedata[] = array_merge($this->user('3'), ['tenantparticipant' => 'tenantid1']);
        $sourcedata[] = array_merge($this->user('4'), ['tenantparticipant' => 'tenantid1,tenantid2']);
        $sourcedata[] = array_merge($this->user('5'), ['tenantparticipant' => 'tenantid1,tenantid2,tenantid3']);
        $sourcedata[] = array_merge($this->user('6'), ['tenantparticipant' => ' tenantid1 ,  tenantid2, tenantid3']);
        $sourcedata[] = array_merge($this->user('7'), ['tenantparticipant' => ' tenantid1, tenantid2,tenantid3, badidnumber']); // Fail.
        $sourcedata[] = array_merge($this->user('8'), ['tenantparticipant' => ' tenantid1, tenantid2,tenantid3, badidnumber']); // Fail.


        $this->create_external_db_table();
        foreach ($sourcedata as $data) {
            $this->ext_dbconnection->insert_record($this->dbtable, (object)$data);
        }

        // Test the sanity check.
        $invalididnumbers = $this->check_sanity(false);
        self::assertCount(2, $invalididnumbers);

        // Check the sync log.
        $sync_log = $DB->get_records('totara_sync_log');
        self::assertCount(2, $sync_log);
        self::assertTrue(self::sync_log_contains('Tenant participant idnumber badidnumber does not exist. Skipped user idnum7', $sync_log));
        self::assertTrue(self::sync_log_contains('Tenant participant idnumber badidnumber does not exist. Skipped user idnum8', $sync_log));

        // Check the source tenantparticipant sync table has been updated with the tenant ids.
        $tenantparticipant = $DB->get_field('totara_sync_user', 'tenantparticipant', ['idnumber' => 'idnum3']);
        self::assertEquals($tenant1->id, $tenantparticipant);

        $tenantparticipant = $DB->get_field('totara_sync_user', 'tenantparticipant', ['idnumber' => 'idnum4']);
        self::assertEquals($tenant1->id . ',' . $tenant2->id, $tenantparticipant);

        $tenantparticipant = $DB->get_field('totara_sync_user', 'tenantparticipant', ['idnumber' => 'idnum5']);
        self::assertEquals($tenant1->id . ',' . $tenant2->id . ',' . $tenant3->id, $tenantparticipant);

        $tenantparticipant = $DB->get_field('totara_sync_user', 'tenantparticipant', ['idnumber' => 'idnum6']);
        self::assertEquals($tenant1->id . ',' . $tenant2->id . ',' . $tenant3->id, $tenantparticipant);
    }

    /**
     * Test the tenantmember field
     */
    public function test_create_update_tenant_members() {
        global $DB;

        // Ensure whe have two users, Admin and Guest.
        self::assertEquals(2, $DB->count_records('user'));

        // Set the config.
        $this->set_source_config(array_merge($this->configdatabase, ['import_tenantmember' => '1']));
        $this->set_element_config(array_merge($this->config));

        // Create some tenants.
        $tenant1 = $this->tenantgenerator->create_tenant(['idnumber' => 'tenantid1']);
        $tenant2 = $this->tenantgenerator->create_tenant(['idnumber' => 'tenantid2']);
        $tenant3 = $this->tenantgenerator->create_tenant(['idnumber' => 'tenantid3']);

        // Create some users, without tenant membership.
        $this->getDataGenerator()->create_user(['idnumber' => 'idnum5', 'totarasync' => '1']);
        $this->getDataGenerator()->create_user(['idnumber' => 'idnum6', 'totarasync' => '1']);
        $this->getDataGenerator()->create_user(['idnumber' => 'idnum7', 'totarasync' => '1']);
        $this->getDataGenerator()->create_user(['idnumber' => 'idnum8', 'totarasync' => '1']);

        // Create some more users, with tenant membership.
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'idnum9', 'totarasync' => '1']);
        totara_tenant\local\util::migrate_user_to_tenant($user->id, $tenant1->id);
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'idnum10', 'totarasync' => '1']);
        totara_tenant\local\util::migrate_user_to_tenant($user->id, $tenant1->id);
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'idnum11', 'totarasync' => '1']);
        totara_tenant\local\util::migrate_user_to_tenant($user->id, $tenant1->id);
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'idnum12', 'totarasync' => '1']);
        totara_tenant\local\util::migrate_user_to_tenant($user->id, $tenant1->id);
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'idnum13', 'totarasync' => '1']);
        totara_tenant\local\util::migrate_user_to_tenant($user->id, $tenant1->id);

        // Ensure whe have the additional users.
        self::assertEquals(11, $DB->count_records('user'));

        //
        // Create the database source.
        //
        $sourcedata = [];

        // Users to be created.
        $sourcedata[] = array_merge($this->user('1'), ['tenantmember' => null]);
        $sourcedata[] = array_merge($this->user('2'), ['tenantmember' => '']);
        $sourcedata[] = array_merge($this->user('3'), ['tenantmember' => 'badidnumber']);
        $sourcedata[] = array_merge($this->user('4'), ['tenantmember' => 'tenantid2']);

        // Users to be updated, with tenancy not currently set.
        $sourcedata[] = array_merge($this->user('5'), ['tenantmember' => null]);
        $sourcedata[] = array_merge($this->user('6'), ['tenantmember' => '']);
        $sourcedata[] = array_merge($this->user('7'), ['tenantmember' => 'badidnumber']);
        $sourcedata[] = array_merge($this->user('8'), ['tenantmember' => 'tenantid2']);

        // Users to be updated, with tenancy set.
        $sourcedata[] = array_merge($this->user('9'), ['tenantmember' => null]);
        $sourcedata[] = array_merge($this->user('10'), ['tenantmember' => '']);
        $sourcedata[] = array_merge($this->user('11'), ['tenantmember' => 'badidnumber']);
        $sourcedata[] = array_merge($this->user('12'), ['tenantmember' => 'tenantid1']);
        $sourcedata[] = array_merge($this->user('13'), ['tenantmember' => 'tenantid2']);

        $this->create_external_db_table();
        foreach ($sourcedata as $data) {
            $this->ext_dbconnection->insert_record($this->dbtable, (object)$data);
        }

        // Run the sync.
        $this->get_element()->sync();

        // Check the sync log.
        $sync_log = $DB->get_records('totara_sync_log');
        self::assertCount(15, $sync_log);
        self::assertTrue(self::sync_log_contains('created user idnum1', $sync_log));
        self::assertTrue(self::sync_log_contains('created user idnum2', $sync_log));
        self::assertTrue(self::sync_log_contains('Tenant idnumber badidnumber does not exist. Skipped user idnum3', $sync_log));
        self::assertTrue(self::sync_log_contains('created user idnum4', $sync_log));
        self::assertTrue(self::sync_log_contains('updated user idnum5', $sync_log));
        self::assertTrue(self::sync_log_contains('updated user idnum6', $sync_log));
        self::assertTrue(self::sync_log_contains('Tenant idnumber badidnumber does not exist. Skipped user idnum7', $sync_log));
        self::assertTrue(self::sync_log_contains('updated user idnum8', $sync_log));
        self::assertTrue(self::sync_log_contains('updated user idnum9', $sync_log));
        self::assertTrue(self::sync_log_contains('updated user idnum10', $sync_log));
        self::assertTrue(self::sync_log_contains('Tenant idnumber badidnumber does not exist. Skipped user idnum11', $sync_log));
        self::assertTrue(self::sync_log_contains('updated user idnum12', $sync_log));
        self::assertTrue(self::sync_log_contains('updated user idnum13', $sync_log));

        // 3 Users should have been created.
        self::assertEquals(14, $DB->count_records('user'));

        // Check the users.
        $user1 = $DB->get_record('user', ['idnumber' => 'idnum1']);
        $user2 = $DB->get_record('user', ['idnumber' => 'idnum2']);
        $user3 = $DB->get_record('user', ['idnumber' => 'idnum3']);
        $user4 = $DB->get_record('user', ['idnumber' => 'idnum4']);
        $user5 = $DB->get_record('user', ['idnumber' => 'idnum5']);
        $user6 = $DB->get_record('user', ['idnumber' => 'idnum6']);
        $user7 = $DB->get_record('user', ['idnumber' => 'idnum7']);
        $user8 = $DB->get_record('user', ['idnumber' => 'idnum8']);
        $user9 = $DB->get_record('user', ['idnumber' => 'idnum9']);
        $user10 = $DB->get_record('user', ['idnumber' => 'idnum10']);
        $user11 = $DB->get_record('user', ['idnumber' => 'idnum11']);
        $user12 = $DB->get_record('user', ['idnumber' => 'idnum12']);
        $user13 = $DB->get_record('user', ['idnumber' => 'idnum13']);
        self::assertEquals(null, $user1->tenantid);
        self::assertEquals(null, $user2->tenantid);
        self::assertFalse($user3);
        self::assertEquals($tenant2->id, $user4->tenantid);
        self::assertEquals(null, $user5->tenantid);
        self::assertEquals(null, $user6->tenantid);
        self::assertEquals(null, $user7->tenantid);
        self::assertEquals($tenant2->id, $user8->tenantid);
        self::assertEquals($tenant1->id, $user9->tenantid);
        self::assertEquals(null, $user10->tenantid);
        self::assertEquals($tenant1->id, $user11->tenantid);
        self::assertEquals($tenant1->id, $user12->tenantid);
        self::assertEquals($tenant2->id, $user13->tenantid);

        // Check the tenant membership.
        $participation = totara_tenant\local\util::get_user_participation($user1->id);
        self::assertCount(0, $participation);
        $participation = totara_tenant\local\util::get_user_participation($user2->id);
        self::assertCount(0, $participation);
        $participation = totara_tenant\local\util::get_user_participation($user4->id);
        self::assertEquals($tenant2->id, current($participation));
        $participation = totara_tenant\local\util::get_user_participation($user5->id);
        self::assertCount(0, $participation);
        $participation = totara_tenant\local\util::get_user_participation($user6->id);
        self::assertCount(0, $participation);
        $participation = totara_tenant\local\util::get_user_participation($user7->id);
        self::assertCount(0, $participation);
        $participation = totara_tenant\local\util::get_user_participation($user8->id);
        self::assertEquals($tenant2->id, current($participation));
        $participation = totara_tenant\local\util::get_user_participation($user9->id);
        self::assertEquals($tenant1->id, current($participation));
        $participation = totara_tenant\local\util::get_user_participation($user10->id);
        self::assertCount(0, $participation);
        $participation = totara_tenant\local\util::get_user_participation($user11->id);
        self::assertEquals($tenant1->id, current($participation));
        $participation = totara_tenant\local\util::get_user_participation($user12->id);
        self::assertEquals($tenant1->id, current($participation));
        $participation = totara_tenant\local\util::get_user_participation($user13->id);
        self::assertEquals($tenant2->id, current($participation));
    }

    /**
     * Test the tenantparticipant field
     */
    public function test_create_update_tenant_participation() {
        global $DB;

        // Ensure whe have two users, Admin and Guest.
        self::assertEquals(2, $DB->count_records('user'));

        // Set the config.
        $this->set_source_config(array_merge($this->configdatabase, ['import_tenantparticipant' => '1']));
        $this->set_element_config(array_merge($this->config));

        // Create some tenants.
        $tenant1 = $this->tenantgenerator->create_tenant(['idnumber' => 'tenantid1']);
        $tenant2 = $this->tenantgenerator->create_tenant(['idnumber' => 'tenantid2']);
        $tenant3 = $this->tenantgenerator->create_tenant(['idnumber' => 'tenantid3']);

        // Create some users, without tenant participations.
        $this->getDataGenerator()->create_user(['idnumber' => 'idnum9', 'totarasync' => '1']);
        $this->getDataGenerator()->create_user(['idnumber' => 'idnum10', 'totarasync' => '1']);
        $this->getDataGenerator()->create_user(['idnumber' => 'idnum11', 'totarasync' => '1']);
        $this->getDataGenerator()->create_user(['idnumber' => 'idnum12', 'totarasync' => '1']);
        $this->getDataGenerator()->create_user(['idnumber' => 'idnum13', 'totarasync' => '1']);

        // Create some more users, with tenant participations.
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'idnum14', 'totarasync' => '1']);
        totara_tenant\local\util::set_user_participation($user->id, [$tenant1->id, $tenant2->id]);
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'idnum15', 'totarasync' => '1']);
        totara_tenant\local\util::set_user_participation($user->id, [$tenant1->id, $tenant2->id]);
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'idnum16', 'totarasync' => '1']);
        totara_tenant\local\util::set_user_participation($user->id, [$tenant1->id, $tenant2->id]);
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'idnum17', 'totarasync' => '1']);
        totara_tenant\local\util::set_user_participation($user->id, [$tenant1->id, $tenant2->id]);
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'idnum18', 'totarasync' => '1']);
        totara_tenant\local\util::set_user_participation($user->id, [$tenant1->id, $tenant2->id]);

        // Ensure whe have the additional users.
        self::assertEquals(12, $DB->count_records('user'));

        //
        // Create the database source.
        //
        $sourcedata = [];

        // Users to be created.

        $sourcedata[] = array_merge($this->user('1'), ['tenantparticipant' => null]);
        $sourcedata[] = array_merge($this->user('2'), ['tenantparticipant' => '']);
        $sourcedata[] = array_merge($this->user('3'), ['tenantparticipant' => 'badidnumber']);
        $sourcedata[] = array_merge($this->user('4'), ['tenantparticipant' => 'tenantid2']);
        $sourcedata[] = array_merge($this->user('5'), ['tenantparticipant' => 'tenantid1,tenantid2']);
        $sourcedata[] = array_merge($this->user('6'), ['tenantparticipant' => 'tenantid1,tenantid1,tenantid2']);
        $sourcedata[] = array_merge($this->user('7'), ['tenantparticipant' => 'tenantid1,tenantid2,badidnumber']);
        $sourcedata[] = array_merge($this->user('8'), ['tenantparticipant' => ',,,,,']);

        // Users to be updated, with tenancy not currently set.
        $sourcedata[] = array_merge($this->user('9'), ['tenantparticipant' => null]);
        $sourcedata[] = array_merge($this->user('10'), ['tenantparticipant' => '']);
        $sourcedata[] = array_merge($this->user('11'), ['tenantparticipant' => 'badidnumber']);
        $sourcedata[] = array_merge($this->user('12'), ['tenantparticipant' => 'tenantid2']);
        $sourcedata[] = array_merge($this->user('13'), ['tenantparticipant' => 'tenantid1,tenantid2']);

        // Users to be updated, with tenancy set.
        $sourcedata[] = array_merge($this->user('14'), ['tenantparticipant' => null]);
        $sourcedata[] = array_merge($this->user('15'), ['tenantparticipant' => '']);
        $sourcedata[] = array_merge($this->user('16'), ['tenantparticipant' => 'badidnumber']);
        $sourcedata[] = array_merge($this->user('17'), ['tenantparticipant' => 'tenantid1']);
        $sourcedata[] = array_merge($this->user('18'), ['tenantparticipant' => 'tenantid1,tenantid2']);

        $this->create_external_db_table();
        foreach ($sourcedata as $data) {
            $this->ext_dbconnection->insert_record($this->dbtable, (object)$data);
        }

        // Run the sync.
        $this->get_element()->sync();

        // Check the sync log.
        $sync_log = $DB->get_records('totara_sync_log');
        self::assertCount(20, $sync_log);

        self::assertTrue(self::sync_log_contains('created user idnum1', $sync_log));
        self::assertTrue(self::sync_log_contains('created user idnum2', $sync_log));
        self::assertTrue(self::sync_log_contains('Tenant participant idnumber badidnumber does not exist. Skipped user idnum3', $sync_log));
        self::assertTrue(self::sync_log_contains('created user idnum4', $sync_log));
        self::assertTrue(self::sync_log_contains('created user idnum5', $sync_log));
        self::assertTrue(self::sync_log_contains('created user idnum6', $sync_log));
        self::assertTrue(self::sync_log_contains('Tenant participant idnumber badidnumber does not exist. Skipped user idnum7', $sync_log));
        self::assertTrue(self::sync_log_contains('created user idnum8', $sync_log));
        self::assertTrue(self::sync_log_contains('updated user idnum9', $sync_log));
        self::assertTrue(self::sync_log_contains('updated user idnum10', $sync_log));
        self::assertTrue(self::sync_log_contains('Tenant participant idnumber badidnumber does not exist. Skipped user idnum11', $sync_log));
        self::assertTrue(self::sync_log_contains('updated user idnum12', $sync_log));
        self::assertTrue(self::sync_log_contains('updated user idnum13', $sync_log));
        self::assertTrue(self::sync_log_contains('updated user idnum14', $sync_log));
        self::assertTrue(self::sync_log_contains('updated user idnum15', $sync_log));
        self::assertTrue(self::sync_log_contains('Tenant participant idnumber badidnumber does not exist. Skipped user idnum16', $sync_log));
        self::assertTrue(self::sync_log_contains('updated user idnum17', $sync_log));
        self::assertTrue(self::sync_log_contains('updated user idnum18', $sync_log));

        // 3 Users should have been created.
        self::assertEquals(18, $DB->count_records('user'));

        // Check the users.
        $user1 = $DB->get_record('user', ['idnumber' => 'idnum1']);
        $user2 = $DB->get_record('user', ['idnumber' => 'idnum2']);
        $user3 = $DB->get_record('user', ['idnumber' => 'idnum3']);
        $user4 = $DB->get_record('user', ['idnumber' => 'idnum4']);
        $user5 = $DB->get_record('user', ['idnumber' => 'idnum5']);
        $user6 = $DB->get_record('user', ['idnumber' => 'idnum6']);
        $user7 = $DB->get_record('user', ['idnumber' => 'idnum7']);
        $user8 = $DB->get_record('user', ['idnumber' => 'idnum8']);
        $user9 = $DB->get_record('user', ['idnumber' => 'idnum9']);
        $user10 = $DB->get_record('user', ['idnumber' => 'idnum10']);
        $user11 = $DB->get_record('user', ['idnumber' => 'idnum11']);
        $user12 = $DB->get_record('user', ['idnumber' => 'idnum12']);
        $user13 = $DB->get_record('user', ['idnumber' => 'idnum13']);
        $user14 = $DB->get_record('user', ['idnumber' => 'idnum14']);
        $user15 = $DB->get_record('user', ['idnumber' => 'idnum15']);
        $user16 = $DB->get_record('user', ['idnumber' => 'idnum16']);
        $user17 = $DB->get_record('user', ['idnumber' => 'idnum17']);
        $user18 = $DB->get_record('user', ['idnumber' => 'idnum18']);
        self::assertEquals(null, $user1->tenantid);
        self::assertEquals(null, $user2->tenantid);
        self::assertFalse($user3);
        self::assertEquals(null, $user4->tenantid);
        self::assertEquals(null, $user5->tenantid);
        self::assertEquals(null, $user6->tenantid);
        self::assertFalse($user7);
        self::assertEquals(null, $user8->tenantid);
        self::assertEquals(null, $user9->tenantid);
        self::assertEquals(null, $user10->tenantid);
        self::assertEquals(null, $user11->tenantid);
        self::assertEquals(null, $user12->tenantid);
        self::assertEquals(null, $user13->tenantid);
        self::assertEquals(null, $user14->tenantid);
        self::assertEquals(null, $user15->tenantid);
        self::assertEquals(null, $user16->tenantid);
        self::assertEquals(null, $user17->tenantid);
        self::assertEquals(null, $user18->tenantid);

        // Check the tenant membership.
        $participation = totara_tenant\local\util::get_user_participation($user1->id);
        self::assertCount(0, $participation);
        $participation = totara_tenant\local\util::get_user_participation($user2->id);
        self::assertCount(0, $participation);
        $participation = totara_tenant\local\util::get_user_participation($user4->id);
        self::assertEquals($tenant2->id, current($participation));
        $participation = totara_tenant\local\util::get_user_participation($user5->id);
        self::assertEquals([$tenant1->id => $tenant1->id, $tenant2->id => $tenant2->id], $participation);
        $participation = totara_tenant\local\util::get_user_participation($user6->id);
        self::assertEquals([$tenant1->id => $tenant1->id, $tenant2->id => $tenant2->id], $participation);
        $participation = totara_tenant\local\util::get_user_participation($user8->id);
        self::assertCount(0, $participation);
        $participation = totara_tenant\local\util::get_user_participation($user9->id);
        self::assertCount(0, $participation);
        $participation = totara_tenant\local\util::get_user_participation($user10->id);
        self::assertCount(0, $participation);
        $participation = totara_tenant\local\util::get_user_participation($user11->id);
        self::assertCount(0, $participation);
        $participation = totara_tenant\local\util::get_user_participation($user12->id);
        self::assertEquals($tenant2->id, current($participation));
        $participation = totara_tenant\local\util::get_user_participation($user13->id);
        self::assertEquals([$tenant1->id => $tenant1->id, $tenant2->id => $tenant2->id], $participation);
        $participation = totara_tenant\local\util::get_user_participation($user14->id);
        self::assertEquals([$tenant1->id => $tenant1->id, $tenant2->id => $tenant2->id], $participation);
        $participation = totara_tenant\local\util::get_user_participation($user15->id);
        self::assertCount(0, $participation);
        $participation = totara_tenant\local\util::get_user_participation($user16->id);
        self::assertEquals([$tenant1->id => $tenant1->id, $tenant2->id => $tenant2->id], $participation);
        $participation = totara_tenant\local\util::get_user_participation($user17->id);
        self::assertEquals($tenant1->id, current($participation));
        $participation = totara_tenant\local\util::get_user_participation($user18->id);
        self::assertEquals([$tenant1->id => $tenant1->id, $tenant2->id => $tenant2->id], $participation);
    }
}
