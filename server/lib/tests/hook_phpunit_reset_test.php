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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core\hook\phpunit_reset;
use totara_core\hook\manager;

class core_hook_phpunit_reset_testcase extends advanced_testcase {

    public function test_phpunit_reset_hook() {
        $self = $this;
        $hook = function ($hook) use ($self, &$i) {
            $self->assertInstanceOf(phpunit_reset::class, $hook);
        };

        $watchers = [
            [
                'hookname' => 'core\hook\phpunit_reset',
                'callback' => $hook,
            ],
        ];

        manager::phpunit_replace_watchers($watchers);

        $instance = new phpunit_reset();
        $instance->execute();

        manager::phpunit_reset();
    }
}