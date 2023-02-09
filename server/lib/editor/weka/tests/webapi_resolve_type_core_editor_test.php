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
 * @package editor_weka
 */
defined('MOODLE_INTERNAL') || die();

use core\editor\variant_name;
use core\webapi\resolver\type\editor;
use editor_weka\variant;
use totara_webapi\phpunit\webapi_phpunit_helper;

class editor_weka_webapi_resolve_type_core_editor_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/editor/weka/lib.php");
    }

    /**
     * @return void
     */
    public function test_resolve_editor_weka_variant(): void {
        $editor = new weka_texteditor();

        /** @var variant $variant */
        $variant = $this->resolve_graphql_type(
            $this->get_graphql_name(editor::class),
            'variant',
            $editor,
            ['variant_name' => variant_name::DESCRIPTION,]
        );

        self::assertInstanceOf(variant::class, $variant);
        self::assertEquals(variant_name::DESCRIPTION, $variant->get_variant_name());

        // No context was set, so it was default to context_system.
        self::assertEquals(context_system::instance()->id, $variant->get_context_id());
    }

    /**
     * @return void
     */
    public function test_resolve_editor_weka_with_context(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $context_one = context_user::instance($user_one->id);
        $context_two = context_user::instance($user_two->id);

        $editor = new weka_texteditor();
        $editor->set_context_id($context_one->id);

        // The resolver is prefering to the context set to the editor instance.
        // Therefore the execution process's context will not be prefered to.
        $context_id = $this->resolve_graphql_type(
            $this->get_graphql_name(editor::class),
            'context_id',
            $editor,
            ['variant_name' => variant_name::STANDARD],
            $context_two
        );

        self::assertNotEquals($context_two->id, $context_id);
        self::assertNotEquals(context_system::instance()->id, $context_id);
        self::assertEquals($context_one->id, $context_id);
    }

    /**
     * @return void
     */
    public function test_resolve_editor_weka_name(): void {
        $editor = new weka_texteditor();
        $name = $this->resolve_graphql_type(
            $this->get_graphql_name(editor::class),
            'name',
            $editor
        );

        self::assertEquals('weka', $name);
    }
}