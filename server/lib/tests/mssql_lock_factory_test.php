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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Test mssql locking.
 */
class core_mssql_lock_factory_testcase extends advanced_testcase {
    public function test_locking() {
        global $DB;

        if ($DB->get_dbfamily() !== 'mssql') {
            $this->markTestSkipped('mssql_lock_factory works with MS SQL only');
        }

        $factory = new \core\lock\mssql_lock_factory('test');

        $key1 = 'lock1';
        $name1 = $factory->get_lock_name_from_key($key1);
        $this->assertFalse($this->is_used_lock($name1));
        $lock1 = $factory->get_lock($key1, 100);
        $this->assertInstanceOf(core\lock\lock::class, $lock1);
        $this->assertTrue($this->is_used_lock($name1));

        $key2 = 'lock.2 ?';
        $name2 = $factory->get_lock_name_from_key($key2);
        $this->assertFalse($this->is_used_lock($name2));
        $lock2 = $factory->get_lock($key2, 100);
        $this->assertInstanceOf(core\lock\lock::class, $lock2);
        $this->assertTrue($this->is_used_lock($name2));

        $key3 = 'lock.3 lllllllooooooooooooooooooooooooooooooooooooooooooooonnnnnnnnnnnnnnngggggggggggggggggggggggggs';
        $name3 = $factory->get_lock_name_from_key($key3);
        $this->assertFalse($this->is_used_lock($name3));
        $lock3 = $factory->get_lock($key3, 100);
        $this->assertInstanceOf(core\lock\lock::class, $lock3);
        $this->assertTrue($this->is_used_lock($name3));

        $this->assertTrue($lock1->release());
        $this->assertFalse($this->is_used_lock($name1));
        $this->assertTrue($this->is_used_lock($name2));
        $this->assertTrue($this->is_used_lock($name3));

        $factory->auto_release();
        $this->assertFalse($this->is_used_lock($name1));
        $this->assertFalse($this->is_used_lock($name2));
        $this->assertFalse($this->is_used_lock($name3));

        $this->assertTrue($lock2->release());
    }

    protected function is_used_lock($name): bool {
        global $DB;
        $mode = $DB->get_field_sql("SELECT APPLOCK_MODE('public', ?, 'Session');", [$name]);
        return ($mode !== 'NoLock');
    }
}