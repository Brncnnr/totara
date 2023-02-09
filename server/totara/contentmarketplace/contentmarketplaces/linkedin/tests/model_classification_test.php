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
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\model\classification;
use contentmarketplace_linkedin\entity\classification as entity;
use contentmarketplace_linkedin\testing\generator;
use core\orm\query\builder;
use core_phpunit\testcase;

/**
 * Unit test for {@see classification}
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_model_classification_testcase extends testcase {
    /**
     * @var classification|null
     */
    private $model;

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $generator = generator::instance();

        $entity = $generator->create_classification(
            "urn:li:category:252",
            [
                "name" => "Classification",
                "locale_language" => "en",
                "locale_country" => "US",
                "type" => constants::CLASSIFICATION_TYPE_SUBJECT
            ]
        );

        $this->model = new classification($entity);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->model = null;
    }

    /**
     * @return void
     */
    public function test_get_id(): void {
        self::assertNotEmpty($this->model->id);

        $db = builder::get_db();
        self::assertTrue($db->record_exists(entity::TABLE, ["id" => $this->model->id]));
    }

    /**
     * @return void
     */
    public function test_get_urn(): void {
        self::assertEquals("urn:li:category:252", $this->model->urn);
    }

    /**
     * @return void
     */
    public function test_get_locale_language(): void {
        self::assertEquals("en", $this->model->locale_language);
    }

    /**
     * @return void
     */
    public function test_get_locale_country(): void {
        self::assertEquals("US", $this->model->locale_country);
    }

    /**
     * @return void
     */
    public function test_get_name(): void {
        self::assertEquals("Classification", $this->model->name);
    }

    /**
     * @return void
     */
    public function test_get_type(): void {
        self::assertEquals(constants::CLASSIFICATION_TYPE_SUBJECT, $this->model->type);
    }

    /**
     * @return void
     */
    public function test_get_parents(): void {
        self::assertEquals(0, $this->model->parents->count());

        $generator = generator::instance();
        $parent_entity = $generator->create_classification(null, ["type" => constants::CLASSIFICATION_TYPE_LIBRARY]);

        $generator->create_classification_relationship($parent_entity->id, $this->model->id);
        $this->model->refresh(true);

        $parents = $this->model->parents;
        self::assertNotEquals(0, $parents->count());
        self::assertEquals(1, $parents->count());

        $parent = $parents->first();
        self::assertInstanceOf(classification::class, $parent);
        self::assertEquals($parent_entity->id, $parent->id);
    }


    /**
     * @return void
     */
    public function test_get_children(): void {
        self::assertEquals(0, $this->model->children->count());

        $generator = generator::instance();
        $child_entity = $generator->create_classification(null, ["type" => constants::CLASSIFICATION_TYPE_SKILL]);

        $generator->create_classification_relationship($this->model->id, $child_entity->id);
        $this->model->refresh(true);

        $children = $this->model->children;
        self::assertNotEquals(0, $children->count());
        self::assertEquals(1, $children->count());

        $child = $children->first();
        self::assertInstanceOf(classification::class, $child);
        self::assertEquals($child_entity->id, $child->id);
    }
}