<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use core\event\base;
use mod_approval\entity\application\application;
use mod_approval\entity\workflow\workflow_version;
use mod_approval\event\stage_started;
use mod_approval\event\application_event_base;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\event\application_event_base
 */
class mod_approval_events_testcase extends mod_approval_testcase {

    /**
     * @covers ::create_from_application
     * @covers ::get_name
     * @covers ::get_description
     */
    public function test_create_from_application_properties(): void {
        $applicant = $this->getDataGenerator()->create_user();
        $this->setUser($applicant);
        $application = $this->create_application_for_user();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        // Same for all workflow events
        $classes = core_component::get_namespace_classes('event', application_event_base::class, 'mod_approval');
        /** @var application_event_base $class */
        foreach ($classes as $class) {
            /** @var application_event_base $event */
            $event = $class::create_from_application($application);
            $this->assertNotNull($class::get_name());
            $this->assertNotNull($event->get_description());
            $this->assertEquals(base::LEVEL_OTHER, $event->edulevel);
            $this->assertEquals(application::TABLE, $event->objecttable);
            $this->assertEquals($application->id, $event->objectid);
            $this->assertEquals($user->id, $event->userid);
            $this->assertEquals($applicant->id, $event->relateduserid);
            $this->assertEquals($application->get_context()->id, $event->get_context()->id);
            $this->assertEventContextNotUsed($event);
            $record = $event->get_record_snapshot(application::TABLE, $event->objectid);
            $this->assertInstanceOf(stdClass::class, $record);
            $this->assertEquals($record->id, $application->id);
        }
    }

    /**
     * @covers ::create_from_application
     */
    public function test_create_from_application_stage_and_level() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $application = $this->create_application_for_user();
        workflow_version::repository()->where('id', $application->workflow_version_id)
            ->update([
                'status' => status::DRAFT
            ]);
        $application->workflow_version->refresh();

        $stage2 = workflow_stage::create($application->workflow_version, 'Season 2', approvals::get_enum());
        $level2 = $stage2->add_approval_level('Episode 2');
        $stage3 = workflow_stage::create($application->workflow_version, 'Final Season', form_submission::get_enum());
        $application->workflow_version->activate();

        // Use stage_started as the victim of workflow_event_base::create_from_application.
        $this->application_update_stage_and_level_silently($application, $stage2->id, $level2->id);
        $event = stage_started::create_from_application($application);
        $this->assertEquals('test workflow type 001', $event->other['workflow_type_name']);
        $this->assertEquals($stage2->id, $event->other['workflow_stage_id']);
        $this->assertEquals('Season 2', $event->other['workflow_stage_name']);
        $this->assertEquals('Episode 2', $event->other['approval_level_name']);

        $this->application_update_stage_and_level_silently($application, $stage3->id, null);
        $event = stage_started::create_from_application($application);
        $this->assertEquals('test workflow type 001', $event->other['workflow_type_name']);
        $this->assertEquals($stage3->id, $event->other['workflow_stage_id']);
        $this->assertEquals('Final Season', $event->other['workflow_stage_name']);
        $this->assertEquals('', $event->other['approval_level_name']);
    }
}
