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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package ml_recommender
 */
defined('MOODLE_INTERNAL') || die();

use core_phpunit\testcase;

use ml_recommender\local\csv\writer;
use ml_recommender\local\environment;
use ml_recommender\local\export\user_data;

use totara_competency\entity\assignment;
use totara_competency\expand_task;
use totara_competency\task\competency_aggregation_all;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale;
use totara_hierarchy\entity\scale_value;
use totara_job\job_assignment;

/**
 * @group ml_recommender
 */
class export_user_data_testcase extends testcase {
    /**
     * @var string
     */
    private $data_path;

    /**
     * @return void
     */
    protected function setUp(): void {
        $this->data_path = environment::get_data_path();
        $this->data_path = rtrim($this->data_path,  "/\\");

        if (!is_dir($this->data_path)) {
            make_writable_directory($this->data_path);
        }
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        global $CFG;

        if (is_dir($this->data_path)) {
            require_once("{$CFG->dirroot}/lib/filelib.php");

            // Delete the data path.
            fulldelete($this->data_path);
        }

        $this->data_path = null;
    }

    /**
     * @return void
     */
    public function test_export_user_data(): void {
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $csv_file = "{$this->data_path}/boom.csv";
        $writer = new writer($csv_file);

        $exporter = new user_data();
        $result = $exporter->export($writer);
        self::assertTrue($result);

        $writer->close();
        self::assertTrue(file_exists($csv_file));

        // Due to the records fetch from database can be randomly ordered, hence its not
        // reliable to check expected contents against actual content. However, we can
        // do some sort of different checks such as partial checks.
        $actual_content = file_get_contents($csv_file);
        self::assertStringContainsString("user_id,lang", $actual_content);

        self::assertStringContainsString("{$user_one->id},{$user_one->lang}", $actual_content);
        self::assertStringContainsString("{$user_two->id},{$user_two->lang}", $actual_content);

        $admin_user = get_admin();
        self::assertStringContainsString("{$admin_user->id},{$admin_user->lang}", $actual_content);

        $guest_user = guest_user();
        self::assertStringNotContainsString("{$guest_user->id},{$guest_user->lang}", $actual_content);

        // There should be 4 rows in total from the actual CSV content.
        self::assertEquals(4, substr_count($actual_content, "\n"));
    }

