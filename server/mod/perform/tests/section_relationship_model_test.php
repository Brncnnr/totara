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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

use core\orm\query\exceptions\record_not_found_exception;
use mod_perform\constants;
use mod_perform\entity\activity\section;
use mod_perform\models\activity\section_relationship;
use mod_perform\entity\activity\section_relationship as section_relationship_entity;

require_once(__DIR__.'/relationship_testcase.php');

/**
 * @group perform
 */
class mod_perform_section_relationship_model_testcase extends mod_perform_relationship_testcase {

    public function test_create_invalid_relationship_id() {
        $this->setAdminUser();
        $perform_generator = $this->perform_generator();
        $activity1 = $perform_generator->create_activity_in_container();
        $section1 = $perform_generator->create_section($activity1);

        $this->expectException(dml_write_exception::class);

        section_relationship::create(
            $section1->get_id(),
            -1,
            true
        );
    }

    public function test_create_invalid_section_id() {
        $this->setAdminUser();
        $non_existent_section_id = 1234;
        while (section::repository()->where('id', $non_existent_section_id)->exists()) {
            $non_existent_section_id ++;
        }
        $this->expectException(record_not_found_exception::class);

        section_relationship::create(
            $non_existent_section_id,
            $this->perform_generator()->get_core_relationship(constants::RELATIONSHIP_SUBJECT)->id,
            true
        );
    }

    public function test_create_missing_capability() {
        $this->setAdminUser();
        $perform_generator = $this->perform_generator();
        $subject_id = $perform_generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT)->id;
        $activity1 = $perform_generator->create_activity_in_container();
        $section1 = $perform_generator->create_section($activity1);

        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $this->expectException(required_capability_exception::class);
        $this->expectExceptionMessage('you do not currently have permissions to do that (Manage performance activities)');

