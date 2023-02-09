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
use contentmarketplace_linkedin\formatter\classification;
use contentmarketplace_linkedin\model\classification as model;
use core\format;
use core_phpunit\testcase;
use contentmarketplace_linkedin\testing\generator;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_webapi_formatter_classification_testcase extends testcase {
    /**
     * @var model|null
     */
    private $model;

    /**
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $generator = generator::instance();

        $entity = $generator->create_classification(
            null,
            [
                "name" => /** @lang text */"<script>alert('something bad');</script>",
                "type" => constants::CLASSIFICATION_TYPE_LIBRARY
            ]
        );
        $this->model = new model($entity);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();
        $this->model = null;
    }

    /**
     * @return void
     */
    public function test_get_id(): void {
        $formatter = new classification($this->model, context_system::instance());
        self::assertEquals($this->model->id, $formatter->format("id"));
    }

    /**
     * @return void
     */
    public function test_get_name(): void {
        $formatter = new classification($this->model, context_system::instance());
        self::assertNotEquals(
            /** @lang text */"<script>alert('something bad');</script>",
            $formatter->format("name", format::FORMAT_PLAIN)
        );

        self::assertEquals(
            "alert('something bad');",
            $formatter->format("name", format::FORMAT_PLAIN)
        );

        self::assertEquals(
            /** @lang text */"<script>alert('something bad');</script>",
            $formatter->format("name", format::FORMAT_RAW)
        );
    }

    /**
     * @return void
     */
    public function test_get_type(): void {
        $formatter = new classification($this->model, context_system::instance());
        self::assertEquals(
            $this->model->type,
            $formatter->format("type")
        );
    }

    /**
     * @return void
     */
    public function test_get_empty_parents(): void {
        $formatter = new classification($this->model, context_system::instance());
        $parents = $formatter->format("parents");

        self::assertIsArray($parents);
        self::assertEmpty($parents);
    }


    /**
     * @return void
     */
    public function test_get_empty_children(): void {
        $formatter = new classification($this->model, context_system::instance());
        $children = $formatter->format("children");

        self::assertIsArray($children);
        self::assertEmpty($children);
    }

    /**
     * @return void
     */
    public function test_get_children(): void {
        $generator = generator::instance();
        $child_entity = $generator->create_classification();

        $generator->create_classification_relationship($this->model->id, $child_entity->id);
        $formatter = new classification($this->model, context_system::instance());

        $children = $formatter->format("children");
        self::assertIsArray($children);
        self::assertNotEmpty($children);

        self::assertCount(1, $children);
        $child = reset($children);

        self::assertInstanceOf(model::class, $child);
        self::assertEquals($child_entity->id, $child->id);
    }
}