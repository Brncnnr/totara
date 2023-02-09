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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\collection;
use core\orm\collection as orm_collection;
use core\orm\entity\entity;
use core\orm\query\builder;
use mod_perform\dates\constants;
use mod_perform\dates\date_offset;
use mod_perform\dates\resolvers\date_resolver;
use mod_perform\dates\resolvers\dynamic\dynamic_source;
use mod_perform\dates\resolvers\dynamic\job_assignment_start_date;
use mod_perform\dates\resolvers\dynamic\user_creation_date;
use mod_perform\dates\resolvers\dynamic\user_custom_field;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\track;
use mod_perform\models\activity\track_status;
use PHPUnit\Framework\MockObject\MockObject;
use totara_core\dates\date_time_setting;

/**
 * @coversDefaultClass \mod_perform\models\activity\track
 *
 * @group perform
 */
class mod_perform_track_model_testcase extends advanced_testcase {
    /**
     * @covers ::create
     * @covers ::load_by_activity
     */
    public function test_create_tracks(): void {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container(['create_track' => true]);

        // There is already a "default" track, created when the activity is
        // created.
        $existing_tracks = track::load_by_activity($activity);
        $this->assertEquals(1, $existing_tracks->count(), 'wrong existing track count');
        $default_track = $existing_tracks->first();

        $desc_base = "my test track";
        $tracks = collection::new(range(0, 1))
            ->map_to(
                function (int $i) use ($activity, $desc_base): track {
                    return track::create($activity, "$desc_base #$i");
                }
            )->all();

        $tracks_by_id = [];
        foreach ($tracks as $track) {
            $track_id = $track->id;

            $this->assertGreaterThan(0, $track_id, 'transient track');
            $this->assertStringContainsString($desc_base, $track->description, 'wrong desc');
            $this->assertEquals($activity->get_id(), $track->activity_id, 'wrong parent');
            $this->assertEquals(track_status::ACTIVE, $track->status, 'wrong track status');
            $this->assertEmpty($track->assignments->all(), 'wrong track assignments');

            $tracks_by_id[$track_id] = $track;
        }

        // Confirm the repository really has the new tracks.
        $retrieved_tracks = track::load_by_activity($activity);
        $this->assertEquals(
            count($tracks) + 1,
            $retrieved_tracks->count(),
            'wrong track retrieval count'
        );

        foreach ($retrieved_tracks as $track) {
            $track_id = $track->id;
            if ($track_id === $default_track->id) {
                // Ignore the default track.
                continue;
            }

            $expected = $tracks_by_id[$track_id] ?? null;
            $this->assertNotNull($expected, "unknown retrieved track id '$track_id'");

            $expected_values = [
                $expected->activity_id,
                $expected->status,
                []
            ];

            $actual_values = [
                $track->activity_id,
                $track->status,
                $track->assignments->all()
            ];

            $this->assertEquals($expected_values, $actual_values, 'wrong track values');
        }
    }

    /**
     * @covers ::create
     * @covers ::activate
     * @covers ::pause
     */
    public function test_track_transitions(): void {
        $this->setAdminUser();

        $active = track_status::ACTIVE;
        $paused = track_status::PAUSED;

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container();
        $track = track::create($activity);

        // State active; invoke activate - ignored.
        $to_status = $track->activate()->status;
        $this->assertEquals($active, $to_status, 'wrong status');

        // Transition active -> paused
        $from_status = $track->status;
        $to_status = $track->pause()->status;
        $this->assertEquals($active, $from_status, 'wrong status');
        $this->assertEquals($paused, $to_status, 'wrong status');

        // State paused; invoke paused - ignored
        $from_status = $track->status;
        $to_status = $track->pause()->status;
        $this->assertEquals($paused, $to_status, 'wrong status');

        // Transition paused to active.
        $from_status = $track->status;
        $to_status = $track->activate()->status;
        $this->assertEquals($paused, $from_status, 'wrong status');
        $this->assertEquals($active, $to_status, 'wrong status');
    }

