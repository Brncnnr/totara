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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use core\webapi\execution_context;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\subject_instance;
use mod_perform\entity\activity\participant_section;
use totara_job\job_assignment;

/**
 * Class mod_perform_subject_instance_testcase
 *
 * @group perform
 */
abstract class mod_perform_subject_instance_testcase extends advanced_testcase {

    /** @var stdClass */
    protected static $user;

    /** @var subject_instance */
    protected static $about_user_and_participating;

    /** @var subject_instance */
    protected static $about_someone_else_and_participating;

    /** @var subject_instance That the self::$user is about someone else but the user is a participant */
    protected static $about_user_but_not_participating;

    /** @var subject_instance A user activity that doesn't actually exist in the database anymore */
    protected static $non_existing;

    protected function setUp(): void {
        self::$user = self::getDataGenerator()->create_user([
            'firstname' => 'Actinguser',
            'middlename' => 'Manfred',
            'lastname' => 'Ziller',
        ]);

        self::create_user_activities(self::$user);

        self::setUser(self::$user);
    }

    protected function tearDown(): void {
        parent::tearDown();

        self::$user = null;
        self::$about_someone_else_and_participating = null;
        self::$about_user_and_participating = null;
        self::$about_user_but_not_participating = null;
        self::$non_existing = null;
    }

    /**
     * @param $target_user
     * @param string $activity_type
     * @throws coding_exception
     */
    protected static function create_user_activities($target_user, string $activity_type = 'appraisal'): void {
        // Change to the Admin user for creating the perform activity container.
        self::setAdminUser();

        $other_subject = self::getDataGenerator()->create_user([
            'firstname' => 'Otheruser',
            'middlename' => 'Testfred',
            'lastname' => 'Nontarget',
        ]);
        $other_participant = self::getDataGenerator()->create_user();

        self::$about_user_and_participating = subject_instance::load_by_entity(
            self::perform_generator()->create_subject_instance([
                'activity_name' => $activity_type . ' activity about target user',
                'activity_type' => $activity_type,
                'subject_user_id' => $target_user->id,
                'other_participant_id' => $other_participant->id,
                'subject_is_participating' => true,
            ])
        );

        self::$about_user_but_not_participating = subject_instance::load_by_entity(
            self::perform_generator()->create_subject_instance([
                'activity_name' => $activity_type . ' activity target user is not participating in',
                'activity_type' => $activity_type,
                'subject_user_id' => $target_user->id,
                'other_participant_id' => $other_participant->id,
                'subject_is_participating' => false,
            ])
        );

        self::$about_someone_else_and_participating = subject_instance::load_by_entity(
            self::perform_generator()->create_subject_instance([
                'activity_name' => $activity_type . ' activity about someone else and participating',
                'activity_type' => $activity_type,
                'subject_user_id' => $other_subject->id,
                'other_participant_id' => $target_user->id,
                'subject_is_participating' => false,
            ])
        );

        self::$non_existing = subject_instance::load_by_entity(
            self::perform_generator()->create_subject_instance([
                'activity_name' => $activity_type . ' subject instance will be deleted',
                'activity_type' => $activity_type,
                'subject_user_id' => $other_subject->id,
                'other_participant_id' => $target_user->id,
                'subject_is_participating' => false,
            ])
        );

        foreach (self::$non_existing->get_participant_instances() as $participant_instance) {
            participant_section::repository()->where('participant_instance_id', $participant_instance->get_id())->delete();
        }
        $subject_instance_id = self::$non_existing->id;
        (new subject_instance_entity($subject_instance_id))->delete();
    }

    /**
     * A data provider for most the primary user activity subject/participant combinations.
     *
     * Because data providers are called before set up, the user activity models must be delivered in closures.
     *
     * @return subject_instance[]
     */
    public function subject_instance_provider(): array {
        return [
            'activity about the user and the user is a participant' => [
                function () {
                    return self::$about_user_and_participating;
                }, true
            ],
            'activity about someone else the user is a participant' => [
                function () {
                    return self::$about_someone_else_and_participating;
                }, true
            ],
            'activity about the user but they are not participating' => [
                function () {
                    return self::$about_user_but_not_participating;
                }, false
            ],
            'activity that does not exist' => [
                function () {
                    return self::$non_existing;
                }, false
            ],
        ];
    }

    /**
     * @param subject_instance $expected
     * @param subject_instance $actual
     */
    protected static function assert_same_subject_instance(subject_instance $expected, subject_instance $actual): void {
        self::assertEquals($expected->id, $actual->id,
            'Expected subject instance ids to match'
        );

        self::assertEquals($expected->subject_user->id, $actual->subject_user->id,
            'Expected subject ids match'
        );

        self::assertEquals($expected->get_activity()->id, $actual->get_activity()->id,
            'Expected activity ids to match'
        );
    }

    /**
     * Helper to get execution context
     *
     * @param string $type
     * @param string|null $operation
     * @return execution_context
     */
    protected function get_execution_context(string $type = 'dev', ?string $operation = null): execution_context {
        return execution_context::create($type, $operation);
    }

    protected static function perform_generator(): \mod_perform\testing\generator {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return \mod_perform\testing\generator::instance();
    }

    protected function strip_expected_dates(array $actual_result): array {
        self::assertArrayHasKey(
            'created_at',
            $actual_result,
            'Result is expected to contain created_at'
        );

        $month_and_year = (new DateTime())->format('F Y');
        self::assertStringContainsString(
            $month_and_year,
            $actual_result['created_at'],
            'Expected created at to at least be the current month and year'
        );

        unset($actual_result['created_at']);

        return $actual_result;
    }

    /**
     * @param $manager
     * @param $employee
     * @throws coding_exception
     */
    protected function setup_manager_employee_job_assignment($manager, $employee): void {
        $manager_job_assignment = job_assignment::create(
            [
                'userid' => $manager->id,
                'idnumber' => $manager->id,
            ]
        );

        job_assignment::create(
            [
                'userid' => $employee->id,
                'idnumber' => $employee->id,
                'managerjaid' => $manager_job_assignment->id,
            ]
        );
    }

}