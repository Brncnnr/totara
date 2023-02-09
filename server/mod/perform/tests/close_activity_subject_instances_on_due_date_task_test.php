<?php
/**
 * This file is part of Totara Perform
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

use core\collection;
use core\orm\query\builder;
use core\webapi\execution_context;
use core_phpunit\testcase;
use mod_perform\constants;
use mod_perform\dates\date_offset;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\subject_instance;
use mod_perform\state\participant_instance\closed as participant_instance_availability_closed;
use mod_perform\state\participant_instance\open as participant_instance_availability_open;
use mod_perform\state\subject_instance\active as subject_instance_active;
use mod_perform\state\subject_instance\closed as subject_instance_availability_closed;
use mod_perform\state\subject_instance\open as subject_instance_availability_open;
use mod_perform\state\subject_instance\pending as subject_instance_pending;
use mod_perform\webapi\resolver\mutation\update_track_schedule;
use totara_webapi\phpunit\webapi_phpunit_helper;
use mod_perform\task\close_activity_subject_instances_on_due_date_task;
use mod_perform\testing\generator;

require_once(__DIR__ . '/webapi_resolver_mutation_update_track_schedule.php');

class mod_perform_close_activity_subject_instances_on_due_date_task_testcase extends testcase {

    use webapi_phpunit_helper;

    protected function setUp(): void {
        parent::setUp();
        self::setAdminUser();
    }

    public function test_execute_task(): void {
        $subject_instances_due = $this->create_subject_instances('due', true, true, true, true);
        $subject_instances_due_but_pending = $this->create_subject_instances('due but pending', false, true, true, true);
        $subject_instances_due_but_not_to_be_closed = $this->create_subject_instances(
            'due but not to be closed', true, true, true, false
        );
        $subject_instances_without_due_date = $this->create_subject_instances('without due date', true, false, false, true);
        $subject_instances_with_future_due_date = $this->create_subject_instances('future due date', true, true, false, true);

        [$si_open, $si_close, $pi_open, $pi_close] = $this->availability_statuses();
        $this->assert_availability($subject_instances_due, $si_open, $pi_open);
        $this->assert_availability($subject_instances_due_but_pending, $si_open, $pi_open);
        $this->assert_availability($subject_instances_due_but_not_to_be_closed, $si_open, $pi_open);
        $this->assert_availability($subject_instances_without_due_date, $si_open, $pi_open);
        $this->assert_availability($subject_instances_with_future_due_date, $si_open, $pi_open);

        (new close_activity_subject_instances_on_due_date_task())->execute();

        $this->assert_availability($subject_instances_due, $si_close, $pi_close);
        $this->assert_availability($subject_instances_due_but_pending, $si_close, $pi_close);
        $this->assert_availability($subject_instances_due_but_not_to_be_closed, $si_open, $pi_open);
        $this->assert_availability($subject_instances_without_due_date, $si_open, $pi_open);
        $this->assert_availability($subject_instances_with_future_due_date, $si_open, $pi_open);
    }

    // Check that the task throws an exception digest when closing some subject instances cause individual exceptions.
    public function test_execute_task_with_exceptions(): void {
        $subject_instances_due_triggering_exception = $this->create_subject_instances('with exception', true, true, true, true);
        $subject_instances_due = $this->create_subject_instances('due', true, true, true, true);

        /** @var subject_instance_entity $subject_instance */
        $subject_instance = $subject_instances_due_triggering_exception->first();

        foreach ($subject_instances_due_triggering_exception as $subject_instance) {
            // Muck up the subject_instance records to provoke an exception.
            builder::table('perform_subject_instance')
                ->where('id', $subject_instance->id)
                ->update(['progress' => - 1]);
        }

        try {
            (new close_activity_subject_instances_on_due_date_task())->execute();
            self::fail('expected Exception did not happen.');
        } catch (moodle_exception $e) {
            $subject_instance_ids = $subject_instances_due_triggering_exception->pluck('id');
            self::assertStringContainsString('There were exceptions trying to close some subject instances:', $e->getMessage());
            self::assertStringContainsString($subject_instance_ids[0] . ': Coding error detected', $e->getMessage());
            self::assertStringContainsString($subject_instance_ids[1] . ': Coding error detected', $e->getMessage());
        }

        // Make sure the other subject instances were still processed.
        [$si_open, $si_close, $pi_open, $pi_close] = $this->availability_statuses();
        $this->assert_availability($subject_instances_due, $si_close, $pi_close);
    }

    /**
     * @param string $activity_name
     * @param bool $subject_instance_active
     * @param bool $due_date
     * @param bool $set_past_due_date
     * @param bool $close_on_due_date
     * @return collection
     */
    private function create_subject_instances(
        string $activity_name,
        bool $subject_instance_active,
        bool $due_date,
        bool $set_past_due_date,
        bool $close_on_due_date
    ): collection {
        $no_of_subject_instances = 2;
        $perform_generator = generator::instance();
        $activity = $perform_generator->create_activity_in_container(['create_track' => true, 'activity_name' => $activity_name]);

        $subject_instance_due_date = null;
        if ($due_date) {
            $track = $activity->get_tracks()->first();
            $args = [
                'track_schedule' => [
                    'track_id' => $track->id,
                    'schedule_is_open' => false,
                    'schedule_is_fixed' => true,
                    'schedule_fixed_from' => ['iso' => '2020-12-04', 'timezone' => 'Pacific/Auckland'],
                    'schedule_fixed_to' => ['iso' => '2020-12-05', 'timezone' => 'Pacific/Auckland'],
                    'due_date_is_enabled' => true,
                    'due_date_is_fixed' => false,
                    'due_date_offset' => [
                        'count' => 2,
                        'unit' => date_offset::UNIT_DAY
                    ],
                    'repeating_is_enabled' => false,
                    'subject_instance_generation' => constants::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT,
                ],
            ];
            update_track_schedule::resolve($args, execution_context::create('dev'));

            $offset = date_offset::create_from_json($args['track_schedule']['due_date_offset']);
            $subject_instance_due_date = $set_past_due_date
                ? $offset->apply(time() - (DAYSECS * 3))
                : $offset->apply(time() + (DAYSECS * 3));

            $data['activity_name'] = $activity->name;
            $data[activity_setting::CLOSE_ON_DUE_DATE] = $close_on_due_date ? 'yes' : 'no';
            $perform_generator->create_activity_settings($data);
        }

        $core_generator = self::getDataGenerator();
        $si_data = [
            'activity_id' => $activity->id,
            'other_participant_id' => $core_generator->create_user()->id,
            'third_participant_username' => $core_generator->create_user()->username,
            'subject_is_participating' => true,
            'include_questions' => false,
            'status' => $subject_instance_active ? subject_instance_active::get_code() : subject_instance_pending::get_code(),
            'due_date' => $subject_instance_due_date
        ];

        $subject_instances = collection::new(range(1, $no_of_subject_instances))
            ->map_to(
                function (int $i) use ($core_generator): int {
                    return $core_generator->create_user()->id;
                }
            )
            ->map_to(
                function (int $uid) use ($si_data, $perform_generator): subject_instance {
                    $data = array_merge(['subject_user_id' => $uid], $si_data);
                    $entity = $perform_generator->create_subject_instance($data);

                    return subject_instance::load_by_entity($entity);
                }
            );

        return $subject_instances;
    }

    /**
     * @param collection $subject_instances
     * @param string $expected_si_status
     * @param string $expected_pi_status
     */
    private function assert_availability(
        collection $subject_instances,
        string $expected_si_status,
        string $expected_pi_status
    ): void {
        foreach ($this->refresh($subject_instances) as $si) {
            $this->assertEquals($expected_si_status, $si->availability_status);

            foreach ($si->participant_instances as $pi) {
                $this->assertEquals($expected_pi_status, $pi->availability_status);
            }
        }
    }

    /**
     * @param collection $subject_instances
     * @return collection
     */
    private function refresh(
        collection $subject_instances
    ): collection {
        // Unfortunately there is no refresh() method on an ORM model so need
        // to do it in a roundabout way.
        return $subject_instances
            ->map(
                function (subject_instance $si): subject_instance {
                    return subject_instance::load_by_id($si->id);
                }
            );
    }

    /**
     * @return array
     */
    private function availability_statuses(): array {
        return [
            subject_instance_availability_open::get_name(),
            subject_instance_availability_closed::get_name(),
            participant_instance_availability_open::get_name(),
            participant_instance_availability_closed::get_name()
        ];
    }
}