    /**
     * @dataProvider invalid_open_dynamic_schedule_from_to_permutations_provider
     * @param int $count_from
     * @param int $count_to
     * @param string $direction
     * @param dynamic_source $dynamic_source
     * @param string $expected_exception_message
     * @throws coding_exception
     */
    public function test_invalid_open_dynamic_schedule_from_to_permutations(
        int $count_from,
        int $count_to,
        string $direction,
        dynamic_source $dynamic_source,
        string $expected_exception_message
    ): void {
        $track = track::load_by_entity($this->mock_existing_entity(track_entity::class));

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage($expected_exception_message);

        $from = new date_offset(
            $count_from,
            date_offset::UNIT_DAY,
            $direction
        );
        $to = new date_offset(
            $count_to,
            date_offset::UNIT_DAY,
            $direction
        );
        $track->set_schedule_closed_dynamic(
            $from,
            $to,
            $dynamic_source
        );
    }

    /**
     * Mock an entity that will be flagged as "existing".
     *
     * Useful for when you don't actually need to hit the database, but something requires an
     * "existing" entity, such as testing a model method that doesn't directly need the database.
     *
     * @param string $class
     * @return entity | MockObject
     */
    protected function mock_existing_entity(string $class): entity {
        $mock = $this->getMockBuilder($class)->onlyMethods(['exists'])->getMock();
        $mock->method('exists')->willReturn(true);

        return $mock;
    }

    public function invalid_open_dynamic_schedule_from_to_permutations_provider(): array {
        $available_dynamic_source = (new user_creation_date())->get_options()->first();
        $unavailable_dynamic_source = new dynamic_source(null, 'default', 'Birthday');

        return [
            'From after to' => [
                100,
                0,
                date_offset::DIRECTION_AFTER,
                $available_dynamic_source,
                '"from" must not be after "to"'
            ],
            'To before from' => [
                0,
                100,
                date_offset::DIRECTION_BEFORE,
                $available_dynamic_source,
                'from" must not be after "to"'
            ],
            'Unavailable date resolver option' => [
                100,
                0,
                date_offset::DIRECTION_BEFORE,
                $unavailable_dynamic_source,
                'Dynamic source must be available'
            ],
        ];
    }

