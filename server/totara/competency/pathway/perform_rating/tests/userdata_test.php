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
 * @package pathway_perform_rating
 */

use core\collection;
use core_phpunit\testcase;

use mod_perform\testing\generator as perform_generator;

use pathway_perform_rating\entity\perform_rating;
use pathway_perform_rating\userdata\perform_rating_other;
use pathway_perform_rating\userdata\perform_rating_self;
use totara_competency\testing\generator as competency_generator;
use totara_core\relationship\relationship;

use totara_hierarchy\entity\scale_value;

use totara_userdata\userdata\target_user;

/**
 * @group pathway_perform_rating
 * @group totara_competency
 */
class pathway_perform_userdata_testcase extends testcase {
    public function test_purge_self() {
        $data = $this->create_test_data();
        $user = $data->user1;
        $user_id = $user->id;
        $context = context_system::instance();

        $self_rating_count = perform_rating::repository()
            ->where('user_id', $user_id)
            ->where('rater_user_id', $user_id)
            ->count();

        $this->assertEquals(
            $self_rating_count,
            perform_rating_self::execute_count($user, $context)
        );

        perform_rating_self::execute_purge($user, $context);
        $this->assertEquals(
            0,
            perform_rating_self::execute_count($user, $context)
        );

        $existing_ratings_after_purge = $data->ratings->filter(
            function (perform_rating $rating) use ($user_id): bool {
                $purged_self_rated = $rating->user_id === $user_id
                    && $rating->rater_user_id === $user_id;

                return !$purged_self_rated;
            }
        );

        $actual_ratings_after_purge = perform_rating::repository()->get();
        $this->assertEquals(
            $existing_ratings_after_purge->count(),
            $actual_ratings_after_purge->count()
        );

        foreach ($actual_ratings_after_purge as $rating) {
            $self_rated = $rating->user_id === $user_id
                && $rating->rater_user_id === $user_id;

            $this->assertFalse($self_rated);
        }
    }

    public function test_purge_others() {
        $data = $this->create_test_data();
        $user = $data->user1;
        $user_id = $user->id;
        $context = context_system::instance();

        $other_rating_count = perform_rating::repository()
            ->where('user_id', '!=', $user_id)
            ->where('rater_user_id', $user_id)
            ->count();
        $this->assertEquals(
            $other_rating_count,
            perform_rating_other::execute_count($user, $context)
        );

        perform_rating_other::execute_purge($user, $context);
        $this->assertEquals(
            0,
            perform_rating_other::execute_count($user, $context)
        );

        $nulled_other_rating_count = perform_rating::repository()
            ->where_null('rater_user_id')
            ->count();
        $this->assertEquals($other_rating_count, $nulled_other_rating_count);
    }

    public function test_purge_no_rating() {
        $data = $this->create_test_data();
        $user = $data->no_rating_user;
        $context = context_system::instance();

        $this->assertEquals(0, perform_rating_self::execute_count($user, $context));
        $this->assertEquals(
            perform_rating_self::RESULT_STATUS_SUCCESS,
            perform_rating_self::execute_purge($user, $context)
        );

        $this->assertEquals(0, perform_rating_other::execute_count($user, $context));
        $this->assertEquals(
            perform_rating_other::RESULT_STATUS_SUCCESS,
            perform_rating_other::execute_purge($user, $context)
        );
    }

    public function test_export_self() {
        $data = $this->create_test_data();
        $user = $data->user1;
        $user_id = $user->id;
        $context = context_system::instance();

        $self_rating_count = perform_rating::repository()
            ->where('user_id', $user_id)
            ->where('rater_user_id', $user_id)
            ->count();
        $this->assertGreaterThan(0, $self_rating_count);

        $exported = perform_rating_self::execute_export($user, $context)
            ->data['perform_rating_self'];
        $this->assertCount($self_rating_count, $exported);

        $expected_by_id = $data->ratings->filter(
            function (perform_rating $rating) use ($user_id): bool {
                return $rating->user_id === $user_id
                    && $rating->rater_user_id === $user_id;
            }
        )
        ->key_by('id');

        foreach ($exported as $export) {
            $expected = $expected_by_id->item($export['id']);

            $this->assertNotNull($expected);
            $this->assert_export_equals($expected, $data->activity->name, $export);
        }
    }

