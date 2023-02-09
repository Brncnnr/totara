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
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder;

use external_function_parameters;
use external_value;
use external_multiple_structure;
use external_single_structure;
use external_warnings;

global $CFG;
require_once("$CFG->libdir/externallib.php");

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_reportbuilder
 *
 */
class totara_reportbuilder_external_testcase extends \advanced_testcase {

    /**
     * @var \totara_reportbuilder\testing\generator
     */
    public $rb_generator;

    protected function setUp(): void {
        $this->rb_generator = $this->getDataGenerator()->get_plugin_generator('totara_reportbuilder');
        parent::setup();
    }

    protected function tearDown(): void {
        $this->rb_generator = null;
        parent::tearDown();
    }

    public function create_data() {
        global $DB;

        $data = new \stdClass();

        /** @var \totara_reportbuilder\testing\generator $rbgen */
        $rbgen = $this->getDataGenerator()->get_plugin_generator('totara_reportbuilder');

        // Create 2 users.
        $data->user1 = $this->getDataGenerator()->create_user();
        $data->user2 = $this->getDataGenerator()->create_user();

        // Create a report.
        $data->report = (object)[
            'fullname' => 'Users',
            'shortname' => 'user',
            'source' => 'user',
            'hidden' => 1
        ];
        $data->report->id = $DB->insert_record('report_builder', $data->report);

        // Create four shared saved searches, two for each user.
        $data->shared_savedsearch1_user1 = $rbgen->create_saved_search($data->report, $data->user1, ['name' => 'Saved1 user1 shared', 'ispublic' => 1]);
        $data->shared_savedsearch2_user1 = $rbgen->create_saved_search($data->report, $data->user1, ['name' => 'Saved2 user1 shared', 'ispublic' => 1]);
        $data->shared_savedsearch1_user2 = $rbgen->create_saved_search($data->report, $data->user2, ['name' => 'Saved1 user2 shared', 'ispublic' => 1]);
        $data->shared_savedsearch2_user2 = $rbgen->create_saved_search($data->report, $data->user2, ['name' => 'Saved2 user2 shared', 'ispublic' => 1]);

        // Create two private saved searches, one for each user.
        $data->private_savedsearch1_user1 = $rbgen->create_saved_search($data->report, $data->user1, ['name' => 'Saved3 user1 private', 'ispublic' => 0]);
        $data->private_savedsearch1_user2 = $rbgen->create_saved_search($data->report, $data->user2, ['name' => 'Saved3 user2 private', 'ispublic' => 0]);

        return $data;
    }

