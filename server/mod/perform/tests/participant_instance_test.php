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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\collection;
use core\entity\user;
use mod_perform\constants;
use mod_perform\testing\generator as perform_generator;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\participant_source;
use mod_perform\models\response\section_element_response;
use mod_perform\entity\activity\element_response as element_response_entity;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use totara_core\relationship\relationship;

/**
 * @group perform
 */
class mod_perform_participant_instance_testcase extends \core_phpunit\testcase {
    public function test_get_activity_roles(): void {
        $sub_r = constants::RELATIONSHIP_SUBJECT;
        $mgr_r = constants::RELATIONSHIP_MANAGER;
        $appr_r = constants::RELATIONSHIP_APPRAISER;
        $peer_r = constants::RELATIONSHIP_PEER;
        $mtr_r = constants::RELATIONSHIP_MENTOR;
        $rwr_r = constants::RELATIONSHIP_REVIEWER;

        [$uid1, $uid2, $uid3, $mgr_uid, $appr_uid] = $this->create_users(5)->pluck('id');

        $subject_instance_details = [
            'relationships_can_view' => '',
            'subject_is_participating' => false,
            'include_questions' => false
        ];

        $this->create_participant_instances(
            $uid1,
            [
                $mgr_uid => [$mgr_r, $mtr_r],
                $appr_uid => [$appr_r],
                $uid2 => [$rwr_r]
            ],
            $subject_instance_details
        );

        $this->create_participant_instances(
            $uid2,
            [
                $mgr_uid => [$rwr_r],
                $appr_uid => [$appr_r],
                $uid1 => [$peer_r]
            ],
            $subject_instance_details
        );

        $expected = [
            $uid1 => [$sub_r, $peer_r],
            $uid2 => [$sub_r, $rwr_r],
            $uid3 => [],
            $mgr_uid => [$mgr_r, $mtr_r, $rwr_r],
            $appr_uid => [$appr_r]
        ];

        $this->assert_activity_roles($expected, false);
    }

    public function test_get_activity_roles_for_suspended_subjects(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/user/lib.php");

        $users = collection
            ::new([
                constants::RELATIONSHIP_SUBJECT,
                constants::RELATIONSHIP_MANAGER,
                constants::RELATIONSHIP_APPRAISER,
                constants::RELATIONSHIP_PEER,
                constants::RELATIONSHIP_MENTOR,
                constants::RELATIONSHIP_REVIEWER
            ])
            ->map(
                function (string $role): array {
                    return [$this->create_users(1)->first()->id, $role];
                }
            );

        $active_role_assignments = $users->reduce(
            function (array $assignments, array $tuple): array {
                [$user_id, $role] = $tuple;
                $assignments[$user_id] = [$role];

                return $assignments;
            },
            []
        );

        [$subject_id, ] = $users->first();
        $this->create_participant_instances(
            $subject_id,
            $active_role_assignments,
            [
                'relationships_can_view' => '',
                'subject_is_participating' => false,
                'include_questions' => false
            ]
        );

        // Initially all roles are visible for an active subject whatever the
        // value for the active user only filter.
        $this->assert_activity_roles($active_role_assignments, false);
        $this->assert_activity_roles($active_role_assignments, true);

        // If the subject is now suspended, all role assignments should still be
        // returned if the active user only filter is disabled.
        user_suspend_user($subject_id);
        $this->assert_activity_roles($active_role_assignments, false);

        // However, if the active user only filter is enabled, there should be no
        // roles returned.
        $suspended_assignments = $users->reduce(
            function (array $assignments, array $tuple): array {
                [$user_id, ] = $tuple;
                $assignments[$user_id] = [];

                return $assignments;
            },
            []
        );
        $this->assert_activity_roles($suspended_assignments, true);

        // If the subject is now reactivated, all original role assignments are
        // returned whatever the value for the active user only filter.
        user_unsuspend_user($subject_id);

        $this->assert_activity_roles($active_role_assignments, false);
        $this->assert_activity_roles($active_role_assignments, true);
    }

    /**
     * Data provider for test_manually_delete
     */
    public function td_manually_delete(): array {
        return [
            'answering participant' => [true],
            'read only participant' => [true]
        ];
    }

