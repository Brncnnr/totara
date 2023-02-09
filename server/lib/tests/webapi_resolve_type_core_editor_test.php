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

use core\editor\fallback_variant;
use core\webapi\resolver\type\editor;
use totara_webapi\phpunit\webapi_phpunit_helper;

class core_webapi_resolve_type_core_editor_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @param string $class_name
     * @return string
     */
    private function get_editor_name(string $class_name): string {
        [$name] = explode('_', $class_name, 2);
        return $name;
    }

    /**
     * @return void
     */
    public function test_resolve_editor_name(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/editorlib.php");

        $formats = [
            FORMAT_PLAIN,
            FORMAT_MOODLE,
            FORMAT_HTML,
            FORMAT_MARKDOWN,
            FORMAT_JSON_EDITOR
        ];

        foreach ($formats as $format) {
            $editor = editors_get_preferred_editor($format);
            $expected_editor_name = $this->get_editor_name(get_class($editor));

            $editor_name = $this->resolve_graphql_type(
                $this->get_graphql_name(editor::class),
                'name',
                $editor
            );

            self::assertEquals($expected_editor_name, $editor_name);
        }
    }

    /**
     * This is to make sure that our API either returns the preferred
     * instance of the variant class or the fallback variant instance otherwise.
     * @return void
     */
    public function test_resolve_editor_variant(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/editorlib.php");

        $formats = [
            FORMAT_PLAIN,
            FORMAT_MOODLE,
            FORMAT_HTML,
            FORMAT_MARKDOWN,
            FORMAT_JSON_EDITOR
        ];

        foreach ($formats as $format) {
            $editor = editors_get_preferred_editor($format);
            $variant = $this->resolve_graphql_type(
                $this->get_graphql_name(editor::class),
                'variant',
                $editor
            );

            $editor_name = $this->get_editor_name(get_class($editor));
            $variant_class = "\\editor_{$editor_name}\\variant";

            if (class_exists($variant_class)) {
                self::assertInstanceOf($variant_class, $variant);
                continue;
            }

            self::assertInstanceOf(fallback_variant::class, $variant);
        }
    }
}