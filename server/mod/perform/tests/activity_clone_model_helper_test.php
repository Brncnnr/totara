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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

use container_perform\backup\backup_helper;
use container_perform\backup\restore_helper;
use core\orm\collection;
use core\orm\query\builder;
use mod_perform\backup\backup_activity_structure_step as backup_step;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\manual_relationship_selection;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\track;
use mod_perform\state\activity\draft;
use mod_perform\util;
use totara_core\extended_context;
use totara_notification\model\notification_preference;
use totara_notification\builder\notification_preference_builder;
use totara_notification\entity\notification_preference as notification_preference_entity;

/**
 * @group perform
 */
class mod_perform_activity_clone_model_helper_testcase extends advanced_testcase {

    /**
     * @var string[] table names which we do not back up
     */
    protected $ignored_tables = [
        'perform_type'
    ];

    /**
     * @var string[][] columns ignored by the backup check
     */
    protected $ignored_columns = [
        'perform' => [
            'course'
        ]
    ];

    /**
     * Test activity clone
     */
    public function test_clone(): void {

        $this->setAdminUser();

        $data_generator = self::getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        /** @var activity $activity */
        $activity = $perform_generator->create_full_activities()->first();
        /** @var section $section */
        $section = $activity->sections->first();

        $element = $perform_generator->create_element(
            [
                'identifier' => 'test ID'
            ]
        );
        $perform_generator->create_section_element($section, $element);

        $perform_generator->create_element([
            'context' => $activity->get_context(),
            'plugin_name' => 'short_text',
            'title' => 'test sub element title',
            'parent' => $element->id
        ]);

        [$custom_notification, $preference] = $this->create_custom_totara_notification($activity);

        $entity = activity_entity::repository()->find($activity->get_id());
        $new_activity = activity::load_by_entity($entity)->clone();

        $suffix = get_string('activity_name_restore_suffix', 'mod_perform');
        $cloned_name = util::augment_text($activity->name, activity::NAME_MAX_LENGTH, '', $suffix);
        $this->assertEquals($cloned_name, $new_activity->name);

        $this->assertEquals($activity->type, $new_activity->type);
        $this->assertEquals(draft::get_code(), $new_activity->status);
        $this->assertEquals($activity->description, $new_activity->description);
        $this->assertGreaterThanOrEqual($activity->created_at, $new_activity->created_at);
        $this->assertGreaterThanOrEqual($activity->updated_at, $new_activity->updated_at);

        $old_sections = $activity->get_sections();
        $new_sections = $new_activity->get_sections();
        $this->assertSameSize($old_sections, $new_sections);

        $old_notifications = $activity->get_notifications();
        $new_notifications = $new_activity->get_notifications();
        $this->assertSameSize($old_notifications, $new_notifications);

        $old_sections = $old_sections->all(true);
        /** @var section $old_section */
        foreach ($old_sections as $key => $old_section) {
            if (!$new_section = $new_sections->find('title', $old_section->title)) {
                $this->fail('Section was not cloned');
            }
            $this->assertEquals($old_section->title, $new_section->title);
            $this->assertEquals($new_activity->id, $new_section->activity_id);
            $this->assertGreaterThanOrEqual($old_section->created_at, $new_section->created_at);
            $this->assertGreaterThanOrEqual($old_section->updated_at, $new_section->updated_at);

            $old_section_relationships = $old_section->get_section_relationships();
            $new_section_relationships = $new_section->get_section_relationships();
            $this->assertSameSize($old_section_relationships, $new_section_relationships);

            $old_section_elements = $old_section->get_section_elements();
            $new_section_elements = $new_section->get_section_elements();
            $this->assertSameSize($old_section_elements, $new_section_elements);
            $old_element = $old_section_elements->first()->get_element();
            $new_element = $new_section_elements->first()->get_element();
            $this->assertEquals($old_element->identifier, $new_element->identifier);

            /** @var section_element $old_section_element */
            foreach ($old_section_elements as $section_element_key => $old_section_element) {
                if (!$new_section_element = $new_section_elements->find('section_id', $new_section->id)) {
                    $this->fail('Section element was not cloned');
                }
                $this->assertEquals($old_section_element->sort_order, $new_section_element->sort_order);

                $old_sub_elements = $old_section_element->element->children->pluck('title');
                $new_sub_elements = $new_section_element->element->children->pluck('title');
                $this->assertSameSize($old_sub_elements, $new_sub_elements);
                $this->assertEqualsCanonicalizing($old_sub_elements, $new_sub_elements);

                $old_section_elements->__unset($section_element_key);
            }
            $this->assertEmpty($old_section_elements);

            unset($old_sections[$key]);
        }
        $this->assertEmpty($old_sections);

        $old_tracks = $activity->get_tracks();
        $new_tracks = $new_activity->get_tracks();
        $this->assertSameSize($old_tracks, $new_tracks);

        /** @var track $new_track */
        if (!$new_track = $new_tracks->find('activity_id', $new_activity->get_id())) {
            $this->fail('Track was not cloned');
        }
        /** @var track $old_track */
        if (!$old_track = $old_tracks->find('activity_id', $activity->get_id())) {
            $this->fail('Old track was not found');
        }

        $old_track_assignments = $old_track->get_assignments();
        $new_track_assignments = $new_track->get_assignments();
        $this->assertSameSize($old_track_assignments, $new_track_assignments);

        /** @var collection|manual_relationship_selection[] $old_selection_settings */
        $old_selection_settings = manual_relationship_selection::repository()
            ->where('activity_id', $activity->id)
            ->get();

        $expected_selection_settings = [];
        foreach ($old_selection_settings as $old_selection_setting) {
            $expected_selection_settings[] = [
                'manual_relationship_id' => $old_selection_setting->manual_relationship_id,
                'selector_relationship_id' => $old_selection_setting->selector_relationship,
            ];
        }

        $new_selection_settings = manual_relationship_selection::repository()
            ->where('activity_id', $new_activity->id)
            ->get();

        $actual_selection_settings = [];
        foreach ($new_selection_settings as $new_selection_setting) {
            $actual_selection_settings[] = [
                'manual_relationship_id' => $new_selection_setting->manual_relationship_id,
                'selector_relationship_id' => $new_selection_setting->selector_relationship,
            ];
        }

        $this->assertEqualsCanonicalizing($expected_selection_settings, $actual_selection_settings);

        $extended_context = extended_context::make_with_context(
            $new_activity->get_context()
        );
        $repository = notification_preference_entity::repository();
        $cloned_notification_entity = $repository->find_by_context_and_ancestor_id(
            $extended_context,
            $preference->id
        );
        $cloned_notification = notification_preference::from_entity($cloned_notification_entity);

        $this->assertEquals($custom_notification->get_ancestor_id(), $cloned_notification->get_ancestor_id());
        $this->assertEquals($custom_notification->get_resolver_class_name(), $cloned_notification->get_resolver_class_name());
        $this->assertEquals($custom_notification->get_notification_class_name(), $cloned_notification->get_notification_class_name());
        $this->assertEquals($custom_notification->get_title(), $cloned_notification->get_title());
        $this->assertEquals($custom_notification->get_subject(), $cloned_notification->get_subject());
        $this->assertEquals($custom_notification->get_body(), $cloned_notification->get_body());
        $this->assertNotEquals(
            $custom_notification->get_extended_context()->get_context_id(),
            $cloned_notification->get_extended_context()->get_context_id()
        );
        $this->assertEquals(
            $custom_notification->get_extended_context()->get_item_id(),
            $cloned_notification->get_extended_context()->get_item_id()
        );
        $this->assertNotEquals($custom_notification->get_id(), $cloned_notification->get_id());
    }

