<?php
/**
 * This file is part of Totara Learn
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package enrol_cohort
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test deleting cohort audience syncs
 *
 * @group enrol_cohort
 */
class enrol_cohort_delete_enrol_instance_test extends advanced_testcase {

    /**
     * @var array|stdClass
     */
    private $user;

    /**
     * @var stdClass
     */
    private $course;

    /**
     * @var stdClass
     */
    private $audience;

    /**
     * @var enrol_cohort_plugin
     */
    private $plugin;

    /**
     * @var int
     */
    private $instance_id;

    /**
     * @return void
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();

        $this->setAdminUser();

        $this->user = $this->getDataGenerator()->create_user();
        $this->course = $this->getDataGenerator()->create_course();
        $this->audience = $this->getDataGenerator()->create_cohort();
        $learner = $DB->get_record('role', ['shortname' => 'student']);
        cohort_add_member($this->audience->id, $this->user->id);

        /** @var enrol_cohort_plugin $plugin */
        $this->plugin = enrol_get_plugin('cohort');
        $this->instance_id = $this->plugin->add_instance(
            $this->course,
            [
                'customint1' => $this->audience->id,
                'roleid' => $learner->id
            ]
        );

        $sink = $this->redirectMessages();
        $this->executeAdhocTasks();
        $sink->clear();
        $sink->close();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->user = null;
        $this->course = null;
        $this->audience = null;
        $this->instance_id = null;
        $this->plugin = null;

        parent::tearDown();
    }

    /**
     * @return array
     */
    public function delete_data(): array {
        return [
            [true,],
            [false,],
        ];
    }

    /**
     * @dataProvider delete_data
     * @param bool $direct_delete
     * @return void
     */
    public function test_delete(bool $direct_delete): void {
        global $DB;

        $instance = $DB->get_record('enrol', ['id' => $this->instance_id]);
        $this->assertEquals(ENROL_INSTANCE_ENABLED, $instance->status);

        // Delete the audience sync
        $this->plugin->schedule_delete_instance($instance);

        // Confirm it exists, but is flagged as deleted
        $instance = $DB->get_record('enrol', ['id' => $this->instance_id]);
        $this->assertEquals(ENROL_INSTANCE_DELETED, $instance->status);

        // Confirm the user still has their enrolment record
        $count = $DB->count_records('user_enrolments', ['enrolid' => $instance->id, 'userid' => $this->user->id]);
        $this->assertSame(1, $count);

        if ($direct_delete) {
            // Delete it properly
            $this->plugin->delete_instance($instance);
        } else {
            // Delete via the task
            $sink = $this->redirectMessages();
            $this->executeAdhocTasks();
            $sink->clear();
            $sink->close();
        }

        // Confirm it does not exist
        $count = $DB->count_records('enrol', ['id' => $instance->id]);
        $this->assertSame(0, $count);

        // Confirm the user is no longer enrolled
        $count = $DB->count_records('user_enrolments', ['enrolid' => $instance->id, 'userid' => $this->user->id]);
        $this->assertSame(0, $count);
    }
}
