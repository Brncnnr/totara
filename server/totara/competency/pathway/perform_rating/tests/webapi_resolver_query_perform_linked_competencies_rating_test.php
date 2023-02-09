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
 * @package totara_competency
 */

use core\orm\query\builder;
use mod_perform\constants;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use pathway_perform_rating\entity\perform_rating as perform_rating_entity;
use pathway_perform_rating\models\perform_rating as perform_rating_model;
use pathway_perform_rating\testing\generator as pathway_perform_rating_generator;
use totara_core\advanced_feature;
use totara_core\relationship\relationship;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once __DIR__.'/perform_rating_base_testcase.php';

/**
 * @group pathway_perform_rating
 * @group totara_competency
 */
class pathway_perform_rating_webapi_resolver_query_linked_competencies_rating_testcase extends perform_rating_base_testcase {

    use webapi_phpunit_helper;

    /**
     * Returns the name of the query used for this testcase
     *
     * @return string
     */
    protected function get_query_name(): string {
        return 'pathway_perform_rating_linked_competencies_rating';
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void {
        parent::setUp();

        if (!core_component::get_plugin_directory('performelement', 'linked_review')) {
            $this->markTestSkipped('Required linked review plugin is not present');
        }
    }

    public function test_user_can_view() {
        $data = $this->create_data();

        $this->setUser($data->manager_user);

        $args = [
            'input' => [
                'user_id' => $data->subject_user->id,
                'competency_id' => $data->competency->id,
            ]
        ];

        $result = $this->resolve_graphql_query($this->get_query_name(), $args);
        $this->assertArrayHasKey('rating', $result);
        // There's no rating yet, so should be empty
        $this->assertNull($result['rating']);

        $this->setUser($data->subject_user);

        $result = $this->resolve_graphql_query($this->get_query_name(), $args);
        $this->assertArrayHasKey('rating', $result);
        // There's no rating yet, so should be empty
        $this->assertNull($result['rating']);

        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        // Now create a rating and check that it gets returned
        $perform_rating = pathway_perform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance1),
            $data->section_element,
            $scale_value
        );

        $result = $this->resolve_graphql_query($this->get_query_name(), $args);
        $this->assertArrayHasKey('rating', $result);
        $this->assertInstanceOf(perform_rating_model::class, $result['rating']);
        $this->assertEquals($perform_rating->id, $result['rating']->id);

        $this->setUser($data->manager_user);

