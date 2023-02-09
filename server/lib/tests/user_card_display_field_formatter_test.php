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
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core_user\profile\value_card_display_field;
use core_user\profile\user_field_resolver;
use core_user\profile\field\metadata;
use core\formatter\user_card_display_field_formatter;
use core\format;
use core_user\profile\null_card_display_field;

class core_user_card_display_field_formatter_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_formatter_with_value_field_that_contains_html_tag(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user([
            'firstname' => /** @lang text */'<script>alert(1)</script>'
        ]);

        $this->setAdminUser();
        $metadata = new metadata(
            "firstname",
            "First name"
        );

        $resolver = user_field_resolver::from_record($user_one);
        $display_field = new value_card_display_field($resolver, $metadata);

        $context = context_system::instance();
        $formatter = new user_card_display_field_formatter($display_field, $context);

        self::assertEquals(
            "&#60;script&#62;alert(1)&#60;/script&#62;",
            $formatter->format('value', format::FORMAT_HTML)
        );

        self::assertEquals(
        /** @lang text */"<script>alert(1)</script>",
            $formatter->format('value', format::FORMAT_RAW)
        );

        // Format plain will not strip out the tags, this is intentionally, because the workflow of the system
        // treats the field as plain text, and tags will be strip out on the way in.
        // Its ideally is to decode any utf-8 entities.
        self::assertEquals(
        /** @lang text */"<script>alert(1)</script>",
            $formatter->format('value', format::FORMAT_PLAIN)
        );
    }

    /**
     * @return void
     */
    public function test_formatter_with_value_field_that_contains_utf8_char(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user([
            'lastname' => 'O\'Mountain',
            'firstname' => 'Mason'
        ]);

        $this->setAdminUser();
        $metadata = new metadata("fullname", "Bob");

        $resolver = user_field_resolver::from_record($user_one);
        $display_field = new value_card_display_field($resolver, $metadata);

        $formatter = new user_card_display_field_formatter($display_field, context_system::instance());

        // Ideally format raw should just return whatever is save in database, however our fullname
        // embedded security layer within same place therefore this is what we would get.
        // TL-28122 will resolve this - which this test would fail - please remove this comment once patch
        // is merge.
        self::assertEquals(
            "Mason O&#39;Mountain",
            $formatter->format("value", format::FORMAT_RAW)
        );

        self::assertEquals(
            "Mason O&#39;Mountain",
            $formatter->format("value", format::FORMAT_HTML)
        );

        self::assertEquals(
            "Mason O'Mountain",
            $formatter->format("value", format::FORMAT_PLAIN)
        );
    }

    /**
     * @return void
     */
    public function test_formatter_with_null_field(): void {
        $null_field = new null_card_display_field();
        $formatter = new user_card_display_field_formatter($null_field, context_system::instance());

        self::assertNull($formatter->format('value', format::FORMAT_PLAIN));
        self::assertNull($formatter->format('label'));
        self::assertNull($formatter->format('associate_url'));
        self::assertFalse($formatter->format('is_custom'));
    }
}