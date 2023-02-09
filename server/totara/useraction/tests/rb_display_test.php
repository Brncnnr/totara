<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

use core_phpunit\testcase;
use totara_useraction\fixtures\mock_action;
use totara_useraction\local\testing\mock_actions;
use totara_useraction\rb\display\scheduled_rule_action;
use totara_useraction\rb\display\scheduled_rule_name;

/**
 * Tests the display classes for the reports.
 *
 * @group totara_useraction
 */
class totara_useraction_rb_display_testcase extends testcase {
    use mock_actions;

    /**
     * Assert the scheduled_rule_action class can present action names.
     *
     * @return void
     */
    public function test_scheduled_rule_action(): void {
        $row = new stdClass();
        $report = $this->createMock(reportbuilder::class);
        $column = $this->createMock(rb_column::class);

        $text = scheduled_rule_action::display(mock_action::class, 'text', $row, $column, $report);
        self::assertSame('mock action', $text);

        $text = scheduled_rule_action::display('no real class', 'text', $row, $column, $report);
        self::assertSame('Unknown action', $text);

        $text = scheduled_rule_action::display('', 'text', $row, $column, $report);
        self::assertSame('Unknown action', $text);
    }

    /**
     * Assert the scheduled_rule_name display function shows in both plain & html versions.
     *
     * @return void
     */
    public function test_scheduled_rule_name(): void {
        $row = new stdClass();
        $report = $this->createMock(reportbuilder::class);

        $link = new moodle_url('/totara/useraction/scheduled_actions.php');
        $expected_html = html_writer::link($link, 'my name');
        $expected_text = 'my name';

        $column = new rb_column('text', 123, 'test', 'test', ['extrafields' => ['name' => null]]);
        $alias = reportbuilder_get_extrafield_alias('text', '123', 'name');
        $row->$alias = 'my <b>name</b>';

        $text = scheduled_rule_name::display('123', 'text', $row, $column, $report);
        self::assertSame($expected_text, $text);

        $html = scheduled_rule_name::display('123', 'html', $row, $column, $report);
        self::assertSame($expected_html, $html);
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->inject_mock_actions();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();
        $this->remove_mock_actions();
    }
}
