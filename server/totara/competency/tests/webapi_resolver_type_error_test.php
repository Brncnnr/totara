<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_competency
 * @category test
 */

use core\format;
use core_phpunit\testcase;
use core\webapi\formatter\field\field_formatter_interface;
use core\webapi\formatter\field\string_field_formatter;
use totara_competency\helpers\error;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_error_test extends testcase {
    use webapi_phpunit_helper;

    private const TYPE = 'totara_competency_error';

    /**
     * Test data for test_invalid
     */
    public function td_invalid(): array {
        $source = error::no_selected_competencies();

        return [
            '1. wrong target class' => [new stdClass(), 'message', error::class],
            '2. unknown field' => [$source, 'unknown_field', 'unknown_field'],
        ];
    }

    /**
     * @dataProvider td_invalid
     */
    public function test_invalid(
        $source,
        string $field,
        string $error
    ): void {
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage($error);
        $this->resolve_graphql_type(self::TYPE, $field, $source);
    }

    /**
     * Test data for test_valid
     */
    public function td_valid(): array {
        $source = error::no_selected_competencies();
        $context = context_system::instance();

        return [
            '1. message (default)' => [
                $source,
                'message',
                null,
                new string_field_formatter(format::FORMAT_PLAIN, $context)
            ],

            '2. message (html)' => [
                $source,
                'message',
                format::FORMAT_HTML,
                new string_field_formatter(format::FORMAT_HTML, $context)
            ],

            '3. code' => [$source, 'code', null, null],
        ];
    }

    /**
     * @dataProvider td_valid
     */
    public function test_valid(
        error $source,
        string $field,
        ?string $format,
        ?field_formatter_interface $formatter
    ): void {
        $raw = $source->$field;
        $expected = $formatter ? $formatter->format($raw) : $raw;
        $args = $format ? ['format' => $format] : [];

        $this->assertEquals(
            $expected,
            $this->resolve_graphql_type(self::TYPE, $field, $source, $args),
            'wrong value'
        );
    }
}