    /**
     * Helper function to see if a saved search appears on the searches array
     *
     * @param array $saved_searches
     * @param string $name
     * @return bool
     */
    private function saved_search_contains($saved_searches, $name) {
        foreach ($saved_searches as $saved_search) {
            if ($saved_search['name'] === $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Guests users should not be able to add a search search let alone set a default.
     */
    public function test_no_saving_for_guests() {
        $data = $this->create_data();

        // Set as guest.
        $this->setGuestUser();

        $result = external::set_default_search($data->report->id, $data->shared_savedsearch1_user1->id, true);
        $this->assertEquals("No saving for guests.", $result['warnings'][0]['message']);
        $this->assertEmpty($result['savedsearches']);
    }

    /**
     * The user should have permission for this report.
     */
    public function test_user_is_capable() {
        global $DB;

        $data = $this->create_data();

        // Set as user1.
        $this->setUser($data->user1);

        // The user should be capable
        $result = external::set_default_search($data->report->id, $data->shared_savedsearch1_user1->id, true);
        $this->assertEmpty($result['warnings']);
        $this->assertNotEmpty($result['savedsearches']);

        // Change the report accessmode
        $data->report->accessmode = REPORT_BUILDER_ACCESS_MODE_ANY;
        $DB->update_record('report_builder', $data->report);

        // The user should be capable.
        $result = external::set_default_search($data->report->id, $data->shared_savedsearch1_user1->id, true);
        $this->assertEquals("You do not have permission for this report.", $result['warnings'][0]['message']);
        $this->assertEmpty($result['savedsearches']);
    }

    /**
     * Users need to have access to the report.
     */
    public function test_user_has_access_to_report() {
        $data = $this->create_data();

        // Set as user1.
        $this->setUser($data->user1);

        // User1 should not have access their shared saved search.
        $result = external::set_default_search($data->report->id, $data->shared_savedsearch1_user1->id, true);
        $this->assertEmpty($result['warnings']);
        $this->assertNotEmpty($result['savedsearches']);

        // User1 should have access their private saved search.
        $result = external::set_default_search($data->report->id, $data->private_savedsearch1_user1->id, true);
        $this->assertEmpty($result['warnings']);
        $this->assertNotEmpty($result['savedsearches']);

        // User1 should have access to the shared saved saved search of user2.
        $result = external::set_default_search($data->report->id, $data->shared_savedsearch1_user2->id, true);
        $this->assertEmpty($result['warnings']);
        $this->assertNotEmpty($result['savedsearches']);

        // User2 should not have access to the private saved search of user2.
        $result = external::set_default_search($data->report->id, $data->private_savedsearch1_user2->id, true);
        $this->assertEquals("You do not have access to this report.", $result['warnings'][0]['message']);
        $this->assertEmpty($result['savedsearches']);
    }

    /**
     * Users can set a default.
     *
     */
    public function test_user_can_set_a_default() {
        global $DB;

        $data = $this->create_data();

        // Set as user1.
        $this->setUser($data->user1);

        // User1 can set a default.
        $result = external::set_default_search($data->report->id, $data->shared_savedsearch1_user1->id, true);
        $this->assertCount(5, $result['savedsearches']);
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved1 user1 shared (Default view)"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved2 user1 shared"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved1 user2 shared"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved2 user2 shared"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved3 user1 private"));

        // User1 can set a default.
        $result = external::set_default_search($data->report->id, $data->shared_savedsearch1_user1->id, true);

        // The default should be added.
        $this->assertTrue($DB->record_exists('report_builder_saved_user_default',
            array('userid' => $data->user1->id, 'reportid' => $data->report->id, 'savedid' => $data->shared_savedsearch1_user1->id)));

        // Only one record should exists for the user/report.
        $this->assertCount(1, $DB->get_records('report_builder_saved_user_default',
            array('userid' => $data->user1->id, 'reportid' => $data->report->id)));

        // Check the returned data.
        $this->assertEmpty($result['warnings']);
        $this->assertCount(5, $result['savedsearches']);
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved1 user1 shared (Default view)"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved2 user1 shared"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved1 user2 shared"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved2 user2 shared"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved3 user1 private"));
    }

    /**
     * Users can change a default.
     *
     */
    public function test_user_can_change_a_default() {
        global $DB;


        $data = $this->create_data();

        // Set as user1.
        $this->setUser($data->user1);

        // Set a default for user1.
        $this->rb_generator->create_saved_search_user_default($data->report, $data->user1, $data->shared_savedsearch1_user1);

        $this->assertTrue($DB->record_exists('report_builder_saved_user_default',
            array('userid' => $data->user1->id, 'reportid' => $data->report->id, 'savedid' => $data->shared_savedsearch1_user1->id)));


        // User1 can change a default.
        $result = external::set_default_search($data->report->id, $data->shared_savedsearch2_user1->id, true);

        // The old default should be removed.
        $this->assertFalse($DB->record_exists('report_builder_saved_user_default',
            array('userid' => $data->user1->id, 'reportid' => $data->report->id, 'savedid' => $data->shared_savedsearch1_user1->id)));

        // The new default should be added.
        $this->assertTrue($DB->record_exists('report_builder_saved_user_default',
            array('userid' => $data->user1->id, 'reportid' => $data->report->id, 'savedid' => $data->shared_savedsearch2_user1->id)));

        // Only one record should exists for the user/report.
        $this->assertCount(1, $DB->get_records('report_builder_saved_user_default',
            array('userid' => $data->user1->id, 'reportid' => $data->report->id)));

        // Check the returned data.
        $this->assertEmpty($result['warnings']);

        $this->assertCount(5, $result['savedsearches']);
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved1 user1 shared"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved2 user1 shared (Default view)"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved1 user2 shared"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved2 user2 shared"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved3 user1 private"));
    }

    /**
     * Users can remove a default.
     *
     */
    public function test_user_can_remove_a_default() {
        global $DB;

        $data = $this->create_data();

        // Set as user1.
        $this->setUser($data->user1);

        // Set a default for user1.
        $this->rb_generator->create_saved_search_user_default($data->report, $data->user1, $data->shared_savedsearch1_user1);

        $this->assertTrue($DB->record_exists('report_builder_saved_user_default',
            array('userid' => $data->user1->id, 'reportid' => $data->report->id, 'savedid' => $data->shared_savedsearch1_user1->id)));

        // User1 can remove the set default.
        $result = external::set_default_search($data->report->id, $data->shared_savedsearch1_user1->id, false);

        // The user should have their savedid set a zero.
        $this->assertTrue($DB->record_exists('report_builder_saved_user_default',
            array('userid' => $data->user1->id, 'reportid' => $data->report->id, 'savedid' => 0)));

        // Only one record should exists for the user/report.
        $this->assertCount(1, $DB->get_records('report_builder_saved_user_default',
            array('userid' => $data->user1->id, 'reportid' => $data->report->id)));

        // Check the returned data.
        $this->assertEmpty($result['warnings']);
        $this->assertCount(5, $result['savedsearches']);

        $this->saved_search_contains($result['savedsearches'], "Saved1 user1 shared");
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved1 user1 shared"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved2 user1 shared"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved1 user2 shared"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved2 user2 shared"));
        $this->assertTrue($this->saved_search_contains($result['savedsearches'], "Saved3 user1 private"));
    }

}