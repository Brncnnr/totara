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

use editor_weka\hook\find_context;
use editor_weka\webapi\resolver\query\editor;
use totara_core\hook\manager;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * This test is designed to be removed once all the deprecated fields
 * get removed from the code.
 */
class editor_weka_webapi_editor_weka_testcase extends advanced_testcase {
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
    public function test_get_editor_weka_with_deprecated_fields(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $editor = $this->resolve_graphql_query(
            $this->get_graphql_name(editor::class),
            [
                'component' => 'editor_weka',
                'area' => 'learn',
                'instance_id' => 42
            ]
        );

        $this->assertDebuggingCalled([
            "The parameter 'component' had been deprecated, please use 'usage_identifier' instead.",
            "The parameter 'instance_id' had been deprecated, please use 'usage_identifier' instead.",
            "The parameter 'area' had been deprecated, please use 'usage_identifier' instead.",
        ]);

        self::assertInstanceOf(weka_texteditor::class, $editor);
    }

    /**
     * @return void
     */
    public function test_get_editor_with_deprecated_component_which_should_not_set_the_context_id(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        manager::phpunit_replace_watchers([
            [
                'hookname' => find_context::class,
                'callback' => function (find_context $hook) use ($user_one): void {
                    $hook->set_context(context_user::instance($user_one->id));
                }
            ]
        ]);


        $this->setUser($user_one);

        /** @var weka_texteditor $editor */
        $editor = $this->resolve_graphql_query(
            $this->get_graphql_name(editor::class),
            [
                'component' => 'editor_weka',
            ]
        );

        self::assertInstanceOf(weka_texteditor::class, $editor);
        self::assertNull($editor->get_context_id());

        $this->assertDebuggingCalled(
            "The parameter 'component' had been deprecated, please use 'usage_identifier' instead."
        );
    }

    /**
     * @return void
     */
    public function test_get_editor_with_deprecated_area_which_should_not_set_the_context_id(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        manager::phpunit_replace_watchers([
            [
                'hookname' => find_context::class,
                'callback' => function (find_context $hook) use ($user_one): void {
                    $hook->set_context(context_user::instance($user_one->id));
                }
            ]
        ]);


        $this->setUser($user_one);

        /** @var weka_texteditor $editor */
        $editor = $this->resolve_graphql_query(
            $this->get_graphql_name(editor::class),
            [
                'area' => 'editor_weka',
            ]
        );

        self::assertInstanceOf(weka_texteditor::class, $editor);
        self::assertNull($editor->get_context_id());

        $this->assertDebuggingCalled(
            "The parameter 'area' had been deprecated, please use 'usage_identifier' instead."
        );
    }

    /**
     * @return void
     */
    public function test_get_editor_with_deprecated_area_component_which_should_set_the_context_id(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $context_user = context_user::instance($user_one->id);
        manager::phpunit_replace_watchers([
            [
                'hookname' => find_context::class,
                'callback' => function (find_context $hook) use ($context_user): void {
                    $hook->set_context($context_user);
                }
            ]
        ]);


        $this->setUser($user_one);

        /** @var weka_texteditor $editor */
        $editor = $this->resolve_graphql_query(
            $this->get_graphql_name(editor::class),
            [
                'component' => 'editor_weka',
                'area' => 'learn'
            ]
        );

        self::assertInstanceOf(weka_texteditor::class, $editor);
        self::assertNotNull($editor->get_context_id());
        self::assertEquals($context_user->id, $editor->get_context_id());

        $this->assertDebuggingCalled([
            "The parameter 'component' had been deprecated, please use 'usage_identifier' instead.",
            "The parameter 'area' had been deprecated, please use 'usage_identifier' instead.",
        ]);
    }

    /**
     * @return void
     */
    public function test_get_editor_with_deprecated_fields_but_supersede_by_usage_identifier(): void {
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $context_one = context_user::instance($user_one->id);
        $context_two = context_user::instance($user_two->id);

        manager::phpunit_replace_watchers([
            [
                'hookname' => find_context::class,
                'callback' => function (find_context $hook) use ($context_one, $context_two) : void {
                    $component = $hook->get_component();
                    if ('editor_weka' === $component) {
                        $hook->set_context($context_one);
                        return;
                    }

                    $hook->set_context($context_two);
                }
            ]
        ]);

        $this->setUser($user_one);

        /** @var weka_texteditor $editor */
        $editor = $this->resolve_graphql_query(
            $this->get_graphql_name(editor::class),
            [
                'component' => 'editor_weka',
                'area' => 'boom',
                'usage_identifier' => [
                    'component' => 'bob_sensei',
                    'area' => 'boom_cooker'
                ]
            ]
        );

        self::assertInstanceOf(weka_texteditor::class, $editor);
        self::assertNotEmpty($editor->get_context_id());

        // The usage identifier will supersede the deprecated fields.
        // Hence we should be expecting the context's id from user two rather than user one.

        self::assertEquals($context_two->id, $editor->get_context_id());
        $this->assertDebuggingCalledCount(2);
    }
}