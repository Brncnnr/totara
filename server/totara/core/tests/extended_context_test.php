<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_core
 */

use totara_core\extended_context;

/**
 * @group message_trigger
 * @group totara_notification
 */

class totara_core_extended_context_testcase extends advanced_testcase {

    public function test_make_with_context() {
        $system_context = context_system::instance();

        $extended_context = extended_context::make_with_context($system_context);

        self::assertSame($system_context, $extended_context->get_context());
        self::assertSame($system_context->id, $extended_context->get_context_id());
        self::assertSame(extended_context::NATURAL_CONTEXT_COMPONENT, $extended_context->get_component());
        self::assertSame(extended_context::NATURAL_CONTEXT_AREA, $extended_context->get_area());
        self::assertSame(extended_context::NATURAL_CONTEXT_ITEM_ID, $extended_context->get_item_id());
    }

    public function test_make_with_id() {
        $system_context = context_system::instance();

        $extended_context = extended_context::make_with_id($system_context->id);

        self::assertSame($system_context, $extended_context->get_context());
        self::assertSame($system_context->id, $extended_context->get_context_id());
        self::assertSame(extended_context::NATURAL_CONTEXT_COMPONENT, $extended_context->get_component());
        self::assertSame(extended_context::NATURAL_CONTEXT_AREA, $extended_context->get_area());
        self::assertSame(extended_context::NATURAL_CONTEXT_ITEM_ID, $extended_context->get_item_id());
    }

    public function test_make_with_extended_context() {
        $system_context = context_system::instance();

        $extended_context = extended_context::make_with_context(
            $system_context,
            'fake_component',
            'fake_area',
            123
        );

        self::assertSame($system_context, $extended_context->get_context());
        self::assertSame($system_context->id, $extended_context->get_context_id());
        self::assertSame('fake_component', $extended_context->get_component());
        self::assertSame('fake_area', $extended_context->get_area());
        self::assertSame(123, $extended_context->get_item_id());
    }

    public function test_make_with_invalid_component() {
        $system_context = context_system::instance();

        self::expectException('coding_exception');
        self::expectExceptionMessage('Extended contexts must either provide component, area AND item ID, or none of these');

        extended_context::make_with_context(
            $system_context,
            extended_context::NATURAL_CONTEXT_COMPONENT,
            'fake_area',
            123
        );
    }

    public function test_make_with_invalid_area() {
        $system_context = context_system::instance();

        self::expectException('coding_exception');
        self::expectExceptionMessage('Extended contexts must either provide component, area AND item ID, or none of these');

        extended_context::make_with_context(
            $system_context,
            'fake_component',
            extended_context::NATURAL_CONTEXT_AREA,
            123
        );
    }

    public function test_make_with_invalid_item_id() {
        $system_context = context_system::instance();

        self::expectException('coding_exception');
        self::expectExceptionMessage('Extended contexts must either provide component, area AND item ID, or none of these');

        extended_context::make_with_context(
            $system_context,
            'fake_component',
            'fake_area',
            extended_context::NATURAL_CONTEXT_ITEM_ID
        );
    }

    public function test_get_parent_with_system_context() {
        $system_context = context_system::instance();

        $extended_context = extended_context::make_with_context($system_context);
        $parent_context = $extended_context->get_parent();

        self::assertNull($parent_context);
    }

    public function test_get_parent_with_real_context() {
        $system_context = context_system::instance();
        $category = $this->getDataGenerator()->create_category();
        $category_extended_context = extended_context::make_with_context($category->get_context());

        $category_extended_context_parent = $category_extended_context->get_parent();

        self::assertSame($system_context, $category_extended_context_parent->get_context());
        self::assertSame(extended_context::NATURAL_CONTEXT_COMPONENT, $category_extended_context_parent->get_component());
        self::assertSame(extended_context::NATURAL_CONTEXT_AREA, $category_extended_context_parent->get_area());
        self::assertSame(extended_context::NATURAL_CONTEXT_ITEM_ID, $category_extended_context_parent->get_item_id());
    }

    public function test_get_parent_with_extended_context() {
        $category = $this->getDataGenerator()->create_category();
        $extended_context = extended_context::make_with_context(
            $category->get_context(),
            'core_category',
            'fake_area',
            123
        );

        $extended_context_parent = $extended_context->get_parent();

        self::assertSame($category->get_context(), $extended_context_parent->get_context());
        self::assertSame(extended_context::NATURAL_CONTEXT_COMPONENT, $extended_context_parent->get_component());
        self::assertSame(extended_context::NATURAL_CONTEXT_AREA, $extended_context_parent->get_area());
        self::assertSame(extended_context::NATURAL_CONTEXT_ITEM_ID, $extended_context_parent->get_item_id());
    }

    public function test_is_real_context_with_real_context() {
        $system_context = context_system::instance();

        $extended_context = extended_context::make_with_context($system_context);

        self::assertTrue($extended_context->is_natural_context());
    }

    public function test_is_real_context_with_extended_context() {
        $system_context = context_system::instance();

        $extended_context = extended_context::make_with_context(
            $system_context,
            'fake_component',
            'fake_area',
            123
        );

        self::assertFalse($extended_context->is_natural_context());
    }

    public function test_get_parent_context_ids() {
        $system_context = context_system::instance();

        // Extended context is the system context.
        $extended_context = extended_context::make_system();
        // Only ancestro contexts are returned (there are none).
        self::assertEqualsCanonicalizing([], $extended_context->get_parent_context_ids());

        // Extended context is an immediate child of the system context.
        $extended_context = extended_context::make_with_context(
            $system_context,
            'child_of_system_component',
            'child_of_system_area',
            123
        );
        // Result includes system context.
        self::assertEqualsCanonicalizing([$system_context->id], $extended_context->get_parent_context_ids());

        // Extended context is a course context.
        $course = self::getDataGenerator()->create_course();
        $course_context = context_course::instance($course->id);
        $extended_context = extended_context::make_with_context(
            $course_context
        );
        // Only ancestor contexts are returned.
        self::assertEqualsCanonicalizing(
            $course_context->get_parent_context_ids(false),
            $extended_context->get_parent_context_ids()
        );

        // Extended context is the immediate child of a course context.
        $extended_context = extended_context::make_with_context(
            $course_context,
            'child_of_course_component',
            'child_of_course_area',
            123
        );
        // Ancestors and own context are returned.
        self::assertEqualsCanonicalizing(
            $course_context->get_parent_context_ids(true),
            $extended_context->get_parent_context_ids()
        );
    }
}