    public function test_backup_covers_all_tables_and_fields() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');
        require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');
        require_once($CFG->dirroot . '/mod/perform/backup/moodle2/backup_perform_activity_task.class.php');

        $task = $this->getMockBuilder(backup_perform_activity_task::class)
            ->disableOriginalConstructor()
            ->getMock();

        $task->expects($this->any())
            ->method('get_setting_value')
            ->willReturn(1);

        $step = new backup_step('test', 'filename.bck', $task);

        $reflection = new ReflectionClass($step);
        $method = $reflection->getMethod('define_structure');
        $method->setAccessible(true);
        /** @var backup_nested_element $root_element */
        $root_element = $method->invoke($step);

        $actual_tables = $DB->get_tables();

        $actual_tables = array_filter($actual_tables, function ($table) {
            return $table === 'perform' || strpos($table, 'perform_') === 0 || $table === 'notification_preference';
        });

        // We do not backup the type table
        $actual_tables = array_values(array_diff($actual_tables, $this->ignored_tables));

        $msg = '';

        $tables_not_found = [];

        $expected_tables = $this->get_tables_from_backup($root_element);
        $expected_tables = array_values(array_diff($expected_tables, $this->ignored_tables));

        foreach ($expected_tables as $expected_table) {
            if (!in_array($expected_table, $actual_tables)) {
                $tables_not_found[] = $expected_table;
                continue;
            }

            // Remove from the tables as we've covered it
            unset($actual_tables[array_search($expected_table, $actual_tables)]);

            $actual_cols = array_keys($DB->get_columns($expected_table));
            // The id can be safely ignored
            unset($actual_cols[array_search('id', $actual_cols)]);

            // Filter out ignored columns
            if ($ignored_cols = $this->ignored_columns[$expected_table] ?? null) {
                $actual_cols = array_values(array_diff($actual_cols, $ignored_cols));
            }

            // Get the element from the backup definition
            $element = $this->find_backup_element_recursive($root_element, $expected_table);
            $expected_cols = array_keys($element->get_final_elements());

            $missing_cols = [];
            foreach ($expected_cols as $expected_col) {
                if (!in_array($expected_col, $actual_cols)) {
                    $missing_cols[] = $expected_col;
                    continue;
                }

                unset($actual_cols[array_search($expected_col, $actual_cols)]);
            }

            if (!empty($missing_cols)) {
                $msg .= PHP_EOL . PHP_EOL;
                $msg .= 'The backup structure for table \''.$expected_table.'\' defines the following fields which are missing in the current table:';
                $msg .= PHP_EOL . PHP_EOL;
                $msg .= implode(PHP_EOL, $missing_cols);
            }

            if (!empty($actual_cols)) {
                $msg .= PHP_EOL . PHP_EOL;
                $msg .= 'The table \''.$expected_table.'\' has the following fields which are missing in the current backup structure:';
                $msg .= PHP_EOL . PHP_EOL;
                $msg .= implode(PHP_EOL, $actual_cols);
            }
        }

