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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

use core\orm\query\builder;
use totara_msteams\botfw\storage\database_storage;
use totara_msteams\userdata\usersetting as user_data_setting;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die;

/**
 * Test userdata\usersetting class.
 */
class totara_msteams_userdata_usersetting_testcase extends advanced_testcase {
    /** @var stdClass */
    private $user;

    /** @var databasse_storage */
    private $storage;

    public function setUp(): void {
        $this->user = $this->getDataGenerator()->create_user();
        $this->storage = new database_storage('dontcare', 'dontcare');
        $someone = $this->getDataGenerator()->create_user();
        foreach ([$this->user, $someone] as $user) {
            $this->storage->user_store($user->id, '@lorem', (object)['kia' => 'ora']);
            $this->storage->user_store($user->id, 'ipsum', (object)['kia' => 'kaha']);
            $this->storage->user_store($user->id, 'time', (object)['now' => time()]);
        }
        $this->assertEquals(6, builder::table('totara_msteams_user_settings')->count());
    }

    public function tearDown(): void {
        $this->user = null;
        $this->storage = null;
    }

    public function test_purge() {
        $user = new target_user($this->user);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, user_data_setting::execute_purge($user, context_system::instance()));
        $this->assertEquals(3, builder::table('totara_msteams_user_settings')->count());
    }

    public function test_count() {
        $user = new target_user($this->user);
        $this->assertEquals(3, user_data_setting::execute_count($user, context_system::instance()));
    }
}
