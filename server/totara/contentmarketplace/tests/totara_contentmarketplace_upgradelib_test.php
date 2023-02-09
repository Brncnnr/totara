<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Angela Kuznetsova <angela.kuznetsova@totara.com>
 * @package totara_contentmarketplace
 */

use core_phpunit\testcase;
use totara_contentmarketplace\course\course_builder;
use totara_contentmarketplace\entity\course_module_source;
use totara_contentmarketplace\testing\generator;
use totara_contentmarketplace\testing\helper;
use totara_contentmarketplace\testing\mock\create_course_interactor;
use core\orm\query\builder;

/**
 * @group totara_contentmarketplace
 * Test totara_contentmarketplace upgradelib related functions
 */
class totara_contentmarketplace_upgradelib_testcase extends testcase {
    /**
     * @return void
     */
    public function test_remove_orphaned_records(): void {
        global $CFG;

        self::setAdminUser();
        self::assertEquals(0, course_module_source::repository()->count());

        // Create some ordinary contentmarketplace record
        $marketplace_generator = generator::instance();
        $learning_object = $marketplace_generator->create_learning_object('contentmarketplace_linkedin');

        $course_builder = new course_builder(
            $learning_object,
            helper::get_default_course_category_id(),
            new create_course_interactor(get_admin()->id)
        );

        $result = $course_builder->create_course();
        self::assertTrue($result->is_successful());

        $entities = course_module_source::repository()->get();
        self::assertCount(1, $entities);

        /** @var course_module_source $entity */
        $entity = $entities->first();
        self::assertEquals($learning_object->get_id(), $entity->learning_object_id);
        self::assertEquals($learning_object::get_marketplace_component(), $entity->marketplace_component);
        self::assertEquals($result->get_course_id(), $entity->course_id);

        $db = builder::get_db();

        $module = $db->get_record('course_modules', ['id' => $entity->cm_id]);
        self::assertEquals($entity->cm_id, $module->id);

        // Create orphaned record in 'totara_contentmarketplace_course_module_source' table
        try {
            // Remove a key to insert fake record into DB
            $table = new xmldb_table('totara_contentmarketplace_course_module_source');
            $key = new xmldb_key('cm_id_fk', XMLDB_KEY_FOREIGN_UNIQUE, array('cm_id'), 'course_modules', array('id'), 'cascade');
            $dbman = $db->get_manager();
            if ($dbman->key_exists($table, $key)) {
                $dbman->drop_key($table, $key);
            }
            $fake_entity = $db->insert_record('totara_contentmarketplace_course_module_source',
                ['cm_id' => $entity->id + 8, 'marketplace_component' => 'contentmarketplace_linkedin', 'learning_object_id' => $learning_object->get_id() + 3]);

            $entities = course_module_source::repository()->get();
            self::assertCount(2, $entities);

            //Check if record is correct
            $entity = $entities->last();
            self::assertEquals($learning_object->get_id() + 3, $entity->learning_object_id);

            // No modules connected to orphaned record
            $module = $db->get_record('course_modules', ['id' => $entity->cm_id]);
            self::assertFalse($module);

            require_once($CFG->dirroot . '/totara/contentmarketplace/db/upgradelib.php');
            totara_contentmarketplace_upgradelib_remove_orphaned_records();
        } catch (Exception $e) {
            throw $e;
        } finally {
            if (!$dbman->key_exists($table, $key)) {
                $dbman->add_key($table, $key);
            }
        }

        // We have only valid record now
        $entities = course_module_source::repository()->get();
        self::assertCount(1, $entities);
        $entity = $entities->first();
        self::assertEquals($learning_object->get_id(), $entity->learning_object_id);
        self::assertEquals($learning_object::get_marketplace_component(), $entity->marketplace_component);
        self::assertEquals($result->get_course_id(), $entity->course_id);
    }
}