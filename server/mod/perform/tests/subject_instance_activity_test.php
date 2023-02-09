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
use totara_core\entity\relationship;

class mod_perform_subject_instance_activity_test extends advanced_testcase {

    public function setUp(): void {
        $this->setAdminUser();
    }

    public function test_getting_section_relationships_memoized_computation() {
        $data = $this->build_subject_activity();
        /** @var $subject_activity subject_instance_activity */
        $subject_activity = $data['subject_activity'];

        $property = new ReflectionProperty(subject_instance_activity::class, 'section_relationships');
        $property->setAccessible(true);
        $section_relationships_property = $property->getValue($subject_activity);
        $this->assertNull($section_relationships_property);

        $section_relationships = $subject_activity->get_section_relationships();
        $this->assertCount(5, $section_relationships);

        $this->assertEqualsCanonicalizing($section_relationships, $property->getValue($subject_activity));
    }

    public function test_getting_activity_relationships_memoized_computation() {
        $data = $this->build_subject_activity();
        /** @var $subject_activity subject_instance_activity */
        $subject_activity = $data['subject_activity'];

        $property = new ReflectionProperty(subject_instance_activity::class, 'activity_relationships');
        $property->setAccessible(true);
        $activity_relationships_property = $property->getValue($subject_activity);
        $this->assertNull($activity_relationships_property);

        $activity_relationships = $subject_activity->get_activity_relationships();
        $this->assertCount(3, $activity_relationships);

        $this->assertEqualsCanonicalizing($activity_relationships, $property->getValue($subject_activity));
    }

    public function test_checking_has_manual_relationship_memoized_computation() {
        $data = $this->build_subject_activity();
        /** @var $subject_activity subject_instance_activity */
        $subject_activity = $data['subject_activity'];
        $has_manual_relationship_property = new ReflectionProperty(subject_instance_activity::class, 'has_manual_relationship');
        $has_manual_relationship_property->setAccessible(true);

        $this->assertNull($has_manual_relationship_property->getValue($subject_activity));

        $has_manual_relationship = $subject_activity->has_manual_relationship();
        $this->assertTrue($has_manual_relationship);

        $this->assertEquals($has_manual_relationship, $has_manual_relationship_property->getValue($subject_activity));
    }

    public function test_getting_sections_belonging_to_relationship_memoized_computation() {
        $data = $this->build_subject_activity();
        /** @var $subject_activity subject_instance_activity */
        $subject_activity = $data['subject_activity'];
        $sections_belonging_to_relationship_property = new ReflectionProperty(
            subject_instance_activity::class,
            'section_relationships_grouped_by_relationship_id'
        );
        $sections_belonging_to_relationship_property->setAccessible(true);

        $this->assertNull($sections_belonging_to_relationship_property->getValue($subject_activity));

        $peer_relationship_id = $data['relationships']->find('idnumber', constants::RELATIONSHIP_PEER)->id;
        $subject_relationship_id = $data['relationships']->find('idnumber', constants::RELATIONSHIP_SUBJECT)->id;
        $manager_relationship_id = $data['relationships']->find('idnumber', constants::RELATIONSHIP_MANAGER)->id;

        $sections_belonging_to_peer = $subject_activity->get_section_relationships_owned_by_relationship_id($peer_relationship_id);
        $this->assertCount(1, $sections_belonging_to_peer);

        $sections_belonging_to_subject = $subject_activity->get_section_relationships_owned_by_relationship_id($subject_relationship_id);
        $this->assertCount(2, $sections_belonging_to_subject);

        $memorized_manager_relationships = $sections_belonging_to_relationship_property
            ->getValue($subject_activity)[$manager_relationship_id];
        $this->assertCount(2, $memorized_manager_relationships);

        $this->assertCount(3, $sections_belonging_to_relationship_property->getValue($subject_activity));
    }

    private function build_subject_activity(): array {

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

        $activity_entity = activity::repository()
            ->where('id', $activity->id)
            ->eager_load_instance_creation_data()
            ->get()
            ->first();

        return [
            'subject_activity' => new subject_instance_activity($activity_entity),
            'relationships' => $relationships,
        ];
    }
}