    /**
     * @dataProvider td_manually_delete
     *
     * Test to delete participant instance, participant sections and section element responses.
     */
    public function test_manually_delete(bool $is_viewonly): void {
        [$subject_uid, $participant_uid] = $this->create_users(2)->pluck('id');

        [$subject_pi, [$appraiser_pi]] = $this->create_participant_instances(
            $subject_uid,
            [$participant_uid => [constants::RELATIONSHIP_APPRAISER]],
            [
                'subject_is_participating' => true,
                'other_participant_id' => $participant_uid,
                'include_questions' => true,
                'relationships_can_answer' => $is_viewonly ? 'subject' : 'subject,manager',
                'relationships_can_view' => $is_viewonly ? 'manager' : ''
            ]
        );

        $section = section_element::load_by_entity(
            section_element_entity::repository()->get()->first()
        );

        $this->create_response($subject_pi, $section);
        if (!$is_viewonly) {
            $this->create_response($appraiser_pi, $section);
        }

        $subject_pi_id = $subject_pi->id;
        $appraiser_pi_id = $appraiser_pi->id;

        $this->assert_participant_instance_and_response('subject', $subject_pi_id, true, true);
        $this->assert_participant_instance_and_response('appraiser', $appraiser_pi_id, true, !$is_viewonly);

        $subject_pi->manually_delete();
        $this->assert_participant_instance_and_response('subject', $subject_pi_id, false, false);
        $this->assert_participant_instance_and_response('appraiser', $appraiser_pi_id, true, !$is_viewonly);

        $appraiser_pi->manually_delete();
        $this->assert_participant_instance_and_response('subject', $subject_pi_id, false, false);
        $this->assert_participant_instance_and_response('appraiser', $appraiser_pi_id, false, false);
    }

    /**
     * Generates test data.
     *
     * @param int $subject_userid subject user id.
     * @param array $all_relationships [user id => [relationship idnumbers]] mappings for
     *        other participants.
     * @param array<string,mixed> $values additional subject instance details
     *        (other than the activity id and subject user id) to use when
     *        creating the subject instance.
     *
     * @return array a [subject participant instance, other participant_instance[]] tuple.
     */
    private function create_participant_instances(
        int $subject_userid, array $all_relationships, array $values = []
    ): array {
        $this->setAdminUser();

        $perform_generator = perform_generator::instance();
        $subject_instance_id = $this
            ->create_subject_instance($subject_userid, $values)
            ->id;

        $subject_participant_instance = $this->create_participant_instance(
            $subject_instance_id,
            $subject_userid,
            $perform_generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT)
        );

        $participant_instances = [];
        foreach ($all_relationships as $userid => $relationships) {
            foreach ($relationships as $relationship) {
                $participant_instances[] = $this->create_participant_instance(
                    $subject_instance_id,
                    $userid,
                    $perform_generator->get_core_relationship($relationship)
                );
            }
        }

