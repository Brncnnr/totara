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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use mod_perform\constants;
use mod_perform\entity\activity\activity;
use mod_perform\task\service\data\subject_instance_activity;
use mod_perform\task\service\data\subject_instance_activity_collection;
use totara_core\entity\relationship;

class mod_perform_subject_instance_activity_collection_test extends advanced_testcase {

    public function setUp(): void {
        $this->setAdminUser();
    }

    public function test_can_get_only_added_activity() {
        $subject_instance_activity_collection = new subject_instance_activity_collection();
        $activities_property = new ReflectionProperty(subject_instance_activity_collection::class, 'activities');
        $activities_property->setAccessible(true);

        $activity_1 = $this->build_subject_activity();
        $activity_2 = $this->build_subject_activity();
        $subject_instance_activity_collection->add_activity_config($activity_1);
        $this->assertCount(1, $activities_property->getValue($subject_instance_activity_collection));

        $this->assertInstanceOf(
            subject_instance_activity::class,
            $subject_instance_activity_collection->get_activity_config($activity_1->id)
        );

        $this->expectException(coding_exception::class);
        $subject_instance_activity_collection->get_activity_config($activity_2->id);
    }

    public function test_can_get_loaded_missing_activities() {
        $subject_instance_activity_collection = new subject_instance_activity_collection();
        $activities_property = new ReflectionProperty(subject_instance_activity_collection::class, 'activities');
        $activities_property->setAccessible(true);

        $activity_1 = $this->build_subject_activity();
        $activity_2 = $this->build_subject_activity();
        $activity_3 = $this->build_subject_activity();
        $subject_instance_activity_collection->load_activity_configs_if_missing([
            $activity_1->id,
            $activity_2->id,
        ]);

        $this->assertCount(2, $activities_property->getValue($subject_instance_activity_collection));
        $this->assertInstanceOf(
            subject_instance_activity::class,
            $subject_instance_activity_collection->get_activity_config($activity_1->id)
        );
        $this->assertInstanceOf(
            subject_instance_activity::class,
            $subject_instance_activity_collection->get_activity_config($activity_2->id)
        );

        $this->expectException(coding_exception::class);
        $subject_instance_activity_collection->get_activity_config($activity_3->id);
    }

    private function build_subject_activity(): activity {
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        $activity = $generator->create_activity_in_container(['create_section' => 'false']);
        $relationships = relationship::repository()->get();
        $first_section = $generator->create_section($activity);
        $first_section->update_relationships(
            [
                [
                    'core_relationship_id' => $relationships->find('idnumber', constants::RELATIONSHIP_SUBJECT)->id,
                    'can_view' => true,
                    'can_answer' => true,
                ],
                [
                    'core_relationship_id' => $relationships->find('idnumber', constants::RELATIONSHIP_MANAGER)->id,
                    'can_view' => true,
                    'can_answer' => true,
                ],
                [
                    'core_relationship_id' => $relationships->find('idnumber', constants::RELATIONSHIP_PEER)->id,
                    'can_view' => true,
                    'can_answer' => true,
                ]
            ]
        );

        $second_section = $generator->create_section($activity);
        $second_section->update_relationships(
            [
                [
                    'core_relationship_id' => $relationships->find('idnumber', constants::RELATIONSHIP_SUBJECT)->id,
                    'can_view' => true,
                    'can_answer' => true,
                ],
                [
                    'core_relationship_id' => $relationships->find('idnumber', constants::RELATIONSHIP_MANAGER)->id,
                    'can_view' => true,
                    'can_answer' => true,
                ]
            ]
        );

        return activity::repository()->where('id', $activity->id)
            ->eager_load_instance_creation_data()->get()->first();
    }
}