        section_relationship::create(
            $section1->get_id(),
            $subject_id,
            true
        );
    }

    public function test_create_successful() {
        $this->setAdminUser();
        $perform_generator = $this->perform_generator();
        $subject_id = $perform_generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT)->id;
        $manager_id = $perform_generator->get_core_relationship(constants::RELATIONSHIP_MANAGER)->id;
        $appraiser_id = $perform_generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER)->id;
        $activity1 = $perform_generator->create_activity_in_container();
        $activity2 = $perform_generator->create_activity_in_container();
        $section1 = $perform_generator->create_section($activity1);
        $section2 = $perform_generator->create_section($activity1);

        $this->assert_section_relationships($section1, []);
        $this->assert_section_relationships($section2, []);

        $section_relationship = section_relationship::create(
            $section1->get_id(),
            $subject_id,
            true
        );
        $this->assertInstanceOf(section_relationship::class, $section_relationship);
        $this->assertEquals($section1->get_id(), $section_relationship->section_id);
        $this->assert_section_relationships($section1, [constants::RELATIONSHIP_SUBJECT]);
        $this->assert_section_relationships($section2, []);

        // Try to create the same - nothing should change.
        section_relationship::create(
            $section1->get_id(),
            $subject_id,
            true
        );
        $this->assert_section_relationships($section1, [constants::RELATIONSHIP_SUBJECT]);
        $this->assert_section_relationships($section2, []);

        // Add another one to the same section.
        section_relationship::create(
            $section1->get_id(),
            $manager_id,
            true
        );
        $this->assert_section_relationships($section1, [constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER]);
        $this->assert_section_relationships($section2, []);

        // Add another one to the other section.
        section_relationship::create(
            $section2->get_id(),
            $appraiser_id,
            true
        );
        $this->assert_section_relationships($section1, [constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER]);
        $this->assert_section_relationships($section2, [constants::RELATIONSHIP_APPRAISER]);
    }

    public function test_delete_invalid_section_id() {
        $this->setAdminUser();
        $subject_id = $this->perform_generator()->get_core_relationship(constants::RELATIONSHIP_SUBJECT)->id;
        $non_existent_section_id = 1234;
        while (section::repository()->where('id', $non_existent_section_id)->exists()) {
            $non_existent_section_id ++;
        }
        $this->expectException(record_not_found_exception::class);

        section_relationship::delete_with_properties($non_existent_section_id, $subject_id);
    }

    public function test_delete_missing_capability() {
        $this->setAdminUser();
        $perform_generator = $this->perform_generator();
        $subject = $perform_generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT)->id;
        $activity1 = $perform_generator->create_activity_in_container();
        $section1 = $perform_generator->create_section($activity1);

        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $this->expectException(required_capability_exception::class);
        $this->expectExceptionMessage('you do not currently have permissions to do that (Manage performance activities)');

        section_relationship::delete_with_properties($section1->get_id(), $subject);
    }

    public function test_delete_with_properties_successful() {
        $this->setAdminUser();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $this->perform_generator();
        $appraiser_id = $perform_generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER)->id;
        $activity1 = $perform_generator->create_activity_in_container();
        $activity2 = $perform_generator->create_activity_in_container();
        $section1 = $perform_generator->create_section($activity1);
        $section2 = $perform_generator->create_section($activity1);

        $this->assert_section_relationships($section1, []);
        $this->assert_section_relationships($section2, []);

        $perform_generator->create_section_relationship($section1, ['relationship' => constants::RELATIONSHIP_MANAGER]);
        $perform_generator->create_section_relationship($section1, ['relationship' => constants::RELATIONSHIP_SUBJECT]);
        $perform_generator->create_section_relationship($section1, ['relationship' => constants::RELATIONSHIP_APPRAISER]);
        $perform_generator->create_section_relationship($section2, ['relationship' => constants::RELATIONSHIP_APPRAISER]);
        $this->assert_section_relationships($section1, [constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_APPRAISER]);
        $this->assert_section_relationships($section2, [constants::RELATIONSHIP_APPRAISER]);

        section_relationship::delete_with_properties($section1->get_id(), $appraiser_id);
        $this->assert_section_relationships($section1, [constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_SUBJECT]);
        $this->assert_section_relationships($section2, [constants::RELATIONSHIP_APPRAISER]);

        section_relationship::delete_with_properties($section2->get_id(), $appraiser_id);
        $this->assert_section_relationships($section1, [constants::RELATIONSHIP_MANAGER, constants::RELATIONSHIP_SUBJECT]);
        $this->assert_section_relationships($section2, []);
    }

    public function test_is_subject() {
        $this->setAdminUser();
        $perform_generator = $this->perform_generator();
        $subject_id = $perform_generator->get_core_relationship('subject')->id;
        $manager_id = $perform_generator->get_core_relationship('manager')->id;
        $activity1 = $perform_generator->create_activity_in_container();
        $section1 = $perform_generator->create_section($activity1);
        $section2 = $perform_generator->create_section($activity1);

        $this->assert_section_relationships($section1, []);
        $this->assert_section_relationships($section2, []);

        $subject_section_relationship = section_relationship::create(
            $section1->get_id(),
            $subject_id,
            true
        );

        $manager_section_relationship = section_relationship::create(
            $section1->get_id(),
            $manager_id,
            true
        );

        $this->assertTrue($subject_section_relationship->get_is_subject());
        $this->assertFalse($manager_section_relationship->get_is_subject());
    }

    public function test_delete_successful() {
        $this->setAdminUser();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $this->perform_generator();
        $activity1 = $perform_generator->create_activity_in_container();
        $section1 = $perform_generator->create_section($activity1);

        $section_relationship = $perform_generator->create_section_relationship(
            $section1, ['relationship' => constants::RELATIONSHIP_MANAGER]
        );

        $this->assertCount(1, $section1->get_section_relationships());

        $section_relationship->delete();

        $section_relationships = section_relationship_entity::repository()
            ->where('section_id', $section1->id)
            ->get();

        $this->assertCount(0, $section_relationships);
    }
}