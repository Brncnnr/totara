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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core_theme
 * @category test
 */

use core\webapi\resolver\type\theme_file;
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;

global $CFG;
require_once($CFG->dirroot.'/lib/tests/fixtures/mock_theme_file.php');

/**
 * @coversDefaultClass \core\theme\file\theme_file
 *
 * @group core_theme
 */
class core_webapi_resolver_type_theme_file_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        $theme_config = theme_config::load('ventura');
        $mock_theme_file = new mock_theme_file($theme_config);

        // Test default URL.
        $default_url = $this->resolve_graphql_type(
            $this->get_graphql_name(theme_file::class),
            'default_url',
            $mock_theme_file
        );

        $this->assertEquals(
            'https://www.example.com/moodle/theme/image.php/_s/ventura/core_theme/1/mock_file',
            $default_url
        );

        // Test no default.
        $mock_theme_file->set_has_default(false);
        $default_url = $this->resolve_graphql_type(
            $this->get_graphql_name(theme_file::class),
            'default_url',
            $mock_theme_file
        );

        $this->assertNull($default_url);
    }

}
