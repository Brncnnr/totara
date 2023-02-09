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

use editor_weka\extension\extension;

/**
 * When we drop these deprecation and remove them. Please also remove this test.
 */
class editor_weka_extension_deprecation_testcase extends advanced_testcase {
    /**
     * @var object|extension
     */
    private $extended_class;

    /**
     * @return void
     */
    protected function setUp(): void {
        $this->extended_class = new class extends extension {
            /**
             * @return string
             */
            public function get_js_path(): string {
                return  '/doto/with/me';
            }

            /**
             * @param string $component_value
             * @return string
             */
            public function run_pass_component(string $component_value): string {
                $this->component = $component_value;
                return $this->component;
            }

            /**
             * @param string $area
             * @return string
             */
            public function run_pass_area(string $area): string {
                $this->area = $area;
                return $this->area;
            }

            /**
             * @param int $context_id
             * @return int
             */
            public function run_pass_contextid(int $context_id): int {
                $this->contextid = $context_id;
                return $this->contextid;
            }
        };
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->extended_class = null;
    }

    /**
     * @return void
     */
    public function test_check_deprecation_of_field_component(): void {
        $component_value = $this->extended_class->run_pass_component('component_value');

        self::assertNotEquals('component_value', $component_value);
        self::assertEquals('editor_weka', $component_value);

        $this->assertDebuggingCalled([
            "The property 'component' had been deprecated and there is no alternative. Please update all calls.",
            "The property 'component' had been deprecated and there is no alternative. Please update all calls.",
        ]);
    }

    /**
     * @return void
     */
    public function test_check_deprecation_of_field_area(): void {
        $area_value = $this->extended_class->run_pass_area('area_value');

        self::assertNotEquals('area_value', $area_value);
        self::assertEquals('default', $area_value);

        $this->assertDebuggingCalled([
            "The property 'area' had been deprecated and there is no alternative. Please update all calls.",
            "The property 'area' had been deprecated and there is no alternative. Please update all calls.",
        ]);
    }

    /**
     * @return void
     */
    public function test_check_deprecation_of_field_contextid(): void {
        $context_id = $this->extended_class->run_pass_contextid(4200);

        self::assertNotEquals(4200, $context_id);
        self::assertEquals(context_system::instance()->id, $context_id);

        $this->assertDebuggingCalled([
            "The property 'contextid' had been deprecated and there is no alternative. Please update all calls.",
            "The property 'contextid' had been deprecated and there is no alternative. Please update all calls.",
        ]);
    }
}