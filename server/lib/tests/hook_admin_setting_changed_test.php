<?php
/*
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
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

defined('MOODLE_INTERNAL') || die();

class core_hook_admin_setting_changed_testcase extends advanced_testcase {
    public function test_hook() {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $this->setAdminUser();
        admin_get_root(true, true); // Fix random errors depending on test order.

        $self = $this;
        $i = 0;
        $hook = function ($hook) use ($self, &$i) {
            $i++;
            $self->assertInstanceOf(core\hook\admin_setting_changed::class, $hook);
            $this->assertSame('usetags', $hook->name);
            $this->assertSame('1', $hook->oldvalue);
            $this->assertSame('0', $hook->newvalue);
        };

        $watchers = array(
            array(
                'hookname' => 'core\hook\admin_setting_changed',
                'callback' => $hook,
            ),
        );
        totara_core\hook\manager::phpunit_replace_watchers($watchers);

        // Any setting will do.
        $this->assertSame('1', get_config('core', 'usetags'));
        admin_write_settings(['s__usetags' => '0']);
        $this->assertSame('0', get_config('core', 'usetags'));
        $this->assertSame(1, $i);

        // Change to same value must be ignored.
        admin_write_settings(['s__usetags' => '0']);
        $this->assertSame(1, $i);

        // Calls to set_config() are not tracked.
        set_config('usetags', '1');
        $this->assertSame('1', get_config('core', 'usetags'));
        $this->assertSame(1, $i);

        totara_core\hook\manager::phpunit_reset();
    }
}