        $result = $this->resolve_graphql_query($this->get_query_name(), $args);
        $this->assertArrayHasKey('rating', $result);
        $this->assertInstanceOf(perform_rating_model::class, $result['rating']);
        $this->assertEquals($perform_rating->id, $result['rating']->id);
    }

    public function test_your_rating_shows_as_rater_role_for_self_rated_competency() {
        $data = $this->create_data(['rating_relationship' => constants::RELATIONSHIP_SUBJECT]);

        $this->setUser($data->subject_user);

        $args = [
            'input' => [
                'user_id' => $data->subject_user->id,
                'competency_id' => $data->competency->id,
            ]
        ];
        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        // Now create a rating and check that it gets returned
        pathway_perform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->subject_participant_instance1),
            $data->section_element,
            $scale_value
        );

        $result = $this->resolve_graphql_query($this->get_query_name(), $args);
        $this->assertArrayHasKey('rating', $result);
        $this->assertInstanceOf(perform_rating_model::class, $result['rating']);

        // Returns your rating for subject user that rated himself.
        $this->assertEquals('Your rating', $result['rating']->rater_role);

        $this->setUser($data->manager_user);

        $result = $this->resolve_graphql_query($this->get_query_name(), $args);
        $this->assertArrayHasKey('rating', $result);
        $this->assertInstanceOf(perform_rating_model::class, $result['rating']);

        // Returns relationship name for other users.
        $subject_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);
        $this->assertEquals($subject_relationship->name, $result['rating']->rater_role);
    }

    public function test_user_can_view_null_rating() {
        $data = $this->create_data();

        $args = [
            'input' => [
                'user_id' => $data->subject_user->id,
                'competency_id' => $data->competency->id,
            ]
        ];

        $this->setUser($data->subject_user);

        // Now create a rating and check that it gets returned
        $perform_rating = pathway_perform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance1),
            $data->section_element
        );

        $result = $this->parsed_graphql_operation($this->get_query_name(), $args);
        $this->assert_webapi_operation_successful($result);
        $webapi_data = $this->get_webapi_operation_data($result);

        $this->assertArrayHasKey('rating', $webapi_data);
        $this->assertArrayHasKey('scale_value', $webapi_data['rating']);
        $this->assertNull($webapi_data['rating']['scale_value']);
    }

    public function test_activity_deleted() {
        $data = $this->create_data();

        $args = [
            'input' => [
                'user_id' => $data->subject_user->id,
                'competency_id' => $data->competency->id,
            ]
        ];

        $this->setUser($data->subject_user);

        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        // Now create a rating and check that it gets returned
        $perform_rating = pathway_perform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance1),
            $data->section_element,
            $scale_value
        );

        $activity = activity_model::load_by_entity($data->participant_instance1->subject_instance->activity());

        // Now with the user existing we should get the proper names back
        $result = $this->parsed_graphql_operation($this->get_query_name(), $args);
        $this->assert_webapi_operation_successful($result);
        $webapi_data = $this->get_webapi_operation_data($result);

        $this->assertArrayHasKey('rating', $webapi_data);
        $this->assertArrayHasKey('activity', $webapi_data['rating']);
        $this->assertArrayHasKey('name', $webapi_data['rating']['activity']);
        $this->assertEquals($activity->name, $webapi_data['rating']['activity']['name']);

        // Now after deleting the activity we should still get a result but the activity should be null
        $activity->delete();

        $result = $this->parsed_graphql_operation($this->get_query_name(), $args);
        $this->assert_webapi_operation_successful($result);
        $webapi_data = $this->get_webapi_operation_data($result);

        $this->assertArrayHasKey('rating', $webapi_data);
        $this->assertArrayHasKey('activity', $webapi_data['rating']);
        $this->assertNull($webapi_data['rating']['activity']);
    }

    public function test_user_deleted() {
        $data = $this->create_data();

        $args = [
            'input' => [
                'user_id' => $data->subject_user->id,
                'competency_id' => $data->competency->id,
            ]
        ];

        $this->setUser($data->subject_user);

        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        // Now create a rating and check that it gets returned
        $perform_rating = pathway_perform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance1),
            $data->section_element,
            $scale_value
        );

        // Now with the user existing we should get the proper names back
        $result = $this->parsed_graphql_operation($this->get_query_name(), $args);
        $this->assert_webapi_operation_successful($result);
        $webapi_data = $this->get_webapi_operation_data($result);

        $this->assertArrayHasKey('rating', $webapi_data);
        $this->assertArrayHasKey('rater_user', $webapi_data['rating']);
        $this->assertArrayHasKey('fullname', $webapi_data['rating']['rater_user']);
        $this->assertArrayHasKey('profileimageurl', $webapi_data['rating']['rater_user']);
        $expected_name = $data->manager_user->firstname.' '.$data->manager_user->lastname;
        $this->assertEquals($expected_name, $webapi_data['rating']['rater_user']['fullname']);
        $this->assertNotEquals('? ?', $webapi_data['rating']['rater_user']['fullname']);
        $this->assertStringContainsString(
            'moodle/theme/image.php/_s/ventura/core/1/u/f1',
            $webapi_data['rating']['rater_user']['profileimageurl']
        );

        $this->delete_user($data->manager_user);

        // We still should get a result
        $result = $this->parsed_graphql_operation($this->get_query_name(), $args);
        $this->assert_webapi_operation_successful($result);
        $webapi_data = $this->get_webapi_operation_data($result);

        $this->assertArrayHasKey('rating', $webapi_data);
        $this->assertArrayHasKey('rater_user', $webapi_data['rating']);
        $this->assertArrayHasKey('fullname', $webapi_data['rating']['rater_user']);
        $this->assertArrayHasKey('profileimageurl', $webapi_data['rating']['rater_user']);
        $this->assertEquals('? ?', $webapi_data['rating']['rater_user']['fullname']);
        $this->assertStringContainsString(
            'moodle/theme/image.php/_s/ventura/core/1/u/f1',
            $webapi_data['rating']['rater_user']['profileimageurl']
        );
    }

    public function test_user_purged() {
        $data = $this->create_data();

        $args = [
            'input' => [
                'user_id' => $data->subject_user->id,
                'competency_id' => $data->competency->id,
            ]
        ];

        $this->setUser($data->subject_user);

        $scale_value = $data->competency->scale->sorted_values_high_to_low->first();

        // Now create a rating and check that it gets returned
        $perform_rating = pathway_perform_rating_generator::instance()->create_perform_rating(
            $data->competency,
            participant_instance_model::load_by_entity($data->participant_instance1),
            $data->section_element,
            $scale_value
        );

        /** @var perform_rating_entity $rating */
        $rating = perform_rating_entity::repository()->find($perform_rating->id);
        $rating->rater_user_id = null;
        $rating->save();

        // Now with the user existing we should get the proper names back
        $result = $this->parsed_graphql_operation($this->get_query_name(), $args);
        $this->assert_webapi_operation_successful($result);
        $webapi_data = $this->get_webapi_operation_data($result);

        $this->assertArrayHasKey('rating', $webapi_data);
        $this->assertArrayHasKey('rater_user', $webapi_data['rating']);
        $this->assertNull($webapi_data['rating']['rater_user']);
    }

    public function test_user_cannot_view_rating() {
        $data = $this->create_data();

        $this->setUser($data->another_user);

        $args = [
            'input' => [
                'user_id' => $data->subject_user->id,
                'competency_id' => $data->competency->id,
            ]
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('you do not currently have permissions to do that');

        $this->resolve_graphql_query($this->get_query_name(), $args);
    }

    public function test_manager_without_permission() {
        $data = $this->create_data();

        $role = builder::table('role')->where('shortname', 'staffmanager')->one();
        unassign_capability('totara/competency:view_other_profile', $role->id);

        $this->setUser($data->manager_user);

        $args = [
            'input' => [
                'user_id' => $data->subject_user->id,
                'competency_id' => $data->competency->id,
            ]
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('you do not currently have permissions to do that');

        $this->resolve_graphql_query($this->get_query_name(), $args);
    }

    public function test_perform_feature_not_enabled() {
        $this->setAdminUser();

        advanced_feature::disable('performance_activities');

        $args = [
            'input' => [
                'user_id' => 1,
                'competency_id' => 2,
            ]
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Feature performance_activities is not available');

        $this->resolve_graphql_query($this->get_query_name(), $args);
    }

    public function test_assignments_feature_not_enabled() {
        $this->setAdminUser();

        advanced_feature::disable('competency_assignment');

        $args = [
            'input' => [
                'user_id' => 1,
                'competency_id' => 2,
            ]
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Feature competency_assignment is not available');

        $this->resolve_graphql_query($this->get_query_name(), $args);
    }

    public function test_user_not_logged_in() {
        $args = [
            'input' => [
                'user_id' => 1,
                'competency_id' => 2,
            ]
        ];

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('You are not logged in');

        $this->resolve_graphql_query($this->get_query_name(), $args);
    }

}