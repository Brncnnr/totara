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
 * @author  Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_contentmarketplace
 */

use container_course\course;
use contentmarketplace_linkedin\model\learning_object;
use contentmarketplace_linkedin\testing\generator as linkedin_generator;
use core\entity\file;
use core\orm\query\builder;
use core\testing\generator as core_generator;
use mod_contentmarketplace\testing\generator as mod_generator;
use core_container\container;
use core_container\factory;
use core_phpunit\testcase;
use mod_contentmarketplace\entity\content_marketplace;
use totara_contentmarketplace\course\course_builder;
use totara_contentmarketplace\entity\course_module_source;
use totara_contentmarketplace\plugininfo\contentmarketplace;
use totara_contentmarketplace\testing\mock\create_course_interactor;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_backup_restore_testcase extends testcase {

    /**
     * Make sure that a content marketplace activity can be backed up and restored,
     * even if the original learning object record has a different internal ID (i.e. being restored on a different site)
     */
    public function test_backup_and_restore_successful(): void {
        self::setAdminUser();
        $fs = get_file_storage();
        contentmarketplace::plugin('linkedin')->enable();

        $external_id1 = 'urn:abc';
        $learning_object1 = linkedin_generator::instance()->create_learning_object($external_id1);
        $learning_object1_model = learning_object::load_by_entity($learning_object1);

        $external_id2 = 'urn:xyz';
        $learning_object2 = linkedin_generator::instance()->create_learning_object($external_id2);
        $learning_object2_model = learning_object::load_by_entity($learning_object2);

        $course1 = core_generator::instance()->create_course();
        $course1_module = mod_generator::instance()->create_content_marketplace_instance([
            'course' => $course1, 'learning_object' => $learning_object1_model,
        ]);
        $course1_module_file = $fs->create_file_from_string([
            'contextid' => context_module::instance($course1_module->cmid)->id,
            'component' => 'mod_contentmarketplace',
            'filearea' => 'intro',
            'itemid' => '0',
            'filepath' => '/',
            'filename' => 'course1',
        ], 'course1');

        $course2 = core_generator::instance()->create_course();
        $course2_module = mod_generator::instance()->create_content_marketplace_instance([
            'course' => $course2, 'learning_object' => $learning_object2_model,
        ]);
        $course2_module_file = $fs->create_file_from_string([
            'contextid' => context_module::instance($course2_module->cmid)->id,
            'component' => 'mod_contentmarketplace',
            'filearea' => 'intro',
            'itemid' => '0',
            'filepath' => '/',
            'filename' => 'course2',
        ], 'course2');

        $course1_backup = $this->backup($course1->id);

        // Delete the original learning object record in order to make sure that we aren't restoring based on the internal ID.
        $learning_object1->delete();
        $learning_object1 = linkedin_generator::instance()->create_learning_object($external_id1);

        $course1_restored = $this->restore($course1_backup);
        /** @var content_marketplace $course1_activity_restored */
        $course1_activity_restored = content_marketplace::repository()
            ->where('course', $course1_restored->id)
            ->one(true);

        $this->assertNotEquals($course1->id, $course1_activity_restored->course);
        $this->assertNotEquals($course2->id, $course1_activity_restored->course);
        $this->assertEquals('contentmarketplace_linkedin', $course1_activity_restored->learning_object_marketplace_component);
        $this->assertEquals($learning_object1->id, $course1_activity_restored->learning_object_id);

        // A course module source should have been created too.
        /** @var course_module_source $course1_activity_source */
        $course1_activity_source = course_module_source::repository()
            ->where('cm_id', $course1_activity_restored->course_module->id)
            ->one(true);
        $this->assertEquals('contentmarketplace_linkedin', $course1_activity_source->marketplace_component);
        $this->assertEquals($learning_object1->id, $course1_activity_source->learning_object_id);

        // Check that the intro file was migrated
        /** @var file[] $course1_files */
        $course1_files = file::repository()
            ->with('context')
            ->where('component', 'mod_contentmarketplace')
            ->where('filearea', 'intro')
            ->where('itemid', 0)
            ->where('filename', 'course1')
            ->order_by('id')
            ->get()
            ->all();
        $this->assertCount(2, $course1_files);
        $this->assertEquals($course1_module->cmid, $course1_files[0]->context->instanceid);
        $this->assertEquals($course1_activity_restored->course_module->id, $course1_files[1]->context->instanceid);
    }

    /**
     * Make sure an error is shown when the relevant content marketplace is disabled, and the activity module isn't restored.
     */
    public function test_restore_fails_when_marketplace_is_disabled(): void {
        self::setAdminUser();
        $learning_object = linkedin_generator::instance()->create_learning_object('urn:abc');

        $course_original = course_builder::create_with_learning_object(
            'contentmarketplace_linkedin',
            $learning_object->id,
            new create_course_interactor()
        )->create_course();

        $course_backup = $this->backup($course_original->get_course_id());

        $course_restored = $this->restore($course_backup);

        $marketplace_module = content_marketplace::repository()->where('course', $course_original->get_course_id())->one();
        $this->assertNotNull($marketplace_module);
        $this->assertFalse(content_marketplace::repository()->where('course', $course_restored->id)->exists());

        $this->assert_log_message(
            "Can not restore the content marketplace module with ID {$marketplace_module->id}, " .
            "because the LinkedIn Learning content marketplace is not enabled on this site."
        );
    }

    /**
     * Make sure an error is shown when the relevant learning object isn't in the DB, and the activity module isn't restored.
     */
    public function test_restore_fails_when_learning_object_is_not_synced(): void {
        self::setAdminUser();
        contentmarketplace::plugin('linkedin')->enable();

        $learning_object = linkedin_generator::instance()->create_learning_object('urn:abc');

        $course_original = course_builder::create_with_learning_object(
            'contentmarketplace_linkedin',
            $learning_object->id,
            new create_course_interactor()
        )->create_course();

        $course_backup = $this->backup($course_original->get_course_id());

        $learning_object->delete();

        $course_restored = $this->restore($course_backup);

        $marketplace_module = content_marketplace::repository()->where('course', $course_original->get_course_id())->one();
        $this->assertNotNull($marketplace_module);
        $this->assertFalse(content_marketplace::repository()->where('course', $course_restored->id)->exists());

        $this->assert_log_message(
            "Can not restore the content marketplace module with ID {$marketplace_module->id}, " .
            "because LinkedIn Learning is missing a course learning object with the identifier 'urn:abc'. " .
            "It may no longer exist within LinkedIn Learning, or it may not have been synced into Totara yet."
        );
    }

    /**
     * @param int $course_id
     * @return string
     */
    private function backup(int $course_id): string {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // Turn off file logging, otherwise the logs can't be deleted on Windows.
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        $backup_controller = new backup_controller(
            backup::TYPE_1COURSE,
            $course_id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_SAMESITE,
            $USER->id
        );
        $backup_id = $backup_controller->get_backupid();

        $backup_controller->execute_plan();
        $file = $backup_controller->get_results()['backup_destination'];
        $backup_controller->destroy();

        $backup_base_path = $backup_controller->get_plan()->get_basepath();
        if (!file_exists($backup_base_path . '/moodle_backup.xml')) {
            $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $backup_base_path);
        }

        return $backup_id;
    }

    /**
     * @param string $backup_id
     * @return course|container
     */
    private function restore(string $backup_id): course {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $new_course = core_generator::instance()->create_course();

        $restore_controller = new restore_controller(
            $backup_id,
            $new_course->id,
            backup::INTERACTIVE_NO,
            backup::MODE_SAMESITE,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );

        $this->assertTrue($restore_controller->execute_precheck());
        $restore_controller->execute_plan();
        $restore_controller->destroy();

        return factory::from_record($new_course);
    }

    /**
     * @param string $message
     */
    private function assert_log_message(string $message): void {
        $this->assertTrue(
            builder::table('backup_logs')->where('message', $message)->exists(),
            "The following message was not found in the backup logs:\n$message"
        );
    }

}