    public function set_schedule_methods_data_provider(): array {
        $dynamic_source = dynamic_source::all_available()->last();
        return [
            'set_schedule_open_fixed' => [
                'set_schedule_open_fixed', [new date_time_setting(111)]
            ],
            'set_schedule_open_fixed - timezone only change' => [
                'set_schedule_open_fixed',
                [new date_time_setting(111, 'UTC')],
                [new date_time_setting(111, 'Pacific/Auckland')],
            ],
            'set_schedule_closed_fixed' => [
                'set_schedule_closed_fixed', [new date_time_setting(111), new date_time_setting(222)]
            ],
            'set_schedule_closed_dynamic' => [
                'set_schedule_closed_dynamic', [
                    new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER),
                    new date_offset(222, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER),
                    $dynamic_source
                ]
            ],
            'set_schedule_open_dynamic' => [
                'set_schedule_open_dynamic', [
                    new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                    $dynamic_source
                ]
            ],
        ];
    }

    /**
     * @dataProvider set_schedule_methods_data_provider
     * @param string $method_name
     * @param array $params
     * @param array|null $before_params
     * @throws coding_exception
     */
    public function test_schedule_sync_is_not_flagged_for_draft(
        string $method_name,
        array $params,
        array $before_params = null
    ): void {
        $this->setAdminUser();

        $track = $this->create_activity_track('DRAFT');

        /** @var track_entity $track_entity */
        $track_entity = track_entity::repository()->find($track->get_id());

        if ($before_params !== null) {
            $track->$method_name(...$before_params);
            $track->update();
            $track_entity->refresh();
            $track_entity->schedule_needs_sync = false;
            $track_entity->save();
            $track->refresh();
        }

        $this->assertEquals(0, $track_entity->schedule_needs_sync);

        // Update method should set flag.
        $track->$method_name(...$params);
        $track->update();
        $track_entity->refresh();
        $this->assertEquals(0, $track_entity->schedule_needs_sync);
    }

    /**
     * @dataProvider set_schedule_methods_data_provider
     * @param string $method_name
     * @param array $params
     * @param array|null $before_params
     * @throws coding_exception
     */
    public function test_schedule_sync_is_flagged_for_active_activity(
        string $method_name,
        array $params,
        array $before_params = null
    ): void {
        $this->setAdminUser();

        $track = $this->create_activity_track('ACTIVE');

        /** @var track_entity $track_entity */
        $track_entity = track_entity::repository()->find($track->get_id());

        if ($before_params !== null) {
            $track->$method_name(...$before_params);
            $track->update();
            $track_entity->refresh();
            $track_entity->schedule_needs_sync = false;
            $track_entity->save();
            $track->refresh();
        }

        $this->assertEquals(0, $track_entity->schedule_needs_sync);

        // Update method should set flag.
        $track->$method_name(...$params);
        $track->update();
        $track_entity->refresh();
        $this->assertEquals(1, $track_entity->schedule_needs_sync);
    }

    /**
     * @dataProvider set_schedule_methods_data_provider
     * @param string $method_name
     * @param array $params
     */
    public function test_schedule_sync_is_not_flagged_for_update_without_actual_changes(string $method_name, array $params): void {
        $this->setAdminUser();

        $track = $this->create_activity_track('ACTIVE');

        /** @var track_entity $track_entity */
        $track_entity = track_entity::repository()->find($track->get_id());

        // Update method sets flag because our test params are different from default data.
        $track->$method_name(...$params);
        $track->update();
        $track_entity->refresh();
        $this->assertEquals(1, $track_entity->schedule_needs_sync);

        // Reset flag.
        $track_entity->schedule_needs_sync = 0;
        $track_entity->update();

        // Update method doesn't set flag because the params are the same as the ones already saved.
        $track->$method_name(...$params);
        $track->update();
        $track_entity->refresh();
        $this->assertEquals(0, $track_entity->schedule_needs_sync);
    }

    public function schedule_changes_data_provider(): array {
        // NOTE: data providers MUST NOT modify database!
        return [
            ['set_schedule_open_fixed'],
            ['set_schedule_open_fixed - timezone change'],
            ['set_schedule_closed_fixed - start change'],
            ['set_schedule_closed_fixed - end change'],
            ['set_schedule_closed_dynamic - from offset changes'],
            ['set_schedule_closed_dynamic - to offset changes'],
            ['set_schedule_closed_dynamic - unit changes'],
            ['set_schedule_closed_dynamic - direction changes'],
            ['set_schedule_open_dynamic - offset changes'],
            ['set_schedule_open_dynamic - unit changes'],
            ['set_schedule_open_dynamic - direction changes'],
            ['open dynamic - dynamic source resolver change'],
            ['open dynamic - dynamic source option change'],
        ];
    }

    protected function schedule_changes_data_generator(string $type): array {
        $get_custom_field_source = function (string $option_key) {
            builder::get_db()->insert_record(
                'user_info_field',
                (object)['shortname' => $option_key, 'name' => 'time-custom', 'categoryid' => 1, 'datatype' => 'datetime']
            );

            return (new user_custom_field())->get_options()->find(
                function (dynamic_source $source) use ($option_key) {
                    return $source->get_option_key() === $option_key;
                }
            );
        };

        $user_creation_date_source = function() {
            return (new user_creation_date())->get_options()->first();
        };

        switch ($type) {
            case 'set_schedule_open_fixed':
                return [
                    'set_schedule_open_fixed',
                    [new date_time_setting(111)],
                    [new date_time_setting(222)],
                ];
            case 'set_schedule_open_fixed - timezone change':
                return [
                    'set_schedule_open_fixed',
                    [new date_time_setting(111, 'UTC')],
                    [new date_time_setting(111, 'Pacific/Auckland')],
                ];
            case 'set_schedule_closed_fixed - start change':
                return [
                    'set_schedule_closed_fixed',
                    [new date_time_setting(111), new date_time_setting(999)],
                    [new date_time_setting(222), new date_time_setting(999)],
                ];
            case 'set_schedule_closed_fixed - end change':
                return [
                    'set_schedule_closed_fixed',
                    [new date_time_setting(111), new date_time_setting(999)],
                    [new date_time_setting(111), new date_time_setting(888)],
                ];
            case 'set_schedule_closed_dynamic - from offset changes':
                return [
                    'set_schedule_closed_dynamic',
                    [
                        new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER),
                        new date_offset(999, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER),
                        $user_creation_date_source()
                    ],
                    [
                        new date_offset(222, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER),
                        new date_offset(999, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER),
                        $user_creation_date_source()
                    ]
                ];
            case 'set_schedule_closed_dynamic - to offset changes':
                return [
                    'set_schedule_closed_dynamic',
                    [
                        new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER),
                        new date_offset(999, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER),
                        $user_creation_date_source()
                    ],
                    [
                        new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER),
                        new date_offset(888, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER),
                        $user_creation_date_source()
                    ]
                ];
            case 'set_schedule_closed_dynamic - unit changes':
                return [
                    'set_schedule_closed_dynamic',
                    [
                        new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER),
                        new date_offset(999, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER),
                        $user_creation_date_source()
                    ],
                    [
                        new date_offset(111, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
                        new date_offset(999, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER),
                        $user_creation_date_source()
                    ]
                ];
            case 'set_schedule_closed_dynamic - direction changes':
                return [
                    'set_schedule_closed_dynamic',
                    [
                        new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER),
                        new date_offset(999, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER),
                        $user_creation_date_source()
                    ],
                    [
                        new date_offset(999, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                        new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                        $user_creation_date_source()
                    ]
                ];
            case 'set_schedule_open_dynamic - offset changes':
                return [
                    'set_schedule_open_dynamic',
                    [
                        new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                        $user_creation_date_source()
                    ],
                    [
                        new date_offset(222, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                        $user_creation_date_source()
                    ]
                ];
            case 'set_schedule_open_dynamic - unit changes':
                return [
                    'set_schedule_open_dynamic',
                    [
                        new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                        $user_creation_date_source()
                    ],
                    [
                        new date_offset(111, date_offset::UNIT_DAY, date_offset::DIRECTION_BEFORE),
                        $user_creation_date_source()
                    ]
                ];
            case 'set_schedule_open_dynamic - direction changes':
                return [
                    'set_schedule_open_dynamic',
                    [
                        new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                        $user_creation_date_source()
                    ],
                    [
                        new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_AFTER),
                        $user_creation_date_source()
                    ]
                ];
            case 'open dynamic - dynamic source resolver change':
                return [
                    'set_schedule_open_dynamic',
                    [
                        new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                        $user_creation_date_source()
                    ],
                    [
                        new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                        $get_custom_field_source('time-custom')
                    ]
                ];
            case 'open dynamic - dynamic source option change':
                return [
                    'set_schedule_open_dynamic',
                    [
                        new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                        $get_custom_field_source('time-custom1')
                    ],
                    [
                        new date_offset(111, date_offset::UNIT_WEEK, date_offset::DIRECTION_BEFORE),
                        $get_custom_field_source('time-custom2')
                    ]
                ];
            default:
                throw new coding_exception('Invalid type');
        }
    }

    /**
     * @dataProvider schedule_changes_data_provider
     * @param string
     */
    public function test_schedule_sync_is_flagged_for_update_with_actual_changes(string $type) {
        $this->setAdminUser();

        list($method_name, $setup_params, $changed_params) = self::schedule_changes_data_generator($type);

        $track = $this->create_activity_track('ACTIVE');

        /** @var track_entity $track_entity */
        $track_entity = track_entity::repository()->find($track->get_id());

        // Update method sets flag because our test params are different from default data.
        $track->$method_name(...$setup_params);
        $track->update();
        $track_entity->refresh();
        $this->assertEquals(1, $track_entity->schedule_needs_sync);

        // Reset flag.
        $track_entity->schedule_needs_sync = 0;
        $track_entity->update();

        // Update method sets flag because the params are different from the ones already saved.
        $track->$method_name(...$changed_params);
        $track->update();
        $track_entity->refresh();
        $this->assertEquals(1, $track_entity->schedule_needs_sync);
    }


    /**
     * Create an activity with the given status and return the first created track.
     *
     * @param string $activity_status
     * @return track
     */
    private function create_activity_track(string $activity_status): track {
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        /** @var activity $activity */
        $activity = $perform_generator->create_activity_in_container([
            'create_track' => true,
            'activity_status' => $activity_status
        ]);

        return track::load_by_activity($activity)->first();
    }

    public function test_update_performs_validation(): void {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container();
        $track = track::create($activity);

        $track->set_schedule_open_fixed(new date_time_setting(111));
        $track->set_due_date_fixed(new date_time_setting(222));

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Cannot set due date to fixed except when schedule is not open and fixed');

        $track->update();
    }

    public function test_validate_fails_due_to_invalid_due_date_type(): void {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container();
        $track = track::create($activity);

        $track->set_schedule_open_fixed(new date_time_setting(111));
        $track->set_due_date_fixed(new date_time_setting(222));

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Cannot set due date to fixed except when schedule is not open and fixed');

        $track->validate();
    }

    public function test_validate_fails_due_to_invalid_fixed_due_date(): void {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container();
        $track = track::create($activity);

        $track->set_schedule_closed_fixed(new date_time_setting(222), new date_time_setting(444));
        $track->set_due_date_fixed(new date_time_setting(333));

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Cannot set fixed due date earlier than the schedule end date');

        $track->validate();
    }

    public function test_get_resolver_option_when_set(): void {
        /** @var track_entity | MockObject $entity */
        $entity = $this->mock_existing_entity(track_entity::class);

        /** @var dynamic_source $option */
        $option = (new user_creation_date())->get_options()->first();

        $entity->schedule_dynamic_source = $option;

        $track = new track($entity);

        $selected_option = $track->schedule_dynamic_source;

        self::assertInstanceOf(dynamic_source::class, $selected_option);

        self::assertEqualsCanonicalizing([
            'resolver_class_name' => user_creation_date::class,
            'option_key' => user_creation_date::DEFAULT_KEY,
            'display_name' => 'User creation date',
            'is_available' => true,
            'custom_setting_component' => null,
            'custom_data' => null,
            'resolver_base' => constants::DATE_RESOLVER_USER_BASED,
        ], $selected_option->jsonSerialize());
    }

    public function test_get_resolver_option_when_key_is_no_longer_available(): void {
        /** @var track_entity | MockObject $entity */
        $entity = $this->mock_existing_entity(track_entity::class);

        /** @var dynamic_source $option */
        $option = (new user_creation_date())->get_options()->first();

        $data = $option->jsonSerialize();
        $data['option_key'] = 'non-existing';

        $entity->schedule_dynamic_source = $data;

        $track = new track($entity);

        $selected_option = $track->schedule_dynamic_source;

        self::assertInstanceOf(dynamic_source::class, $selected_option);

        self::assertEquals([
            'resolver_class_name' => user_creation_date::class,
            'option_key' => 'non-existing',
            'display_name' => 'User creation date',
            'is_available' => false,
            'custom_setting_component' => null,
            'custom_data' => null,
            'resolver_base' => constants::DATE_RESOLVER_USER_BASED,
        ], $selected_option->jsonSerialize());
    }

    public function test_get_resolver_option_when_not_set(): void {
        /** @var track_entity | MockObject $entity */
        $entity = $this->mock_existing_entity(track_entity::class);

        $entity->schedule_dynamic_source = null;

        $track = new track($entity);

        self::assertNull($track->schedule_dynamic_source);
    }

    public function test_get_date_resolver(): void {
        $this->setAdminUser();

        /** @var track $track */
        $track = $this->create_activity_track('ACTIVE');
        /** @var dynamic_source $dynamic_source */
        $dynamic_source = (new job_assignment_start_date())->get_options()->first();
        /** @var date_offset $offset */
        $offset = new date_offset(1, date_offset::UNIT_DAY, date_offset::DIRECTION_AFTER);

        /** @var track_entity $track_entity */
        $track_entity = track_entity::repository()->find($track->get_id());

        // Update method sets flag because our test params are different from default data.
        $track->set_schedule_open_dynamic($offset, $dynamic_source);

        // Just checking that get_date_resolver can handle collection with and without subject_user_id and job_assignment_id values
        /** @var date_resolver $resolver */
        $resolver = $track->get_date_resolver(orm_collection::new([['id' => 1]]));
        $this->assertNotNull($resolver);

        $resolver = $track->get_date_resolver(orm_collection::new([['id' => 1, 'subject_user_id' => 2]]));
        $this->assertNotNull($resolver);

        $resolver = $track->get_date_resolver(orm_collection::new([['id' => 1, 'subject_user_id' => 2, 'job_assignment_id' => 3]]));
        $this->assertNotNull($resolver);

        $resolver = $track->get_date_resolver(orm_collection::new(
            [
                ['id' => 1],
                ['id' => 1, 'subject_user_id' => 2],
                ['id' => 1, 'subject_user_id' => 2, 'job_assignment_id' => 3],
            ]
        ));
        $this->assertNotNull($resolver);
    }
}
