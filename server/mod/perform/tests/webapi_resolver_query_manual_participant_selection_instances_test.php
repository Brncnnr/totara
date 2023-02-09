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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\models\activity\subject_instance;
use totara_core\advanced_feature;
use totara_core\relationship\relationship;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/subject_instance_set_participant_users_test.php');

/**
 * @group perform
 */
class mod_perform_webapi_resolver_query_manual_participant_selection_instances_testcase extends advanced_testcase {

    private const QUERY = 'mod_perform_manual_participant_selection_instances';

    use webapi_phpunit_helper;

    public function test_ajax_query_successful(): void {
        // Basically just making sure the data we create in manual_participant_selector_test_data::create_data() is returned.
        // We'll have a proper integration test for creating the data elsewhere.
        self::setAdminUser();
        $data = new manual_participant_selector_test_data($this->getDataGenerator());
        $data->create_data();

        // Relationships
        $peer_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_PEER);
        $mentor_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_MENTOR);
        $reviewer_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_REVIEWER);
        $external_relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_EXTERNAL);

        // Manager user can see activity 1 and activity 2 instances for user1 (2 total)
        self::setUser($data->manager_user);
        $manager_data = $this->get_query_data();
        $this->assertCount(2, $manager_data);
        [$managers_instance1, $managers_instance2] = $manager_data;

        // No duplicates
        $this->assertNotEquals($managers_instance1['subject_instance']['id'], $managers_instance2['subject_instance']['id']);
        // Make sure dates are different
        $this->assertNotEquals(
            $managers_instance1['subject_instance']['created_at'], $managers_instance2['subject_instance']['created_at']
        );

        $this->assert_same_subject_instance($data->act1_user1_subject_instance, $managers_instance1['subject_instance']);
        $this->assert_same_relationships(
            [$mentor_relationship, $reviewer_relationship, $external_relationship], // Mentor should be ordered before reviewer.
            $managers_instance1['manual_relationships']
        );

        $this->assert_same_subject_instance($data->act2_user1_subject_instance, $managers_instance2['subject_instance']);
        $this->assert_same_relationships([$peer_relationship], $managers_instance2['manual_relationships']);


        // Appraiser user can see activity 2 and activity 3 instances for user1 and user2 (4 total)
        self::setUser($data->appraiser_user);
        $appraiser_data = $this->get_query_data();
        $this->assertCount(4, $appraiser_data);
        [$appraiser_instance1, $appraiser_instance2, $appraiser_instance3, $appraiser_instance4] = $appraiser_data;

        // No duplicates
        $this->assertNotEquals($appraiser_instance1['subject_instance']['id'], $appraiser_instance2['subject_instance']['id']);
        $this->assertNotEquals($appraiser_instance2['subject_instance']['id'], $appraiser_instance3['subject_instance']['id']);
        $this->assertNotEquals($appraiser_instance3['subject_instance']['id'], $appraiser_instance4['subject_instance']['id']);

        $this->assert_same_subject_instance($data->act2_user1_subject_instance, $appraiser_instance1['subject_instance']);
        $this->assert_same_relationships(
            [$reviewer_relationship, $external_relationship], $appraiser_instance1['manual_relationships']
        );

        $this->assert_same_subject_instance($data->act3_user1_subject_instance, $appraiser_instance2['subject_instance']);
        $this->assert_same_relationships(
            [$peer_relationship, $external_relationship], $appraiser_instance2['manual_relationships']
        );

        $this->assert_same_subject_instance($data->act2_user2_subject_instance, $appraiser_instance3['subject_instance']);
        $this->assert_same_relationships(
            [$reviewer_relationship, $external_relationship], $appraiser_instance3['manual_relationships']
        );

        $this->assert_same_subject_instance($data->act3_user2_subject_instance, $appraiser_instance4['subject_instance']);
        $this->assert_same_relationships(
            [$peer_relationship, $external_relationship], $appraiser_instance4['manual_relationships']
        );


        // Subject user1 can see activity 1 and activity 3 instances for themselves (2 total)
        self::setUser($data->user1);
        $user1_data = $this->get_query_data();
        $this->assertCount(2, $user1_data);
        [$user1_instance1, $user1_instance2] = $user1_data;

        // No duplicates
        $this->assertNotEquals($user1_instance1['subject_instance']['id'], $user1_instance2['subject_instance']['id']);

        $this->assert_same_subject_instance($data->act1_user1_subject_instance, $user1_instance1['subject_instance']);
        $this->assert_same_relationships([$peer_relationship], $user1_instance1['manual_relationships']);
        $this->assert_same_subject_instance($data->act3_user1_subject_instance, $user1_instance2['subject_instance']);
        $this->assert_same_relationships([$reviewer_relationship], $user1_instance2['manual_relationships']);
    }

    public function test_ajax_query_failed(): void {
        self::setUser();
        $result = $this->parsed_graphql_operation(self::QUERY);
        $this->assert_webapi_operation_failed($result, 'not logged in');

        self::setAdminUser();
        advanced_feature::disable('performance_activities');
        $result = $this->parsed_graphql_operation(self::QUERY);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
    }

    /**
     * @param subject_instance $expected Subject instance entity created by data generator.
     * @param array $actual Subject instance data returned by GraphQL.
     */
    private function assert_same_subject_instance($expected, $actual): void {
        // Subject instance IDs are the same
        $this->assertEquals($expected->id, $actual['id']);

        // Subject user info is returned and is the same
        $this->assertEquals(
            $expected->subject_user->id,
            $actual['subject_user']['id']
        );
        // We expect date to be used in front end like '1 January 2020', but can change this assert if it becomes problematic.
        $this->assertEquals(
            date('j F Y', $expected->created_at),
            $actual['created_at']
        );
        $this->assertEquals(
            $expected->subject_user->fullname,
            $actual['subject_user']['fullname']
        );

        // Activity info is returned and is the same.
        $this->assertEquals(
            $expected->get_activity()->name,
            $actual['activity']['name']
        );
    }

    /**
     * @param relationship[] $expected Relationship models created by data generator.
     * @param string[][] $actual Relationship data returned by GraphQL.
     */
    private function assert_same_relationships($expected, $actual): void {
        $this->assertCount(count($expected), $actual);

        foreach ($expected as $i => $expected_relationship) {
            $this->assertEquals($expected_relationship->id, $actual[$i]['id']);
            $this->assertEquals($expected_relationship->name, $actual[$i]['name']);
        }
    }

    private function get_query_data(): array {
        $result = $this->parsed_graphql_operation(self::QUERY);
        $this->assert_webapi_operation_successful($result);
        return $this->get_webapi_operation_data($result);
    }

}
