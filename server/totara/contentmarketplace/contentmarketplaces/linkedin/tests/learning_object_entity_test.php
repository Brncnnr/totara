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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\entity\classification;
use contentmarketplace_linkedin\entity\learning_object;
use contentmarketplace_linkedin\testing\generator;
use core_phpunit\testcase;
use core\orm\query\builder;

/**
 * @covers contentmarketplace_linkedin\entity\learning_object
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_learning_object_entity_testcase extends testcase {
    /**
     * @return void
     */
    public function test_create_learning_object(): void {
        $urn = 'urn:li:lyndaCourse:144562';

        $entity = new learning_object();
        $entity->urn = $urn;
        $entity->level = "BEGINNER";
        $entity->last_updated_at = time();
        $entity->published_at = time();
        $entity->time_to_complete = 496;
        $entity->title = 'This is title';
        $entity->locale_language = 'en';
        $entity->locale_country = 'US';
        $entity->asset_type = constants::ASSET_TYPE_COURSE;

        $db = builder::get_db();
        $entity->save();

        self::assertTrue($db->record_exists('marketplace_linkedin_learning_object', ['urn' => $urn]));
        self::assertEquals(
            1,
            $db->count_records('marketplace_linkedin_learning_object', ['urn' => $urn])
        );

        $fetched_record = $db->get_record('marketplace_linkedin_learning_object', ['urn' => $urn]);

        self::assertEquals($entity->urn, $fetched_record->urn);
        self::assertEquals($entity->id, $fetched_record->id);
        self::assertEquals($entity->level, $fetched_record->level);
        self::assertEquals($entity->last_updated_at, $fetched_record->last_updated_at);
        self::assertEquals($entity->published_at, $fetched_record->published_at);
        self::assertEquals($entity->time_to_complete, $fetched_record->time_to_complete);
        self::assertEquals($entity->locale_language, $fetched_record->locale_language);
        self::assertEquals($entity->locale_country, $fetched_record->locale_country);
        self::assertEquals($entity->title, $fetched_record->title);
        self::assertEquals($entity->asset_type, $fetched_record->asset_type);

        self::assertNull($fetched_record->retired_at);
        self::assertNull($fetched_record->primary_image_url);
        self::assertNull($fetched_record->web_launch_url);
        self::assertNull($fetched_record->sso_launch_url);
        self::assertNull($fetched_record->description);
        self::assertNull($fetched_record->description_include_html);
        self::assertNull($fetched_record->short_description);
    }

    /**
     * @return void
     */
    public function test_create_learning_object_with_duplicate_urn(): void {
        $urn = 'urn:li:lyndaCourse:144562';
        $db = builder::get_db();

        $first_entity = new learning_object();
        $first_entity->urn = $urn;
        $first_entity->level = "BEGINNER";
        $first_entity->last_updated_at = time();
        $first_entity->published_at = time();
        $first_entity->time_to_complete = 469;
        $first_entity->title = 'First title';
        $first_entity->locale_language = 'en';
        $first_entity->locale_country = 'US';
        $first_entity->asset_type = constants::ASSET_TYPE_COURSE;
        $first_entity->save();

        self::assertTrue($db->record_exists('marketplace_linkedin_learning_object', ['urn' => $urn]));
        self::assertEquals(1, $db->count_records('marketplace_linkedin_learning_object', ['urn' => $urn]));

        $second_entity = new learning_object();
        $second_entity->urn = $urn;
        $second_entity->level = "INTERMEDIATE";
        $second_entity->last_updated_at = time();
        $second_entity->published_at = time();
        $second_entity->time_to_complete = 496;
        $second_entity->title = 'こんにちは世界';
        $second_entity->locale_language = 'ja';
        $second_entity->locale_country = 'JP';
        $second_entity->asset_type = constants::ASSET_TYPE_COURSE;

        try {
            $second_entity->save();
            self::fail('Expecting the exception to be thrown');
        } catch (dml_write_exception $e) {
            // We have to be generic here, as different database vendors will yield different message.
            // But generally, it is about duplication.
            self::assertStringContainsString('error writing to database', strtolower($e->getMessage()));
            self::assertStringContainsString('duplicate', strtolower($e->getMessage()));
        }

        self::assertEquals(1, $db->count_records('marketplace_linkedin_learning_object', ['urn' => $urn]));
    }

    /**
     * @return void
     */
    public function test_fetch_learning_objects_with_classifications(): void {
        $generator = generator::instance();
        $learning_object = $generator->create_learning_object('urn:li:lyndaCourse:252');

        $library = $generator->create_classification(
            'urn:li:lyndaCategory:15',
            ['type' => constants::CLASSIFICATION_TYPE_LIBRARY]
        );

        $subject = $generator->create_classification(
            'urn:li:lyndaCategory:11',
            ['type' => constants::CLASSIFICATION_TYPE_SUBJECT]
        );

        $generator->create_classification_relationship($library->id, $subject->id);
        $generator->create_learning_object_classification($learning_object->id, $subject->id);
        $generator->create_learning_object_classification($learning_object->id, $library->id);

        $subjects = $learning_object->subjects;
        self::assertEquals(1, $subjects->count());

        /** @var classification $first_item */
        $first_item = $subjects->first();
        self::assertNotEquals(constants::CLASSIFICATION_TYPE_LIBRARY, $first_item->type);
        self::assertNotEquals($library->id, $first_item->id);

        self::assertEquals(constants::CLASSIFICATION_TYPE_SUBJECT, $first_item->type);
        self::assertEquals($subject->id, $first_item->id);
    }
}