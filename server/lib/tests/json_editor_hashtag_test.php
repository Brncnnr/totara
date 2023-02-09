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

use core\json_editor\node\hashtag;

class core_json_editor_hashtag_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_validate_schema_with_valid_data(): void {
        $this->assertTrue(
            hashtag::validate_schema([
                'type' => hashtag::get_type(),
                'attrs' => [
                    'text' => 'this-is-text'
                ],
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_missing_keys(): void {
        $this->assertFalse(
            hashtag::validate_schema([
                'type' => hashtag::get_type(),
                'attrs' => []
            ])
        );

        $this->assertFalse(
            hashtag::validate_schema([
                'type' => hashtag::get_type()
            ])
        );
    }

    /**
     * @return void
     */
    public function test_validate_schema_with_extra_keys(): void {
        $this->assertFalse(
            hashtag::validate_schema([
                'type' => hashtag::get_type(),
                'attrs' => [
                    'text' => 'x',
                    'new_Text' => 'ddd'
                ]
            ])
        );

        $this->assertFalse(
            hashtag::validate_schema([
                'type' => hashtag::get_type(),
                'attrs' => ['text' => 'this-is-text'],
                'validate' => 'dd'
            ])
        );

        $this->assertDebuggingCalledCount(2);
    }
}