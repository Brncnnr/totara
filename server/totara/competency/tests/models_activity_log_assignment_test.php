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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

use totara_competency\user_groups;
use totara_competency\models\activity_log;
use totara_competency\entity\competency_assignment_user_log;
use totara_competency\entity\assignment;

/**
 * @group totara_competency
 */
class totara_competency_models_activity_log_assignment_testcase extends advanced_testcase {

    public function test_tracking() {
        $time = time();

        $assignment = new assignment();
        $assignment->competency_id = 100;
        $assignment->user_group_id = 300;
        $assignment->user_group_type = 'test';
        $assignment->created_by = 400;
        $assignment->save();

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_TRACKING_START;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Competency active: Achievement tracking started', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_TRACKING_END;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Competency archived: Achievement tracking stopped', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());
        $this->assertSame('', $entry->get_comment());
    }

    public function test_system() {
        $time = time();
        $assignment = new assignment();
        $assignment->type = assignment::TYPE_SYSTEM;
        $assignment->competency_id = 100;
        $assignment->user_group_id = 300;
        $assignment->user_group_type = 'test';
        $assignment->created_by = 400;
        $assignment->save();

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_ASSIGNED;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Assignment transferred for continuous tracking', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Unassigned: Continuous tracking', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_UNASSIGNED_USER_GROUP;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Unassigned: Continuous tracking', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());
        $this->assertSame('', $entry->get_comment());
    }

    public function test_self() {
        $time = time();
        $assignment = new assignment();
        $assignment->type = assignment::TYPE_SELF;
        $assignment->competency_id = 100;
        $assignment->user_group_id = 300;
        $assignment->user_group_type = 'test';
        $assignment->created_by = 400;
        $assignment->save();

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_ASSIGNED;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Assigned: Self-assigned', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Unassigned: Self-assigned', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_UNASSIGNED_USER_GROUP;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Unassigned: Self-assigned', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());
        $this->assertSame('', $entry->get_comment());
    }

    public function test_position() {
        $time = time();

        /** @var \totara_hierarchy\testing\generator $totara_hierarchy_generator */
        $totara_hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $posfw = $totara_hierarchy_generator->create_pos_frame([]);
        $position = $totara_hierarchy_generator->create_pos(['frameworkid' => $posfw->id, 'fullname' => 'Developer']);

        $assignment = new assignment();
        $assignment->type = assignment::TYPE_ADMIN;
        $assignment->user_group_type = user_groups::POSITION;
        $assignment->user_group_id = $position->id;
        $assignment->competency_id = 100;
        $assignment->created_by = 400;
        $assignment->save();

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_ASSIGNED;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Assigned: Developer (Position)', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Unassigned: Developer (Position)', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_UNASSIGNED_USER_GROUP;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Unassigned: Developer (Position)', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());
        $this->assertSame('', $entry->get_comment());
    }

    public function test_organisation() {
        $time = time();

        /** @var \totara_hierarchy\testing\generator $totara_hierarchy_generator */
        $totara_hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgfw = $totara_hierarchy_generator->create_org_frame([]);
        $organisation = $totara_hierarchy_generator->create_org(
            ['frameworkid' => $orgfw->id, 'fullname' => 'Software Development']
        );

        $assignment = new assignment();
        $assignment->type = assignment::TYPE_ADMIN;
        $assignment->user_group_type = user_groups::ORGANISATION;
        $assignment->user_group_id = $organisation->id;
        $assignment->competency_id = 100;
        $assignment->created_by = 400;
        $assignment->save();

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_ASSIGNED;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Assigned: Software Development (Organisation)', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Unassigned: Software Development (Organisation)', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_UNASSIGNED_USER_GROUP;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Unassigned: Software Development (Organisation)', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());
        $this->assertSame('', $entry->get_comment());
    }

    public function test_audience() {
        $time = time();

        $cohort = $this->getDataGenerator()->create_cohort(['name' => 'Competent People']);

        $assignment = new assignment();
        $assignment->type = assignment::TYPE_ADMIN;
        $assignment->user_group_type = user_groups::COHORT;
        $assignment->user_group_id = $cohort->id;
        $assignment->competency_id = 100;
        $assignment->created_by = 400;
        $assignment->save();

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_ASSIGNED;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Assigned: Competent People (Audience)', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Unassigned: Competent People (Audience)', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_UNASSIGNED_USER_GROUP;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Unassigned: Competent People (Audience)', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());
        $this->assertSame('', $entry->get_comment());
    }

    public function test_user_by_admin() {
        $time = time();

        $subject = $this->getDataGenerator()->create_user(['firstname' => 'Albert', 'lastname' => 'Einstein']);
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Isaac', 'lastname' => 'Newton']);

        $assignment = new assignment();
        $assignment->type = assignment::TYPE_ADMIN;
        $assignment->user_group_type = user_groups::USER;
        $assignment->user_group_id = $subject->id;
        $assignment->created_by = $user->id;
        $assignment->competency_id = 100;
        $assignment->save();

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_ASSIGNED;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Assigned: Isaac Newton (Admin)', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Unassigned: Isaac Newton (Admin)', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_UNASSIGNED_USER_GROUP;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Unassigned: Isaac Newton (Admin)', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());
        $this->assertSame('', $entry->get_comment());
    }

    public function test_user_by_other() {
        $time = time();

        $manager = $this->getDataGenerator()->create_user(['firstname' => 'Isaac', 'lastname' => 'Newton']);

        $assignment = new assignment();
        $assignment->type = assignment::TYPE_OTHER;
        $assignment->user_group_type = user_groups::USER;
        $assignment->user_group_id = 100;
        $assignment->created_by = $manager->id;
        $assignment->competency_id = 100;
        $assignment->save();

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_ASSIGNED;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Assigned: Isaac Newton (Manager)', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_UNASSIGNED_ARCHIVED;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Unassigned: Isaac Newton (Manager)', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());

        $assignment_log = new competency_assignment_user_log();
        $assignment_log->created_at = $time;
        $assignment_log->action = competency_assignment_user_log::ACTION_UNASSIGNED_USER_GROUP;
        $assignment_log->assignment_id = $assignment->id;
        $assignment_log->user_id = 1;
        $assignment_log->save();

        $entry = activity_log\assignment::load_by_entity($assignment_log);

        $this->assertEquals('Unassigned: Isaac Newton (Manager)', $entry->get_description());
        $this->assertNull($entry->get_proficient_status());
        $this->assertEquals($time, $entry->get_date());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());
        $this->assertSame('', $entry->get_comment());
    }
}
