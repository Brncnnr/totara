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

class core_hook_login_page_testcase extends advanced_testcase {
    public function test_login_page_start_hook() {
        $self = $this;
        $hook = function ($hook) use ($self, &$i) {
            $self->assertInstanceOf(core\hook\login_page_start::class, $hook);
        };

        $watchers = array(
            array(
                'hookname' => 'core\hook\login_page_start',
                'callback' => $hook,
            ),
        );
        totara_core\hook\manager::phpunit_replace_watchers($watchers);

        $instance = new core\hook\login_page_start();
        $instance->execute();

        totara_core\hook\manager::phpunit_reset();
    }

    public function test_login_page_login_complete_hook() {
        $self = $this;
        $hook = function ($hook) use ($self, &$i) {
            $self->assertInstanceOf(core\hook\login_page_login_complete::class, $hook);
        };

        $watchers = array(
            array(
                'hookname' => 'core\hook\login_page_login_complete',
                'callback' => $hook,
            ),
        );
        totara_core\hook\manager::phpunit_replace_watchers($watchers);

        $instance = new core\hook\login_page_login_complete();
        $instance->execute();

        totara_core\hook\manager::phpunit_reset();
    }
}
