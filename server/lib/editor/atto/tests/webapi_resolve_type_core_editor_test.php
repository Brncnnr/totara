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
 * @package editor_atto
 */
defined('MOODLE_INTERNAL') || die();

use core\editor\fallback_variant;
use core\webapi\resolver\type\editor;
use totara_webapi\phpunit\webapi_phpunit_helper;

class editor_atto_webapi_resolver_type_core_editor_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/editor/atto/lib.php");
    }

    /**
     * @return void
     */
    public function test_resolve_editor_atto_variant(): void {
        $editor = new atto_texteditor();
        $variant = $this->resolve_graphql_type(
            $this->get_graphql_name(editor::class),
            'variant',
            $editor
        );

        self::assertInstanceOf(fallback_variant::class, $variant);
    }

    /**
     * @return void
     */
    public function test_resolve_editor_atto_name(): void {
        $editor = new atto_texteditor();
        $editor_name = $this->resolve_graphql_type(
            $this->get_graphql_name(editor::class),
            'name',
            $editor
        );

        self::assertEquals('atto', $editor_name);
    }

    /**
     * @return void
     */
    public function test_resolve_editor_atto_variant_with_invalid_variant_name(): void {
        $editor = new atto_texteditor();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid variant name 'shakira_number_one'");

        $this->resolve_graphql_type(
            $this->get_graphql_name(editor::class),
            'variant',
            $editor,
            ['variant_name' => 'shakira_number_one']
        );
    }
}