    /**
     * @return void
     */
    public function test_cannot_export_data(): void {
        $generator = self::getDataGenerator();

        /** @var totara_tenant_generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();

        $csv_file = "{$this->data_path}/file.csv";
        $writer = new writer($csv_file);

        $exporter = new user_data();
        $exporter->set_tenant($tenant);

        $result = $exporter->export($writer);
        self::assertFalse($result);

        // Despite of not writing to the file, the file was still created
        // because it was opened for any writes.
        self::assertTrue(file_exists($csv_file));
        self::assertEquals(1, substr_count(file_get_contents($csv_file), "\n"));

        $writer->close();
    }

    /**
     * Test for highest proficiency per competency id.
     *
     */
    public function test_get_highest_proficiencies(): void {
        $test_cases = [
            'test_null' => [
                'competencies' => null,
                'proficiencies' => null,
                'expect' => ''
            ],
            'test_empty_string' => [
                'competencies' => '',
                'proficiencies' => '',
                'expect' => ''
            ],
            'test_no_pipe' => [
                'competencies' => '123',
                'proficiencies' => '45',
                'expect' => '123:45'
            ],
            'test_single_pipe_no_duplicate' => [
                'competencies' => '123|234',
                'proficiencies' => '45|56',
                'expect' => '123:45|234:56'
            ],
            'test_single_pipe_with_duplicate' => [
                'competencies' => '123|123',
                'proficiencies' => '45|12',
                'expect' => '123:45'
            ],
            'test_multiple_pipes_no_duplicates' => [
                'competencies' => '123|234|435',
                'proficiencies' => '45|56|67',
                'expect' => '123:45|234:56|435:67'
            ],
            'test_multiple_pipes_with_duplicates' => [
                'competencies' => '123|234|123|234',
                'proficiencies' => '45|56|67|12',
                'expect' => '123:67|234:56'
            ],
        ];

        // Set up reflected class.
        $refl_user_data = new ReflectionClass(user_data::class);
        $method = $refl_user_data->getMethod('get_highest_proficiencies');
        $method->setAccessible(true);
        $user_data = new user_data();

        // Run the test cases.
        foreach ($test_cases as $test_label => $test_values) {
            self::assertEquals($test_values['expect'], $method->invoke($user_data, $test_values['competencies'], $test_values['proficiencies']));
        }
    }

    /**
     * Test for unique items in pipe-separated list.
     *
     */
    public function test_get_unique_values(): void {
        $test_cases = [
            'test_null' => [
                'input' => null,
                'expect' => '',
            ],
            'test_empty_string' => [
                'input' => '',
                'expect' => '',
            ],
            'test_no_pipes' => [
                'input' => '123234',
                'expect' => '123234',
            ],
            'test_single_pipes_no_duplicate' => [
                'input' => '123|234',
                'expect' => '123|234',
            ],
            'test_single_pipe_with_duplicate' => [
                'input' => '123|123',
                'expect' => '123',
            ],
            'test_multiple_pipes_no_duplicate' => [
                'input' => '123|234|345',
                'expect' => '123|234|345',
            ],
            'test_multiple_pipe_with_duplicates' => [
                'input' => '123|234|345|234|123',
                'expect' => '123|234|345',
            ],
        ];

        // Set up reflected class.
        $refl_user_data = new ReflectionClass(user_data::class);
        $method = $refl_user_data->getMethod('get_unique_values');
        $method->setAccessible(true);
        $user_data = new user_data();

        // Run the test cases.
        foreach ($test_cases as $test_label => $test_values) {
            self::assertEquals($test_values['expect'], $method->invoke($user_data, $test_values['input']));
        }
    }

    /**
     * Set up a bunch of user-related data with which to populate user data export CSV:
     *      Tenants
     *      Users
     *      Badges
     *      Competencies
     *      Positions
     *
     * Data structure is roughly like this:
     * Tenant 1 - 3 users: user2 is flagged deleted, user1 is manager, user3 has no aspirational position
     * Tenant 2 - no users
     * Tenant 3 - 3 active users: user3 is manager, user2 has no aspirational position
     *
     * Organisation framework  - Faked, using tenant id
     *
     * Position framework - Tenants 1 & 3 have their own frameworks in which their users get assigned positions.
     *
     * Badges - Tenant 1:
     *              Badge1 - user2 & user3
     *              Badge2 - user3
     *              Badge3 - user3 (badge inactive)
     *              Badge4 - unissued, badge active
     *              Badge5 - unissued, badge inactive
     *
     *        - Tenant 2:
     *              Badge6 - user1 & user3
     *              Badge7 - user1 & user2
     *              Badge8 - user2 & user3 (badge inactive)
     *              Badge9 - user3 (badge inactive)
     *
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_export_tenant_user(): void {
        global $DB;

        $generator = self::getDataGenerator();

        // Admin user.
        $this->setAdminUser();
        $admin = get_admin();

        /** @var totara_tenant_generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        // Create 3 tenants, but we'll only have users in tenants 1 and 3 (tenant2 will remain empty).
        $tenant1 = $tenant_generator->create_tenant(['name' => 'TestTenant1']);
        $tenant2 = $tenant_generator->create_tenant(['name' => 'TestTenant2']);
        $tenant3 = $tenant_generator->create_tenant(['name' => 'TestTenant3']);

        // Create 3 users for tenant1.
        $user1_t1 = $generator->create_user([
            'tenantid' => $tenant1->id,
            'interests' => ['Int1', 'Int2'],
            'lang' => 'es',
            'city' => 'Ncoatom, Bata',
            'country' => 'GQ',
            'description' => '<p>Todo es realmente genial</p>',
            'descriptionformat' => FORMAT_HTML
        ]);

        $user2_t1 = $generator->create_user([
            'tenantid' => $tenant1->id,
            'deleted' => 1
        ]);

        $user3_t1 = $generator->create_user([
            'tenantid' => $tenant1->id,
            'interests' => ['Int2', 'Int3'],
            'lang' => 'sv',
            'city' => 'Avesta',
            'country' => 'SE',
            'description' => '<p>Allt är riktigt coolt</p>',
            'descriptionformat' => FORMAT_HTML,
        ]);

        // Create 3 users for Tenant3.
        $user1_t3 = $generator->create_user([
            'tenantid' => $tenant3->id,
            'lang' => 'en',
            'city' => 'Hororata',
            'country' => 'NZ',
            'description' => '<p>Everything is really good</p>',
            'descriptionformat' => FORMAT_HTML
        ]);

        $user2_t3 = $generator->create_user([
            'tenantid' => $tenant3->id,
            'interests' => ['Int4', 'Int5'],
            'lang' => 'es',
            'city' => 'Ncoatom, Bata',
            'country' => 'GQ',
            'description' => '<p>Todo es realmente genial</p>',
            'descriptionformat' => FORMAT_HTML
        ]);

        $user3_t3 = $generator->create_user([
            'tenantid' => $tenant3->id,
            'interests' => ['Int4', 'Int6', 'Int7', 'Int8'],
            'lang' => 'it',
            'city' => 'Ostuni, Puglia',
            'country' => 'IT',
            'description' => '<p>Tutto è davvero fantastico</p>',
            'descriptionformat' => FORMAT_HTML
        ]);

        // Badges.
        $badge_generator = $this->getDataGenerator()->get_plugin_generator('core_badges');

        // Badges for tenant 1 users.
        $badge_id = $badge_generator->create_badge($admin->id, ['status' => BADGE_STATUS_ACTIVE]);
        $badge1 = new badge($badge_id);
        $badge1->issue($user2_t1->id, true);
        $badge1->issue($user3_t1->id, true);

        $badge_id = $badge_generator->create_badge($admin->id, ['status' => BADGE_STATUS_ACTIVE]);
        $badge2 = new badge($badge_id);
        $badge2->issue($user3_t1->id, true);

        $badge_id = $badge_generator->create_badge($admin->id, ['status' => BADGE_STATUS_INACTIVE]);
        $badge3 = new badge($badge_id);
        $badge3->issue($user3_t1->id, true);

        // Unissued badges.
        $badge4_id_unissued_active_t1 = $badge_generator->create_badge($admin->id, ['status' => BADGE_STATUS_ACTIVE]);
        $badge5_id_unissued_inactive_t1 = $badge_generator->create_badge($admin->id, ['status' => BADGE_STATUS_INACTIVE]);

        // Badges for tenant 3 users.
        $badge_id = $badge_generator->create_badge($admin->id, ['status' => BADGE_STATUS_ACTIVE]);
        $badge6 = new badge($badge_id);
        $badge6->issue($user1_t3->id, true);
        $badge6->issue($user3_t3->id, true);

        $badge_id = $badge_generator->create_badge($admin->id, ['status' => BADGE_STATUS_ACTIVE]);
        $badge7 = new badge($badge_id);
        $badge7->issue($user1_t3->id, true);
        $badge7->issue($user2_t3->id, true);

        $badge_id = $badge_generator->create_badge($admin->id, ['status' => BADGE_STATUS_INACTIVE]);
        $badge8 = new badge($badge_id);
        $badge8->issue($user2_t3->id, true);
        $badge8->issue($user3_t3->id, true);

        $badge_id = $badge_generator->create_badge($admin->id, ['status' => BADGE_STATUS_INACTIVE]);
        $badge9 = new badge($badge_id);
        $badge9->issue($user3_t3->id, true);

        // Handle the competencies setup and scheduled tasks.
        $users = [];
        $users[] = [
            'manager' => $user1_t1,
            'other' => [
                $user2_t1,
                $user3_t1
            ]
        ];
        $users[] = [
            'manager' => $user3_t3,
            'other' => [
                $user1_t3,
                $user2_t3
            ]
        ];

        $this->competency_aggregation_single_manual($users[0], $tenant1);
        $this->competency_aggregation_single_manual($users[1], $tenant3);

        // Set up positions.
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $t1_pos_fw = $hierarchy_generator->create_pos_frame([]);
        $t3_pos_fw = $hierarchy_generator->create_pos_frame([]);

        $user1_t1->pos = $hierarchy_generator->create_pos(['frameworkid' => $t1_pos_fw->id, 'fullname' => 'fw1p1']);
        $user2_t1->pos = $hierarchy_generator->create_pos(['frameworkid' => $t1_pos_fw->id, 'fullname' => 'fw1p2']);
        $user3_t1->pos = $hierarchy_generator->create_pos(['frameworkid' => $t1_pos_fw->id, 'fullname' => 'fw1p3']);
        $user1_t3->pos = $hierarchy_generator->create_pos(['frameworkid' => $t3_pos_fw->id, 'fullname' => 'fw2p1']);
        $user2_t3->pos = $hierarchy_generator->create_pos(['frameworkid' => $t3_pos_fw->id, 'fullname' => 'fw2p2']);
        $user3_t3->pos = $hierarchy_generator->create_pos(['frameworkid' => $t3_pos_fw->id, 'fullname' => 'fw2p3']);

        // Update competency records with position data and use tenant id for organisation.
        $DB->execute('UPDATE {job_assignment} SET positionid = ' . $user1_t1->pos->id . ', organisationid = ' . $tenant1->id . ' WHERE userid = ' . $user1_t1->id);
        $DB->execute('UPDATE {job_assignment} SET positionid = ' . $user2_t1->pos->id . ', organisationid = ' . $tenant1->id . ' WHERE userid = ' . $user2_t1->id);
        $DB->execute('UPDATE {job_assignment} SET positionid = ' . $user3_t1->pos->id . ', organisationid = ' . $tenant1->id . ' WHERE userid = ' . $user3_t1->id);
        $DB->execute('UPDATE {job_assignment} SET positionid = ' . $user1_t3->pos->id . ', organisationid = ' . $tenant3->id . ' WHERE userid = ' . $user1_t3->id);
        $DB->execute('UPDATE {job_assignment} SET positionid = ' . $user2_t3->pos->id . ', organisationid = ' . $tenant3->id . ' WHERE userid = ' . $user2_t3->id);
        $DB->execute('UPDATE {job_assignment} SET positionid = ' . $user3_t3->pos->id . ', organisationid = ' . $tenant3->id . ' WHERE userid = ' . $user3_t3->id);

        // Set user aspirational positions (excludes $user3_t1 & $user2_t2).
        totara_gap_assign_aspirational_position($user1_t1->id, $user2_t1->pos->id);
        totara_gap_assign_aspirational_position($user2_t1->id, $user3_t1->pos->id);
        totara_gap_assign_aspirational_position($user1_t3->id, $user3_t3->pos->id);
        totara_gap_assign_aspirational_position($user3_t3->id, $user2_t3->pos->id);

        // Set up exporter data.
        $exports = [];
        $exports[] = [
            'filename' => "{$this->data_path}/tenant_' . $tenant1->id . '.csv",
            'tenant' => $tenant1,
        ];
        $exports[] = [
            'filename' => "{$this->data_path}/tenant_' . $tenant2->id . '.csv",
            'tenant' => $tenant2,
        ];
        $exports[] = [
            'filename' => "{$this->data_path}/tenant_' . $tenant3->id . '.csv",
            'tenant' => $tenant3,
        ];

        // Perform exports and do some basic checks on CSV file content.
        $csv_data = [];
        foreach ($exports as $export) {
            $this->create_export($export);
            $this->check_export_basic_content($export);

            // Load the CSV data into associative arrays.
            $csv_data[] = $this->get_csv_to_array($export);
        }

        // Base export works (superficially) as expected.  Let's take a
        // deeper look now at the data elements that comprise the output.

        // Note the following mapping:
        //      $csv_data[0] points to Tenant1 user data;
        //      $csv_data[1] points to Tenant2 user data;
        //      $csv_data[2] points to Tenant3 user data.

        // Expected user record counts.
        self::assertEquals(2, count($csv_data[0]));
        self::assertEquals(0, count($csv_data[1]));
        self::assertEquals(3, count($csv_data[2]));

        // Correct users in tenant 1.
        self::assertEquals($user1_t1->id, $csv_data[0][0]['user_id']);
        self::assertEquals($user3_t1->id, $csv_data[0][1]['user_id']);

        // Correct users in tenant 3.
        self::assertEquals($user1_t3->id, $csv_data[2][0]['user_id']);
        self::assertEquals($user2_t3->id, $csv_data[2][1]['user_id']);
        self::assertEquals($user3_t3->id, $csv_data[2][2]['user_id']);

        // User string data matches.
        $this->check_user_data_strings($user1_t1, $csv_data[0][0]);
        $this->check_user_data_strings($user3_t1, $csv_data[0][1]);
        $this->check_user_data_strings($user1_t3, $csv_data[2][0]);
        $this->check_user_data_strings($user2_t3, $csv_data[2][1]);
        $this->check_user_data_strings($user3_t3, $csv_data[2][2]);

        // Check that expected interests are listed per user.
        $this->check_listed_interests($user1_t1, $csv_data[0][0]);
        $this->check_listed_interests($user3_t1, $csv_data[0][1]);
        $this->check_listed_interests($user1_t3, $csv_data[2][0]);
        $this->check_listed_interests($user2_t3, $csv_data[2][1]);
        $this->check_listed_interests($user3_t3, $csv_data[2][2]);

        // Check aspirational positions per user.
        $this->check_aspirational_positions($user1_t1, $csv_data[0][0]);
        $this->check_aspirational_positions($user3_t1, $csv_data[0][1]);
        $this->check_aspirational_positions($user1_t3, $csv_data[2][0]);
        $this->check_aspirational_positions($user2_t3, $csv_data[2][1]);
        $this->check_aspirational_positions($user3_t3, $csv_data[2][2]);

        // Check job assignments and competency data.
        $this->check_job_assignemnts_etc($user1_t1, $csv_data[0][0]);
        $this->check_job_assignemnts_etc($user3_t1, $csv_data[0][1]);
        $this->check_job_assignemnts_etc($user1_t3, $csv_data[2][0]);
        $this->check_job_assignemnts_etc($user2_t3, $csv_data[2][1]);
        $this->check_job_assignemnts_etc($user3_t3, $csv_data[2][2]);

        // Ensure no-one has an unissued badge - Tenant 1.
        self::assertStringNotContainsString($badge4_id_unissued_active_t1, $csv_data[0][0]['badges']);
        self::assertStringNotContainsString($badge5_id_unissued_inactive_t1, $csv_data[0][0]['badges']);
        self::assertStringNotContainsString($badge4_id_unissued_active_t1, $csv_data[0][1]['badges']);
        self::assertStringNotContainsString($badge5_id_unissued_inactive_t1, $csv_data[0][1]['badges']);

        // Ensure no-one has an unissued badge - Tenant 3.
        self::assertStringNotContainsString($badge4_id_unissued_active_t1, $csv_data[2][0]['badges']);
        self::assertStringNotContainsString($badge5_id_unissued_inactive_t1, $csv_data[2][0]['badges']);
        self::assertStringNotContainsString($badge4_id_unissued_active_t1, $csv_data[2][1]['badges']);
        self::assertStringNotContainsString($badge5_id_unissued_inactive_t1, $csv_data[2][1]['badges']);
        self::assertStringNotContainsString($badge4_id_unissued_active_t1, $csv_data[2][2]['badges']);
        self::assertStringNotContainsString($badge5_id_unissued_inactive_t1, $csv_data[2][2]['badges']);

        // Check that expected badges are listed per user.
        $this->check_listed_badges($user1_t1, $csv_data[0][0]);
        $this->check_listed_badges($user3_t1, $csv_data[0][1]);
        $this->check_listed_badges($user1_t3, $csv_data[2][0]);
        $this->check_listed_badges($user2_t3, $csv_data[2][1]);
        $this->check_listed_badges($user3_t3, $csv_data[2][2]);
    }

    /**
     * Create the user data csv export file.
     *
     * @param array $export
     * @throws coding_exception
     */
    protected function create_export(array $export) {
        $tenant_writer = new writer($export['filename']);
        $tenant_exporter = new user_data();
        $tenant_exporter->set_tenant($export['tenant']);
        $tenant_result = $tenant_exporter->export($tenant_writer);
        $tenant_writer->close();

        // Tenant2 is empty, so it should fail here.
        if ($export['tenant']->name === 'TestTenant2') {
            self::assertFalse($tenant_result);
        } else {
            // Tenants 1 & 3 should pass.
            self::assertTrue($tenant_result);
        }
    }

    protected function check_export_basic_content(array $export) {
        // File exists.
        self::assertTrue(file_exists($export['filename']));

        // Check Headings are correct.
        $headings = 'user_id,lang,city,country,interests,asp_position,positions,organisations,competencies_scale,badges,description';
        $csv_content = file_get_contents($export['filename']);

        // Tenants 1, 2 & 3 should pass as headers are mandatory.
        self::assertStringContainsString($headings, $csv_content);

        // Check for guest user, which should never be included.
        $guest_user = guest_user();
        self::assertStringNotContainsString("{$guest_user->id},{$guest_user->lang}", $csv_content);
    }

    /**
     * Convert CSV data to associative arrays.
     *
     * Source: https://www.php.net/manual/en/function.str-getcsv.php#117692
     *
     * @param array $export
     * @return array
     */
    protected function get_csv_to_array(array $export) {
        $handle = fopen($export['filename'], 'r');
        self::assertNotFalse($handle);

        // Convert CSV data rows to associative arrays.
        $csv = array_map('str_getcsv', file($export['filename']));
        array_walk($csv, function (&$array_combine) use ($csv) {
            $array_combine = array_combine($csv[0], $array_combine);
        });

        // Drop the headings row and return the CSV data.
        array_shift($csv);
        fclose($handle);

        return $csv;
    }

    /**
     * Compare string data between input and data that was exported.
     *
     * @param $user A user defined above.
     * @param $csv  The exported CSV data for the same user.
     */
    protected function check_user_data_strings($user, $csv) {
        // Swap out embedded double-quotes in text fields.
        $user->city = str_replace('"', "'", $user->city);
        $user->description = str_replace('"', "'", content_to_text($user->description, $user->descriptionformat));

        // Build strings and compare them.
        $input = $user->lang . $user->city . $user->country . $user->description;
        $output = $csv['lang'] . $csv['city'] . $csv['country'] . $csv['description'];
        self::assertEquals($input, $output);
    }

    /**
     * Check interests tally.
     *
     * @param $user
     * @param $csv
     */
    protected function check_listed_interests($user, $csv) {
        global $DB;

        $user_interests = array_keys($DB->get_records_sql("SELECT id from {tag_instance} WHERE itemtype = 'user' AND component = 'core' AND itemid = " . $user->id));
        if ($csv['interests'] == '' && count($user_interests) == 0) {
            return;
        }
        $csv_interests = explode('|', $csv['interests']);

        // All queried interests in CSV data.
        foreach ($user_interests as $interestid) {
            self::assertNotFalse(array_search($interestid, $csv_interests));
        }

        // All CSV interests in queried data.
        foreach ($csv_interests as $interestid) {
            self::assertNotFalse(array_search($interestid, $user_interests));
        }
    }

    /**
     * Check aspirational positions.
     *
     * @param $user
     * @param $csv
     */
    protected function check_aspirational_positions($user, $csv) {
        global $DB;

        $user_aspirations = array_keys($DB->get_records_sql("SELECT positionid from {gap_aspirational} WHERE userid = " . $user->id));
        if ($csv['asp_position'] == '' && count($user_aspirations) == 0) {
            return;
        }
        $csv_aspirations = explode('|', $csv['asp_position']);

        // All queried aspirations in CSV data.
        foreach ($user_aspirations as $aspiration) {
            self::assertNotFalse(array_search($aspiration, $csv_aspirations));
        }

        // All CSV aspirations in queried data.
        foreach ($csv_aspirations as $aspiration) {
            self::assertNotFalse(array_search($aspiration, $user_aspirations));
        }
    }

    /**
     * Check job assignments and competency data.
     *
     * @param $user
     * @param $csv
     */
    protected function check_job_assignemnts_etc($user, $csv) {
        global $DB;

        // Current positions and organisations for user.
        $positions = [];
        $organisations = [];
        $sql = "SELECT id, positionid, organisationid FROM {job_assignment}  WHERE userid = :userid";
        $pos_orgs = $DB->get_records_sql($sql, ['userid' => $user->id]);

        foreach ($pos_orgs as $pos_org) {
            $positions[$pos_org->positionid] = $pos_org->positionid;
            $organisations[$pos_org->organisationid] = $pos_org->organisationid;
        }
        $positions = array_keys($positions);
        $organisations = array_keys($organisations);
        asort($positions);
        asort($organisations);

        // Compare positions.
        $csv_positions = explode('|', $csv['positions']);
        asort($csv_positions);
        self::assertEquals($positions, $csv_positions);

        // Compare organisations.
        $csv_organisations = explode('|', $csv['organisations']);
        asort($csv_organisations);
        self::assertEquals($organisations, $csv_organisations);

        // Current competencies for user.
        $sql = "SELECT competency_id, scale_value_id FROM {totara_competency_achievement} where status = :active_assignment and user_id =  :userid";
        $competencies = $DB->get_records_sql($sql, ['userid' => $user->id, 'active_assignment' => 0]);
        $scaleids = [];
        foreach ($competencies as $competency_id => $competency) {
            $scaleids[] = $competency->scale_value_id;
        }

        // Current proficiency level per competency.
        [$sql_in, $params] = $DB->sql_in($scaleids);
        $sql = "SELECT id, sortorder FROM {comp_scale_values} WHERE  id {$sql_in}";
        $proficiencies = $DB->get_records_sql($sql, $params);

        // Competency paired to proficiency.
        $competencies_scale = [];
        foreach ($competencies as $competency) {
            $sortorder = $proficiencies[$competency->scale_value_id]->sortorder;
            if (isset($competencies_scale[$competency->competency_id])) {
                if ($competencies_scale[$competency->competency_id] < $sortorder) {
                    $competencies_scale[$competency->competency_id] = $sortorder;
                }
            } else {
                $competencies_scale[$competency->competency_id] = $sortorder;
            }
        }
        ksort($competencies_scale);

        // Compare competencies and proficiencies.
        $csv_competencies_scale = [];
        $temp_scale = explode('|', $csv['competencies_scale']);
        foreach ($temp_scale as $competency_scale) {
            $comp_proficiency = explode(':', $competency_scale);
            $csv_competencies_scale[$comp_proficiency[0]] = $comp_proficiency[1];
        }
        ksort($csv_competencies_scale);
        self::assertEquals($competencies_scale, $csv_competencies_scale);
    }

    /**
     * Check badges tally.
     *
     * @param $user
     * @param $csv
     */
    protected function check_listed_badges($user, $csv) {
        global $DB;

        $user_badges = array_keys($DB->get_records_sql('SELECT badgeid from {badge_issued} WHERE userid = ' . $user->id));
        if ($csv['badges'] == '' && count($user_badges) == 0) {
            return;
        }
        $csv_badges = explode('|', $csv['badges']);

        // All queried badges in CSV data.
        foreach ($user_badges as $badgeid) {
            self::assertNotFalse(array_search($badgeid, $csv_badges));
        }

        // All CSV badges in queried data.
        foreach ($csv_badges as $badgeid) {
            self::assertNotFalse(array_search($badgeid, $user_badges));
        }
    }

    /**
     * The functions below have been copied (and tweaked) from:
     * server/totara/competency/tests/integration_aggregation.php
     * server/totara/competency/tests/integration_aggregation_simple_test.php
     */

    /**
     * Set up data for competencies.
     *
     * @param array $users
     * @param \core\record\tenant $tenant
     * @return (data object)
     * @throws coding_exception
     */
    protected function setup_for_competencies(array $users, core\record\tenant $tenant) {
        global $DB;

        $data = new class() {
            /** @var scale */
            public $scale;
            /** @var scale_value[] */
            public $scalevalues;
            /** @var competency[] */
            public $competencies = [];
            public $users = [];
            public $courses = [];
            /** @var assignment[]  */
            public $assignments = [];

            /** @var testing_data_generator $generator */
            public $generator;
            /** @var totara_hierarchy_generator $hierarchy_generator */
            public $hierarchy_generator;
            /** @var totara_competency_generator $competency_generator */
            public $competency_generator;
            /** @var totara_criteria_generator $criteria_generator */
            public $criteria_generator;

        };

        $data->generator = $this->getDataGenerator();
        $data->hierarchy_generator = $data->generator->get_plugin_generator('totara_hierarchy');
        $data->competency_generator = $data->generator->get_plugin_generator('totara_competency');
        $data->criteria_generator = $data->generator->get_plugin_generator('totara_criteria');

        $data->scale = $data->hierarchy_generator->create_scale(
            'comp',
            ['name' => $tenant->name . ' Test scale', 'description' => 'Test scale'],
            [
                5 => ['name' => 'No clue', 'proficient' => 0, 'sortorder' => 5, 'default' => 1],
                4 => ['name' => 'Learning', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
                3 => ['name' => 'Getting there', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
                2 => ['name' => 'Almost there', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
                1 => ['name' => 'Arrived', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ]
        );

        $framework = $data->hierarchy_generator->create_comp_frame(['scale' => $data->scale->id]);

        $data->scale = new scale($data->scale->id);
        $data->scalevalues = $data->scale
            ->sorted_values_high_to_low
            ->key_by('sortorder')
            ->all(true);

        $competencies_and_parents = [
            1 => 0,
            2 => 1,
            3 => 0,
            4 => 3,
            5 => 3
        ];

        foreach ($competencies_and_parents as $idx => $parent_idx) {
            $comp_data = [
                'frameworkid' => $framework->id,
                'parentid' => empty($parent_idx) ? 0 : $data->competencies[$parent_idx]->id,
            ];
            $comp = $data->hierarchy_generator->create_comp($comp_data);
            $data->competencies[$idx] = new competency($comp);
        }

        // Users with job assignments
        $data->users['manager'] = $users['manager'];
        $data->users['appraiser'] = $users['manager'];

        // Job assignments
        $managerja = job_assignment::create_default($data->users['manager']->id, [
            'fullname' => 'Manager job ' . $data->users['manager']->id,
            'idnumber' => 'MANAGERJOB ' . $data->users['manager']->id,
        ]);

        foreach ($users['other'] as $index => $user) {
            $data->users[$index] = $user;

            // All users get manager as manager and appraiser as appraiser
            job_assignment::create_default($user->id, [
                'managerjaid' => $managerja->id,
                'fullname' => 'Managed by manager',
                'idnumber' => "User{$index}managed",
            ]);

            job_assignment::create_default($user->id, [
                'appraiserid' => $data->users['appraiser']->id,
                'fullname' => 'Appraised by appraiser',
                'idnumber' => "User{$index}appraised",
            ]);
        }

        return $data;
    }

    /**
     * Aggregation of competency records with manual pathway.
     *
     * @dataProvider task_to_execute_data_provider
     */
    public function competency_aggregation_single_manual(array $users, core\record\tenant $tenant) {
        global $DB;

        /** @var totara_competency_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');


        $data = $this->setup_for_competencies($users, $tenant);

        /** @var manual $pathway */
        $pathway = $data->competency_generator->create_manual($data->competencies[1], [manager::class]);

        // Assign users
        $to_assign = [
            ['user_id' => $users['other'][0]->id, 'competency_id' => $data->competencies[1]->id],
            ['user_id' => $users['other'][1]->id, 'competency_id' => $data->competencies[1]->id],
        ];
        $this->assign_users_to_competencies($to_assign);

        $ratings = [];
        // Manager gives rating
        $ratings[2] = $generator->create_manual_rating(
            $pathway,
            $users['other'][0]->id,
            $users['manager']->id,
            manager::class,
            $data->scalevalues[2]->id
        );
        $ratings[3] = $generator->create_manual_rating(
            $pathway,
            $users['other'][1]->id,
            $users['manager']->id,
            manager::class,
            $data->scalevalues[4]->id
        );

        // Assign users
        $pathway = $data->competency_generator->create_manual($data->competencies[2], [manager::class]);

        $to_assign = [
            ['user_id' => $users['other'][1]->id, 'competency_id' => $data->competencies[2]->id],
            ['user_id' => $users['manager']->id, 'competency_id' => $data->competencies[2]->id],
        ];
        $this->assign_users_to_competencies($to_assign);

        // Manager gives rating
        $ratings[4] = $generator->create_manual_rating(
            $pathway,
            $users['other'][1]->id,
            $users['manager']->id,
            manager::class,
            $data->scalevalues[1]->id
        );
        $ratings[5] = $generator->create_manual_rating(
            $pathway,
            $users['manager']->id,
            $users['manager']->id,
            manager::class,
            $data->scalevalues[5]->id
        );

        // Now run the aggregation tasks.
        $expand_task = new expand_task($DB);
        $expand_task->expand_all();
        (new competency_aggregation_all())->execute();
    }

    /**
     * Assign users to competencies.
     *
     * @param array $to_assign
     * @return array
     * @throws coding_exception
     */
    protected function assign_users_to_competencies(array $to_assign): array {
        global $DB;

        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $assign_generator = $competency_generator->assignment_generator();

        $assignment_ids = [];
        foreach ($to_assign as $user_comp) {
            $key = implode('_', [$user_comp['competency_id'], $user_comp['user_id']]);
            $this->assignments[$key] = $assign_generator->create_user_assignment($user_comp['competency_id'], $user_comp['user_id']);
            $assignment_ids[] = $this->assignments[$key]->id;
        }

        $expand_task = new expand_task($DB);
        $expand_task->expand_all();

        return $assignment_ids;
    }
}