<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_contentmarketplace
 */

use container_course\course;
use container_course\module\course_module;
use core\orm\query\builder;
use core\entity\course_module as course_module_entity;
use core_phpunit\testcase;
use mod_contentmarketplace\completion\condition;
use mod_contentmarketplace\entity\content_marketplace;
use mod_contentmarketplace\exception\learning_object_not_found;
use mod_contentmarketplace\model\content_marketplace as model;
use mod_contentmarketplace\output\content_marketplace_logo;
use totara_contentmarketplace\testing\generator as totara_content_marketplace_generator;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_lib_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/mod/contentmarketplace/lib.php");
    }

    /**
     * @return void
     */
    public function test_add_instance_via_course(): void {
        global $CFG;
        $generator = self::getDataGenerator();

        $user = $generator->create_user();
        $course_record = $generator->create_course();

        // Enrol user to the course as editing teacher.
        $course = course::from_record($course_record);
        $generator->enrol_user(
            $user->id,
            $course->id,
            $CFG->creatornewroleid
        );

        $marketplace_generator = totara_content_marketplace_generator::instance();

        $learning_object = $marketplace_generator->create_learning_object('contentmarketplace_linkedin');

        $module_info = new stdClass();
        $module_info->course = $course->id;
        $module_info->modulename = 'contentmarketplace';
        $module_info->section = 0;
        $module_info->learning_object_id = $learning_object->get_id();
        $module_info->learning_object_marketplace_component = $learning_object::get_marketplace_component();
        $module_info->visible = 1;

        self::setUser($user);

        $course_module = $course->add_module($module_info);
        $db = builder::get_db();

        self::assertTrue($db->record_exists('course_modules', ['id' => $course_module->get_id()]));
        self::assertTrue($db->record_exists(content_marketplace::TABLE, ['id' => $course_module->get_instance()]));

        self::assertTrue(
            $db->record_exists(
                content_marketplace::TABLE,
                [
                    'id' => $course_module->get_instance(),
                    'name' => $learning_object->get_name()
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_add_instance_with_non_existing_learning_object_via_course(): void {
        global $CFG;

        $generator = self::getDataGenerator();

        $user = $generator->create_user();
        $course_record = $generator->create_course();

        $course = course::from_record($course_record);
        $generator->enrol_user(
            $user->id,
            $course->id,
            $CFG->creatornewroleid
        );

        $module_info = new stdClass();
        $module_info->course = $course->id;
        $module_info->modulename = 'contentmarketplace';
        $module_info->section = 0;
        $module_info->learning_object_id = 42;
        $module_info->learning_object_marketplace_component = 'contentmarketplace_linkedin';
        $module_info->visible = 1;

        // Now add the module.
        self::setUser($user);

        try {
            $course->add_module($module_info);
            self::fail("Expect the adding course module process would yield error");
        } catch (coding_exception $e) {
            self::assertStringContainsString(
                "Incorrect function 'contentmarketplace_add_instance'",
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_add_instance_directly(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $marketplace_generator = totara_content_marketplace_generator::instance();
        $learning_object = $marketplace_generator->create_learning_object('contentmarketplace_linkedin');

        $module_info = new stdClass();
        $module_info->course = $course_record->id;
        $module_info->modulename = 'contentmarketplace';
        $module_info->section = 0;
        $module_info->learning_object_id = $learning_object->get_id();
        $module_info->learning_object_marketplace_component = $learning_object::get_marketplace_component();

        $id = contentmarketplace_add_instance($module_info);
        $db = builder::get_db();

        self::assertTrue($db->record_exists(content_marketplace::TABLE, ['id' => $id]));
        self::assertTrue(
            $db->record_exists(
                content_marketplace::TABLE,
                [
                    'id' => $id,
                    'name' => $learning_object->get_name()
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_add_instance_with_non_existing_learning_object_directly(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $module_info = new stdClass();
        $module_info->course = $course_record->id;
        $module_info->modulename = 'contentmarketplace';
        $module_info->section = 0;
        $module_info->learning_object_id = 42;
        $module_info->learning_object_marketplace_component = 'contentmarketplace_linkedin';

        try {
            contentmarketplace_add_instance($module_info);
            self::fail("Expect the add instance would yield errors");
        } catch (learning_object_not_found $e) {
            self::assertEquals(
                get_string('error:cannot_find_learning_object', 'mod_contentmarketplace', 'contentmarketplace_linkedin'),
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_update_instance_when_completion_is_disabled(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/completionlib.php");

        self::setAdminUser();
        $generator = self::getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => COMPLETION_ENABLED]);

        $module_record = $generator->create_module('contentmarketplace', ['course' => $course->id]);
        $module = course_module::from_id($module_record->cmid);

        $db = builder::get_db();
        self::assertNull(
            $db->get_field(content_marketplace::TABLE, 'completion_condition', ['id' => $module->get_instance()])
        );

        self::assertEquals(
            COMPLETION_TRACKING_NONE,
            $db->get_field(course_module_entity::TABLE, 'completion', ['id' => $module->get_id()])
        );

        // Start updating module.
        $update_data = new stdClass();
        $update_data->modulename = "contentmarketplace";
        $update_data->completion = COMPLETION_TRACKING_AUTOMATIC;
        $update_data->completion_condition = condition::CONTENT_MARKETPLACE;
        $update_data->completionunlocked = 1;
        $update_data->visible = 1;
        file_prepare_draft_area($draftid_editor, null, null, null, null);
        $update_data->introeditor = [
            'text' => 'This is a module',
            'format' => FORMAT_HTML,
            'itemid' => $draftid_editor
        ];

        $module->update($update_data);
        self::assertEquals(
            COMPLETION_TRACKING_AUTOMATIC,
            $db->get_field(course_module_entity::TABLE, 'completion', ['id' => $module->get_id()])
        );

        self::assertEquals(
            condition::CONTENT_MARKETPLACE,
            $db->get_field(content_marketplace::TABLE, 'completion_condition', ['id' => $module->get_instance()])
        );

        $new_update_data = new stdClass();
        $new_update_data->modulename = "contentmarketplace";
        $new_update_data->completion = COMPLETION_TRACKING_NONE;
        $new_update_data->completion_condition = condition::CONTENT_MARKETPLACE;
        $new_update_data->completionunlocked = 1;
        $new_update_data->visible = 1;
        file_prepare_draft_area($draftid_editor, null, null, null, null);
        $new_update_data->introeditor = [
            'text' => 'This is a module',
            'format' => FORMAT_HTML,
            'itemid' => $draftid_editor
        ];

        $module->update($new_update_data);
        self::assertNull(
            $db->get_field(content_marketplace::TABLE, 'completion_condition', ['id' => $module->get_instance()])
        );

        self::assertEquals(
            COMPLETION_TRACKING_NONE,
            $db->get_field(course_module_entity::TABLE, 'completion', ['id' => $module->get_id()])
        );
    }

    /**
     * @return void
     */
    public function test_render_cm_info_view(): void {
        global $OUTPUT;

        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $content_marketplace = $generator->create_module("contentmarketplace", ["course" => $course->id]);
        [$course, $cm] = get_course_and_cm_from_cmid($content_marketplace->cmid);
        $cm_info = cm_info::create($cm);

        // We have to use ReflectionClass to avoid the magic function being invoked.
        $reflection_class = new ReflectionClass($cm_info);
        $property = $reflection_class->getProperty("afterlink");

        $property->setAccessible(true);
        self::assertNull($property->getValue($cm_info));

        // Now start invoke the magic function to get the after link text.
        self::assertNotEmpty($cm_info->afterlink);

        // Assert the rendered content.
        $model = model::from_course_module_id($cm->id);
        $logo = content_marketplace_logo::create_from_model($model);
        self::assertEquals(
            $OUTPUT->render($logo),
            $cm_info->afterlink
        );
    }

    /**
     * This test is to make sure that if we ever change the support flag,
     * it should be aware of consequences.
     *
     * @return void
     */
    public function test_check_supports_flag(): void {
        self::assertFalse(contentmarketplace_supports(FEATURE_NO_VIEW_LINK));
        self::assertTrue(contentmarketplace_supports(FEATURE_MOD_INTRO));

        self::assertTrue(contentmarketplace_supports(FEATURE_BACKUP_MOODLE2));
        self::assertTrue(contentmarketplace_supports(FEATURE_COMPLETION_HAS_RULES));

        self::assertNull(contentmarketplace_supports(FEATURE_COMMENT));
        self::assertNull(contentmarketplace_supports(FEATURE_ADVANCED_GRADING));
        self::assertNull(contentmarketplace_supports(FEATURE_GRADE_HAS_GRADE));
        self::assertNull(contentmarketplace_supports(FEATURE_GRADE_OUTCOMES));
        self::assertNull(contentmarketplace_supports(FEATURE_CONTROLS_GRADE_VISIBILITY));
    }
}
