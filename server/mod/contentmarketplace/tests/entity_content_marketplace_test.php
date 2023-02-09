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

use core\orm\query\builder;
use core_phpunit\testcase;
use mod_contentmarketplace\completion\condition;
use mod_contentmarketplace\entity\content_marketplace;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_entity_content_marketplace_testcase extends testcase {
    /**
     * @return void
     */
    public function test_insert(): void {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(content_marketplace::TABLE));

        $entity = new content_marketplace();
        $entity->course = 42;
        $entity->name = "something";
        $entity->learning_object_marketplace_component = "contentmarketplace_linkedin";
        $entity->learning_object_id = 42;
        $entity->completion_condition = condition::CONTENT_MARKETPLACE;

        $entity->save();
        self::assertEquals(1, $db->count_records(content_marketplace::TABLE));
        $entity->refresh();

        self::assertNotEmpty($entity->time_modified);
    }

    /**
     * @return void
     */
    public function test_delete(): void {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(content_marketplace::TABLE));

        $entity = new content_marketplace();
        $entity->course = 42;
        $entity->name = "something";
        $entity->learning_object_marketplace_component = "contentmarketplace_linkedin";
        $entity->learning_object_id = 42;
        $entity->completion_condition = condition::CONTENT_MARKETPLACE;

        $entity->save();
        self::assertTrue($entity->exists());
        self::assertEquals(1, $db->count_records(content_marketplace::TABLE));

        $entity->delete();
        self::assertEquals(0, $db->count_records(content_marketplace::TABLE));
        self::assertFalse($entity->exists());
    }

    /**
     * @return void
     */
    public function test_update(): void {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(content_marketplace::TABLE));

        $entity = new content_marketplace();
        $entity->course = 42;
        $entity->name = "something";
        $entity->learning_object_marketplace_component = "contentmarketplace_linkedin";
        $entity->learning_object_id = 42;
        $entity->completion_condition = condition::CONTENT_MARKETPLACE;

        $entity->save();
        self::assertTrue($db->record_exists(content_marketplace::TABLE, ["name" => "something"]));
        self::assertFalse($db->record_exists(content_marketplace::TABLE, ["name" => "ccc"]));

        $entity->name = "ccc";
        $entity->save();

        self::assertFalse($db->record_exists(content_marketplace::TABLE, ["name" => "something"]));
        self::assertTrue($db->record_exists(content_marketplace::TABLE, ["name" => "ccc"]));
    }

    /**
     * @return void
     */
    public function test_read(): void {
        $db = builder::get_db();

        $record = new stdClass();
        $record->course = 42;
        $record->name = "something";
        $record->learning_object_marketplace_component = "contentmarketplace_linkedin";
        $record->learning_object_id = 42;
        $record->completion_condition = condition::CONTENT_MARKETPLACE;
        $record->time_modified = time();

        $id = $db->insert_record(content_marketplace::TABLE, $record);
        $entity = new content_marketplace($id);

        self::assertEquals($record->course, $entity->course);
        self::assertEquals($record->name, $entity->name);
        self::assertEquals($record->learning_object_marketplace_component, $entity->learning_object_marketplace_component);
        self::assertEquals($record->learning_object_id, $entity->learning_object_id);
        self::assertEquals($record->completion_condition, $entity->completion_condition);
        self::assertEquals($record->time_modified, $entity->time_modified);
    }

    public function test_course_module_relation(): void {
        global $DB;
        if ($DB instanceof sqlsrv_native_moodle_database) {
            $this->markTestSkipped();
        }

        $generator = self::getDataGenerator();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();

        $seminar1 = $generator->create_module(
            'facetoface',
            ['course' => $course1->id]
        );

        $contentmarketplace1 = $generator->create_module(
            'contentmarketplace',
            [
                'course' => $course1->id,
                'learning_object_marketplace_component' => 'contentmarketplace_linkedin',
            ]
        );

        $seminar2 = $generator->create_module(
            'facetoface',
            ['course' => $course2->id]
        );

        $contentmarketplace2 = $generator->create_module(
            'contentmarketplace',
            [
                'course' => $course2->id,
                'learning_object_marketplace_component' => 'contentmarketplace_linkedin',
            ]
        );


        // We want to simulate both the facetoface and contentmarketplace tables having rows with ID 1 and 2,
        // in order to test that the join is happening to the correct table when getting the course_module instance.
        $DB->execute("UPDATE {facetoface} SET id = 1 WHERE id = {$seminar1->id}");
        $DB->execute("UPDATE {course_modules} SET instance = 1 WHERE instance = {$seminar1->id}");
        $seminar1->id = 1;
        $DB->execute("UPDATE {facetoface} SET id = 2 WHERE id = {$seminar2->id}");
        $DB->execute("UPDATE {course_modules} SET instance = 2 WHERE instance = {$seminar2->id}");
        $seminar2->id = 2;
        $DB->execute("UPDATE {contentmarketplace} SET id = 1 WHERE id = {$contentmarketplace1->id}");
        $DB->execute("UPDATE {course_modules} SET instance = 1 WHERE instance = {$contentmarketplace1->id}");
        $contentmarketplace1->id = 1;
        $DB->execute("UPDATE {contentmarketplace} SET id = 2 WHERE id = {$contentmarketplace2->id}");
        $DB->execute("UPDATE {course_modules} SET instance = 2 WHERE instance = {$contentmarketplace2->id}");
        $contentmarketplace2->id = 2;


        $contentmarketplace_entity = new content_marketplace($contentmarketplace1->id);
        $course_module_entity = $contentmarketplace_entity->course_module;

        $this->assertNotNull($course_module_entity);
        $this->assertEquals($contentmarketplace1->cmid, $course_module_entity->id);
        $this->assertEquals($course1->id, $course_module_entity->course);
        $this->assertNotEquals($contentmarketplace2->cmid, $course_module_entity->id);
        $this->assertNotEquals($seminar1->cmid, $course_module_entity->id);
        $this->assertNotEquals($seminar2->cmid, $course_module_entity->id);
    }

}