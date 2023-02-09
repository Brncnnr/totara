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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

use totara_competency\entity\pathway_achievement;

/**
 * @group totara_competency
 */
class totara_competency_pathway_achievement_testcase extends advanced_testcase {

    public function test_get_current_when_none_exist() {

        $user = $this->getDataGenerator()->create_user();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = $competency_generator->create_competency();
        $pathway = $competency_generator->create_test_pathway($competency);

        $this->setCurrentTimeStart();

        $current = pathway_achievement::get_current($pathway, $user->id);

        // It doesn't matter that a record didn't exist. We still want an instance and not just null for example.
        $this->assertInstanceOf(pathway_achievement::class, $current);

        // We haven't saved it.
        $this->assertNull($current->id);
        $this->assertEquals($pathway->get_id(), $current->pathway_id);
        $this->assertEquals($user->id, $current->user_id);
        $this->assertEquals(pathway_achievement::STATUS_CURRENT, $current->status);
        // It has not been aggregated yet.
        $this->assertNull($current->last_aggregated);
        $this->assertNull($current->date_achieved);

        // Just test that this will save.
        $current->save();
    }

    public function test_get_current_when_one_active_exists_only() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = $competency_generator->create_competency();
        $pathway = $competency_generator->create_test_pathway($competency);

        // A custom time that is definitely not now.
        $time = 100;

        $record = new stdClass();
        $record->pathway_id = $pathway->get_id();
        $record->user_id = $user->id;
        $record->status = pathway_achievement::STATUS_CURRENT;
        $record->last_aggregated = $time;
        $record->date_achieved = $time;
        $id = $DB->insert_record('totara_competency_pathway_achievement', $record);

        $current = pathway_achievement::get_current($pathway, $user->id);

        $this->assertInstanceOf(pathway_achievement::class, $current);
        $this->assertEquals($id, $current->id);
        $this->assertEquals($pathway->get_id(), $current->pathway_id);
        $this->assertEquals($user->id, $current->user_id);
        $this->assertEquals(pathway_achievement::STATUS_CURRENT, $current->status);
        $this->assertEquals($time, $current->last_aggregated);
        $this->assertEquals($time, $current->date_achieved);
    }

    public function test_get_current_with_one_active_and_one_archived() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = $competency_generator->create_competency();
        $pathway = $competency_generator->create_test_pathway($competency);

        // A custom time that is definitely not now.
        $archived_time = 100;
        $current_time = 200;

        $record = new stdClass();
        $record->pathway_id = $pathway->get_id();
        $record->user_id = $user->id;
        $record->status = pathway_achievement::STATUS_ARCHIVED;
        $record->last_aggregated = $archived_time;
        $record->date_achieved = $archived_time;
        $DB->insert_record('totara_competency_pathway_achievement', $record);

        $record = new stdClass();
        $record->pathway_id = $pathway->get_id();
        $record->user_id = $user->id;
        $record->status = pathway_achievement::STATUS_CURRENT;
        $record->last_aggregated = $current_time;
        $record->date_achieved = $current_time;
        $current_id = $DB->insert_record('totara_competency_pathway_achievement', $record);

        $current = pathway_achievement::get_current($pathway, $user->id);

        $this->assertInstanceOf(pathway_achievement::class, $current);
        $this->assertEquals($current_id, $current->id);
        $this->assertEquals($pathway->get_id(), $current->pathway_id);
        $this->assertEquals($user->id, $current->user_id);
        $this->assertEquals(pathway_achievement::STATUS_CURRENT, $current->status);
        $this->assertEquals($current_time, $current->last_aggregated);
        $this->assertEquals($current_time, $current->date_achieved);
    }

    public function test_get_current_based_on_status_only() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = $competency_generator->create_competency();
        $pathway = $competency_generator->create_test_pathway($competency);

        // Archived time is later than current time.
        $archived_time = 5000;
        $current_time = 200;

        $record = new stdClass();
        $record->pathway_id = $pathway->get_id();
        $record->user_id = $user->id;
        $record->status = pathway_achievement::STATUS_ARCHIVED;
        $record->last_aggregated = $archived_time;
        $record->date_achieved = $archived_time;
        $DB->insert_record('totara_competency_pathway_achievement', $record);

        $record = new stdClass();
        $record->pathway_id = $pathway->get_id();
        $record->user_id = $user->id;
        $record->status = pathway_achievement::STATUS_CURRENT;
        $record->last_aggregated = $current_time;
        $record->date_achieved = $current_time;
        $current_id = $DB->insert_record('totara_competency_pathway_achievement', $record);

        $current = pathway_achievement::get_current($pathway, $user->id);

        $this->assertInstanceOf(pathway_achievement::class, $current);

        // We still got the active achievement, that is authoritative over the aggregated or achieved times.
        $this->assertEquals($current_id, $current->id);
        $this->assertEquals($pathway->get_id(), $current->pathway_id);
        $this->assertEquals($user->id, $current->user_id);
        $this->assertEquals(pathway_achievement::STATUS_CURRENT, $current->status);
        $this->assertEquals($current_time, $current->last_aggregated);
        $this->assertEquals($current_time, $current->date_achieved);
    }

    public function test_archived() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = $competency_generator->create_competency();
        $pathway = $competency_generator->create_test_pathway($competency);

        // A custom time that is definitely not now.
        $original_time = 100;

        $record = new stdClass();
        $record->pathway_id = $pathway->get_id();
        $record->user_id = $user->id;
        $record->status = pathway_achievement::STATUS_CURRENT;
        $record->last_aggregated = $original_time;
        $record->date_achieved = $original_time;
        $id = $DB->insert_record('totara_competency_pathway_achievement', $record);

        $current = pathway_achievement::get_current($pathway, $user->id);

        $archive_time = 200;
        $current->archive($archive_time);

        $this->assertEquals($id, $current->id);
        $this->assertEquals($original_time, $current->date_achieved);
        $this->assertEquals($archive_time, $current->last_aggregated);
        $this->assertEquals(pathway_achievement::STATUS_ARCHIVED, $current->status);

        $new_current = pathway_achievement::get_current($pathway, $user->id);
        $new_current->save();

        $this->assertNotEquals($id, $new_current->id);
        $this->assertEquals(pathway_achievement::STATUS_CURRENT, $new_current->status);

        $all_achievements = pathway_achievement::repository()->get();
        $this->assertCount(2, $all_achievements);
    }
}