        return [$subject_participant_instance, $participant_instances];
    }

    /**
     * Generates test users.
     *
     * @param int count no of users to generate.
     *
     * @return collection|stdClass[] the created users.
     */
    private function create_users(int $count): collection {
        $this->setAdminUser();

        return collection::new(range(0, $count - 1))
            ->map(
                function (int $i): stdClass {
                    return self::getDataGenerator()->create_user();
                }
            );
    }

    /**
     * Creates a participant instance.
     *
     * @return participant_instance participant instance.
     */
    private function create_participant_instance(
        int $subject_instance_id, int $participant_userid, relationship $relationship
    ): participant_instance {
        $pi = new participant_instance_entity();
        $pi->core_relationship_id = $relationship->id;
        $pi->participant_id = $participant_userid;
        $pi->participant_source = participant_source::INTERNAL;
        $pi->subject_instance_id = $subject_instance_id;
        $pi->save();

        return participant_instance::load_by_entity($pi);
    }

    public function test_get_participant_instances_to_close_for_suspended_users(): void {
        [$user1_id, $user2_id, $user3_id] = $this->create_users(3)->pluck('id');

        $subject_instance_details = [
            'relationships_can_view' => '',
            'subject_is_participating' => false,
            'include_questions' => false
        ];

        $this->create_participant_instances(
            $user1_id,
            [
                $user2_id => [constants::RELATIONSHIP_MANAGER],
                $user3_id => [constants::RELATIONSHIP_APPRAISER],
            ],
            $subject_instance_details
        );

        $this->create_participant_instances(
            $user2_id,
            [
                $user3_id => [constants::RELATIONSHIP_APPRAISER],
            ],
            $subject_instance_details
        );

        self::assertEquals(5, participant_instance_entity::repository()->count());
        $user_1_instance_ids = participant_instance_entity::repository()
            ->where('participant_id', $user1_id)
            ->get()
            ->pluck('id');
        self::assertCount(1, $user_1_instance_ids);
        $user_2_instance_ids = participant_instance_entity::repository()
            ->where('participant_id', $user2_id)
            ->get()
            ->pluck('id');
        self::assertCount(2, $user_2_instance_ids);

        // There are no suspended users, so result should be empty.
        $result = participant_instance::get_participant_instances_to_close_for_suspended_users()->to_array();
        self::assertEmpty($result);

        // Set users 1 & 2 to suspended.
        user::repository()
            ->where_in('id', [$user1_id, $user2_id])
            ->update(['suspended' => 1]);

        $result = participant_instance::get_participant_instances_to_close_for_suspended_users()->to_array();
        self::assertCount(3, $result);
        self::assertEqualsCanonicalizing(
            array_merge($user_1_instance_ids, $user_2_instance_ids),
            [$result[0]->id, $result[1]->id, $result[2]->id]
        );

        // Close one participant instance. It should not be in the result anymore.
        $participant_instance = participant_instance::load_by_id($user_2_instance_ids[0]);
        $participant_instance->manually_close();

        $result = participant_instance::get_participant_instances_to_close_for_suspended_users()->to_array();
        self::assertCount(2, $result);
        self::assertEqualsCanonicalizing(
            [$user_1_instance_ids[0], $user_2_instance_ids[1]],
            [$result[0]->id, $result[1]->id]
        );
    }

    /**
     * Creates a subject instance.
     *
     * @param int $subject_userid subject user id.
     * @param array<string,mixed> $values additional subject instance details
     *        (other than the activity id and subject user id) to use when
     *        creating the subject instance.
     *
     * @return subject_instance_entity the created subject instance.
     */
    private function create_subject_instance(
        int $subject_userid, array $values = []
    ): subject_instance_entity {
        $this->setAdminUser();

        $generator = perform_generator::instance();
        $data = array_merge(
            $values,
            [
                'activity_id' => $generator->create_activity_in_container()->id,
                'subject_user_id' => $subject_userid
            ]
        );

        return $generator->create_subject_instance($data);
    }

    /**
     * Creates a participant response.
     *
     * @return participant_instance participant instance.
     */
    private function create_response(
        participant_instance $pi, section_element $section
    ): section_element_response {
        $response = new section_element_response($pi, $section, null, new collection());

        return $response
            ->set_response_data(json_encode('Hooooray'))
            ->save();
    }

    /**
     * Validates whether the specified participant instance exists and has
     * responses.
     */
    private function assert_participant_instance_and_response(
        string $tag,
        int $pi_id,
        bool $has_pi,
        bool $has_responses
    ): void {
        $this->assertEquals(
            $has_pi,
            participant_instance_entity::repository()->where('id', $pi_id)->exists(),
            $has_pi
                ? "$tag participant instance does not exist when it should"
                : "$tag participant instance exists when it should not"
        );

        $responses = element_response_entity::repository()
            ->where('participant_instance_id', $pi_id)
            ->count();

        $has_responses
            ? $this->assertTrue($responses > 0, "$tag has no responses")
            : $this->assertEquals(0, $responses, "$tag has responses");
    }

    /**
     * Validates whether the given user ids have the expected activity roles.
     */
    public function assert_activity_roles(
        array $expected,
        bool $only_active_users
    ): void {
        foreach ($expected as $uid => $expected_roles) {
            $actual_roles = participant_instance::get_activity_roles_for($uid, $only_active_users)
                ->map(
                    function (relationship $relationship): string {
                        return $relationship->idnumber;
                    }
                )
                ->all();

            $this->assertEqualsCanonicalizing($expected_roles, $actual_roles);
        }
    }
}
