<?php
/*
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @category totara_catalog
 */

use core\orm\query\builder;
use core_phpunit\testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * @group perform
 */
class mod_perform_suspended_users_hook_testcase extends testcase {

    public function setting_data_provider() {
        return [
            ['perform_hide_suspended_users'],
            ['perform_close_suspended_user_instances'],
        ];
    }

    /**
     * @dataProvider setting_data_provider
     * @param string $setting_name
     * @return void
     */
    public function test_hook_queues_the_task_when_setting_is_turned_on(string $setting_name): void {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        self::setAdminUser();
        admin_get_root(true); // Avoid random errors depending on test order.

        $this->assertFalse(
            builder::table('task_adhoc')
                ->where('classname', '\mod_perform\task\close_instances_for_suspended_users_task')
                ->exists()
        );
        self::assertEquals(0, get_config(null, $setting_name));

        admin_write_settings((object)['s__' . $setting_name => '1']);

        self::assertEquals(1, get_config(null, $setting_name));

        $this->assertTrue(
            builder::table('task_adhoc')
                ->where('classname', '\mod_perform\task\close_instances_for_suspended_users_task')
                ->where('component', 'mod_perform')
                ->where('userid', get_admin()->id)
                ->exists()
        );
    }

    /**
     * @dataProvider setting_data_provider
     * @param string $setting_name
     * @return void
     */
    public function test_hook_does_not_queue_the_task_when_setting_is_turned_off(string $setting_name): void {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        self::setAdminUser();
        admin_get_root(true); // Avoid random errors depending on test order.

        $this->assertFalse(
            builder::table('task_adhoc')
                ->where('classname', '\mod_perform\task\close_instances_for_suspended_users_task')
                ->exists()
        );

        set_config($setting_name, 1);
        self::assertEquals(1, get_config(null, $setting_name));

        admin_write_settings((object)['s__' . $setting_name => '0']);

        self::assertEquals(0, get_config(null, $setting_name));

        $this->assertFalse(
            builder::table('task_adhoc')
                ->where('classname', '\mod_perform\task\close_instances_for_suspended_users_task')
                ->exists()
        );
    }
}
