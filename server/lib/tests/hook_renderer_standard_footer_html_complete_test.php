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
 * @author  Chris Snyder <chris.snyder@totaralearning.com>
 * @package core
 */

defined('MOODLE_INTERNAL') || die();

class core_hook_renderer_standard_footer_html_complete_testcase extends advanced_testcase {
    public function test_hook() {
        global $PAGE;

        $self = $this;
        $hook = function ($hook) use ($self, &$i) {
            $self->assertInstanceOf(core\hook\renderer_standard_footer_html_complete::class, $hook);
            $self->assertNotEmpty($hook->output);
            $self->assertInstanceOf(\core_renderer::class, $hook->renderer);
            $self->assertInstanceOf(\moodle_page::class, $hook->page);
            $hook->output .= 'Passed by reference';
        };

        $watchers = array(
            array(
                'hookname' => 'core\hook\renderer_standard_footer_html_complete',
                'callback' => $hook,
            ),
        );
        totara_core\hook\manager::phpunit_replace_watchers($watchers);

        $out = $PAGE->get_renderer('core')->standard_footer_html();
        $self->assertEquals('Passed by reference', substr($out, 0 - strlen('Passed by reference')));

        totara_core\hook\manager::phpunit_reset();
    }
}