    public function test_export_others() {
        $data = $this->create_test_data();
        $user = $data->user1;
        $user_id = $user->id;
        $context = context_system::instance();

        $other_rating_count = perform_rating::repository()
            ->where('user_id', '!=', $user_id)
            ->where('rater_user_id', $user_id)
            ->count();
        $this->assertGreaterThan(0, $other_rating_count);

        $exported = perform_rating_other::execute_export($user, $context)
            ->data['perform_rating_other'];
        $this->assertCount($other_rating_count, $exported);

        $expected_by_id = $data->ratings->filter(
            function (perform_rating $rating) use ($user_id): bool {
                return $rating->user_id !== $user_id
                    && $rating->rater_user_id === $user_id;
            }
        )
        ->key_by('id');

        foreach ($exported as $export) {
            $expected = $expected_by_id->item($export['id']);

            $this->assertNotNull($expected);
            $this->assert_export_equals($expected, $data->activity->name, $export);
        }
    }

    private function create_test_data(): stdClass {
        $this->setAdminUser();

        $scale_id = scale_value::repository()->order_by('id')->first()->id;
        $competency_id = competency_generator::instance()->create_competency()->id;

        $perform_generator = perform_generator::instance();
        $activity = $perform_generator->create_activity_in_container();
        $activity_id = (int)$activity->id;

        $core_generator = $this->getDataGenerator();
        $user1 = new target_user($core_generator->create_user());
        $user2 = new target_user($core_generator->create_user());
        $no_rating_user = new target_user($core_generator->create_user());

        $subject_relation = relationship::load_by_idnumber('subject');
        $manager_relation = relationship::load_by_idnumber('manager');
        $can_answer = 'subject,manager';

        $user1_subject_instance = $perform_generator->create_subject_instance([
            'activity_id' => $activity_id,
            'subject_user_id' => $user1->id,
            'other_participant_id' => $user2->id,
            'relationships_can_answer' => $can_answer
        ]);

        $user2_subject_instance = $perform_generator->create_subject_instance([
            'activity_id' => $activity_id,
            'subject_user_id' => $user2->id,
            'other_participant_id' => $user1->id,
            'relationships_can_answer' => $can_answer
        ]);

        $no_rating_user_instance = $perform_generator->create_subject_instance([
            'activity_id' => $activity_id,
            'subject_user_id' => $no_rating_user->id,
            'other_participant_id' => $user1->id,
            'relationships_can_answer' => $can_answer
        ]);

        $data = (object)[
            'activity' => $activity,
            'user1' => $user1,
            'user1_subject_instance' => $user1_subject_instance,
            'user2' => $user2,
            'user2_subject_instance' => $user2_subject_instance,
            'no_rating_user' => $no_rating_user,
            'no_rating_user_instance' => $no_rating_user_instance,
            'relationship_self_id' => (int)$subject_relation->id,
            'relationship_other_id' => (int)$manager_relation->id,
            'competency_id' => $competency_id,
            'scale_id' => $scale_id
        ];

        $data->ratings = collection::new([
            [$user1->id, $user1->id, $data->user1_subject_instance],
            [$user1->id, $user2->id, $data->user1_subject_instance],
            [$user2->id, $user2->id, $data->user2_subject_instance],
            [$user2->id, $user1->id, $data->user2_subject_instance]
        ])
        ->map(
            function (array $raw) use ($data): perform_rating {
                [$target_user_id, $rating_user_id, $subject_instance] = $raw;

                $relationship_id = $target_user_id === $rating_user_id
                    ? $data->relationship_self_id
                    : $data->relationship_other_id;

                $rating = new perform_rating();
                $rating->user_id = $target_user_id;
                $rating->competency_id = $data->competency_id;
                $rating->scale_value_id = $data->scale_id;
                $rating->activity_id = $data->activity->id;
                $rating->subject_instance_id = $subject_instance->id;
                $rating->rater_user_id = $rating_user_id;
                $rating->rater_relationship_id = $relationship_id;

                $rating->save();

                return $rating;
            }
        );

        return $data;
    }

    private function assert_export_equals(
        perform_rating $rating,
        string $activity_name,
        array $export
    ): void {
        $expected = [
            'id' => (int)$rating->id,
            'user_id' => (int)$rating->user_id,
            'competency_name' => core_text::entities_to_utf8(format_string($rating->competency->fullname)),
            'scale_value_name' => core_text::entities_to_utf8(format_string($rating->scale_value->name)),
            'rater_user_id' => (int)$rating->rater_user_id,
            'rater_relationship' => $rating->rater_relationship->idnumber,
            'activity_name' => $activity_name,
            'created_at' => (int)$rating->created_at
        ];

        $this->assertEquals($expected, $export);
    }
}