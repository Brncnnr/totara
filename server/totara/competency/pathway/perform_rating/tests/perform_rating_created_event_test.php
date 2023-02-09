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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package pathway_perform_rating
 */

use core\orm\query\builder;
use mod_perform\constants;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use pathway_perform_rating\event\perform_rating_created;
use pathway_perform_rating\testing\generator as pathway_peform_rating_generator;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\relationship;
use totara_hierarchy\entity\competency as competency_entity;

require_once __DIR__.'/perform_rating_base_testcase.php';

/**
 * @group pathway_perform_rating
 * @group totara_competency
 */
class pathway_perform_rating_perform_rating_created_event_testcase extends perform_rating_base_testcase {

    public function test_event_is_triggered() {
        $data = $this->create_data();

        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        $sink = $this->redirectEvents();

        // Now create a rating and check that it gets returned
        pathway_peform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance1),
            $data->section_element,
            $scale_value
        );

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        /** @var perform_rating_created $event */
        $event = array_shift($events);
        $this->assertInstanceOf(perform_rating_created::class, $event);

        $this->assertEquals($data->manager_user->id, $event->userid);
        $this->assertEquals($data->subject_user->id, $event->relateduserid);
        $this->assertEquals(
            [
                'competency_id' => $data->competency->id,
                'scale_value_id' => $scale_value->id,
                'rater_id' => $data->manager_user->id,
                'activity_id' => $data->participant_instance1->subject_instance->activity()->id,
                'participant_instance_id' => $data->participant_instance1->id,
                'relationship_id' => relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER)->id,
            ],
            $event->other
        );
    }

    public function test_event_description() {
        $data = $this->create_data();

        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        // Now create a rating and check that it gets returned
        $rating = pathway_peform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance1),
            $data->section_element,
            $scale_value
        );

        $event = perform_rating_created::create_from_perform_rating($rating);

        $this->assertEquals(
            "Received rating from 'Manager' in activity 'Test activity' for competency 'Test competency'",
            $event->get_description()
        );

        // Delete data and make sure the string is correct

        $activity = activity_model::load_by_entity($data->participant_instance1->subject_instance->activity());
        $activity->delete();

        $this->assertEquals(
            "Received rating from 'Manager' in activity 'n/a' for competency 'Test competency'",
            $event->get_description()
        );

        builder::table(competency_entity::TABLE)->where('id', $data->competency->id)->delete();

        $this->assertEquals(
            "Received rating from 'Manager' in activity 'n/a' for competency 'n/a'",
            $event->get_description()
        );

        builder::table(relationship_entity::TABLE)->where('id', relationship::load_by_idnumber(constants::RELATIONSHIP_MANAGER)->id)->delete();

        $this->assertEquals(
            "Received rating from 'n/a' in activity 'n/a' for competency 'n/a'",
            $event->get_description()
        );
    }

}