        if (!empty($tables_not_found)) {
            $msg .= PHP_EOL . PHP_EOL;
            $msg .= 'The following tables are defined in the backup step but do not exist in the database:';
            $msg .= PHP_EOL . PHP_EOL;
            $msg .= implode(PHP_EOL, $tables_not_found);
        }

        if (!empty($actual_tables)) {
            $msg .= PHP_EOL . PHP_EOL;
            $msg .= 'The following tables do exist in the database but are not defined in the backup step:';
            $msg .= PHP_EOL . PHP_EOL;
            $msg .= implode(PHP_EOL, $actual_tables);
        }

        if (!empty($msg)) {
            $msg = 'Backup structure does not match the current database structure' . $msg;
            $this->fail($msg);
        }
    }

    public function clone_capabilities_data_provider(): array {
        return [
            'requires ' . backup_helper::CAPABILITY_CONTAINER => [
                backup_helper::CAPABILITY_CONTAINER, backup_controller_exception::class, 'error/backup_user_missing_capability',
            ],
            'requires ' . restore_helper::CAPABILITY_CONTAINER => [
                restore_helper::CAPABILITY_CONTAINER, restore_controller_exception::class, 'error/restore_user_missing_capability',
            ],
        ];
    }

    /**
     * @dataProvider clone_capabilities_data_provider
     * @param string $capability
     * @param string $exception_class
     * @param string $exception_message
     */
    public function test_clone_capabilities(string $capability, string $exception_class, string $exception_message): void {
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $role_id = builder::table('role')->where('shortname', 'performanceactivitycreator')->value('id');

        self::setAdminUser();
        $user = self::getDataGenerator()->create_user();
        $activity = $generator->create_activity_in_container();

        // User Can't clone because not assigned to the role
        self::setUser($user);
        $this->assertFalse($activity->can_clone);

        role_assign($role_id, $user->id, context_system::instance());

        // User can now clone
        $this->assertTrue($activity->can_clone);
        $this->assertEquals(1, activity_entity::repository()->count());
        $activity->clone();
        $this->assertEquals(2, activity_entity::repository()->count());

        unassign_capability($capability, $role_id);

        $this->assertFalse($activity->can_clone);

        // Try cloning when not allowed - we expect an exception with course/user/capability details in it.
        $this->expectException($exception_class);
        $this->expectExceptionMessage($exception_message);
        $this->expectExceptionMessageMatches("/[user_id] => {$user->id}/");
        $this->expectExceptionMessageMatches("/[courseid] => {$activity->course}/");
        $this->expectExceptionMessageMatches('/' . str_replace('container/', '', $capability) . '/');
        $activity->clone();
    }

    public function test_clone_no_unexpected_roles_assigned(): void {
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $perform_role_id = builder::table('role')->where('shortname', 'performanceactivitycreator')->value('id');
        $editingteacher_role_id = builder::table('role')->where('shortname', 'editingteacher')->value('id');

        self::setAdminUser();
        $user = self::getDataGenerator()->create_user();
        $activity = $generator->create_activity_in_container();

        role_assign($perform_role_id, $user->id, context_system::instance());

        self::setUser($user);

        $editing_teacher_assignments_before = builder::table('role_assignments')
            ->where('roleid', $editingteacher_role_id)
            ->count();

        $activity->clone();

        // No additional editing teacher roles should have been assigned.
        $this->assertEquals($editing_teacher_assignments_before, builder::table('role_assignments')
            ->where('roleid', $editingteacher_role_id)
            ->count()
        );
    }

    public function test_clone_enroll_plugins_are_cloned(): void {
        global $DB;
        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();
        self::setAdminUser();
        $activity = $generator->create_activity_in_container();

        $this->assertEquals(1, $DB->count_records('enrol', [
            'courseid' => $activity->course, 'enrol' => 'container_perform'
        ]));

        $activity_cloned = $activity->clone();
        $this->assertEquals(1, $DB->count_records('enrol', [
            'courseid' => $activity_cloned->course, 'enrol' => 'container_perform'
        ]));
    }

    private function get_tables_from_backup(backup_nested_element $element): array {
        /** @var backup_nested_element[] $children */
        $children = $element->get_children();
        $result = [];
        foreach ($children as $child) {
            if (!empty($child->get_source_table())) {
                $result[] = $child->get_source_table();
            } else if (!empty($child->get_source_sql())) {
                if ($child->get_name() === 'notification_preference') {
                    $result[] = $child->get_name();
                } else {
                    $result[] = 'perform_' . $child->get_name();
                }
            }
            $nested_result = $this->get_tables_from_backup($child);
            if (!empty($nested_result)) {
                $result = array_merge($result, $nested_result);
            }
        }

        return array_unique($result);
    }


    private function find_backup_element_recursive(backup_nested_element $element, $table_name) {
        /** @var backup_nested_element[] $children */
        $children = $element->get_children();
        foreach ($children as $child) {
            if ($child->get_source_table() === $table_name
                || $child->get_name() === str_replace('perform_', '', $table_name)
            ) {
                return $child;
            }
            if ($element = $this->find_backup_element_recursive($child, $table_name)) {
                return $element;
            }
        }

        return null;
    }

    private function create_custom_totara_notification(activity $activity): array {
        $repository = notification_preference_entity::repository();
        $preference = $repository->find_in_system_context(
            'mod_perform\totara_notification\notification\participant_instance_completion_by_manager_for_subject'
        );

        $builder = new notification_preference_builder(
            $preference->resolver_class_name,
            extended_context::make_with_context(
                $activity->get_context()
            )
        );
        $builder->set_notification_class_name($preference->notification_class_name);
        $builder->set_ancestor_id($preference->id);
        $builder->set_forced_delivery_channels(["totara_task"]);
        $custom_notification = $builder->save();
        return [$custom_notification, $preference];
    }
}
