<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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

class core_webapi_scalar_url_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_parse_value(): void {
        $this->assertSame("/hello_world.php", \core\webapi\param\url::parse_value('/hello_world.php'));
        $this->assertSame('http://www.example.com', \core\webapi\param\url::parse_value('http://www.example.com'));
        $this->assertSame('http://www.example.com?admin=bolobala', \core\webapi\param\url::parse_value('http://www.example.com?admin=bolobala'));

        $this->assertEmpty(\core\webapi\param\url::parse_value(''));
        $this->assertEmpty(\core\webapi\param\url::parse_value(null));
    }

    /**
     * @return array
     */
    public static function provide_invalid_data(): array {
        return [
            ['bolobala - hello - world'],
            [false],
            [0]
        ];
    }

    /**
     * @dataProvider provide_invalid_data
     *
     * @param mixed $value
     * @return void
     */
    public function test_parse_invalid_vaue($value): void {
        $this->expectException(\invalid_parameter_exception::class);
        \core\webapi\param\url::parse_value($value);
    }
}
