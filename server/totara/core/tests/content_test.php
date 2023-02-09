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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_core
 */
defined('MOODLE_INTERNAL') || die();

use totara_core\content\content;

/**
 * Unit test for {@see content}
 */
class totara_core_content_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_setters_and_getters(): void {
        $content = new content(
            'This is title',
            'This is content',
            FORMAT_PLAIN,
            42,
            'totara_core',
            'core_area'
        );

        self::assertSame('This is title', $content->get_title());
        self::assertSame('This is content', $content->get_content());
        self::assertEquals(FORMAT_PLAIN, $content->get_contentformat());
        self::assertSame('totara_core', $content->get_component());
        self::assertSame('core_area', $content->get_area());

        // Test default value.
        self::assertNull($content->get_contexturl());

        // By default the context will fallback to context_system.
        $context = context_system::instance();
        self::assertEquals($context->id, $content->get_contextid());

        // No user in the session.
        self::assertEquals(0, $content->get_user_id());

        // Setter for context url
        $content->set_contexturl("/totara/core/index.php");
        self::assertNotNull($content->get_contexturl());
        self::assertStringContainsString("/totara/core/index.php", $content->get_contexturl()->out());

        // Setter for context.
        $content->set_contextid(42);

        self::assertEquals(42, $content->get_contextid());
        self::assertNotEquals($context->id, $content->get_contextid());

        // Setter for user.
        $content->set_user_id(42);
        self::assertEquals(42, $content->get_user_id());
    }

    /**
     * @return void
     */
    public function test_get_set_user_id(): void {
        global $USER;
        $this->setAdminUser();

        $content = new content(
            'This is title 1',
            'This is content 2',
            FORMAT_PLAIN,
            42,
            'totara_core',
            'core_area'
        );

        // By default content will fallback to global $USER.
        self::assertEquals($USER->id, $content->get_user_id());

        // However, if the user id is being set then it will use that value instead.
        $content->set_user_id(42);
        self::assertNotEquals($USER->id, $content->get_user_id());
        self::assertEquals(42, $content->get_user_id());
    }
}