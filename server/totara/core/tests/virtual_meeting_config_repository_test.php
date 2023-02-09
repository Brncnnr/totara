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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

use core\orm\query\exceptions\record_not_found_exception;
use totara_core\entity\virtual_meeting as virtual_meeting_entity;
use totara_core\entity\virtual_meeting_config as virtual_meeting_config_entity;

/**
 * @group virtualmeeting
 * @coversDefaultClass totara_core\entity\virtual_meeting_config_repository
 */
class totara_core_virtual_meeting_config_repository_testcase extends advanced_testcase {
    /**
     * @covers ::find_by_name
     */
    public function test_find_by_name(): void {
        $user = $this->getDataGenerator()->create_user();
        $vm1 = new virtual_meeting_entity();
        $vm1->plugin = 'poc_app';
        $vm1->userid = $user->id;
        $vm1->save();
        $vm2 = new virtual_meeting_entity();
        $vm2->plugin = 'poc_app';
        $vm2->userid = $user->id;
        $vm2->save();
        $vmc1 = new virtual_meeting_config_entity();
        $vmc1->name = 'test';
        $vmc1->value = 'one';
        $vmc1->virtualmeetingid = $vm1->id;
        $vmc1->save();
        $vmc2 = new virtual_meeting_config_entity();
        $vmc2->name = 'test';
        $vmc2->value = 'two';
        $vmc2->virtualmeetingid = $vm2->id;
        $vmc2->save();
        $this->assertEquals(2, virtual_meeting_config_entity::repository()->count());

        $this->assertEquals('one', virtual_meeting_config_entity::repository()->find_by_name($vm1, 'test')->value);
        $this->assertEquals('one', virtual_meeting_config_entity::repository()->find_by_name($vm1->id, 'test')->value);
        $this->assertEquals('two', virtual_meeting_config_entity::repository()->find_by_name($vm2, 'test')->value);
        $this->assertEquals('two', virtual_meeting_config_entity::repository()->find_by_name($vm2->id, 'test')->value);
        $this->assertNull(virtual_meeting_config_entity::repository()->find_by_name($vm1, 'none'));
        $this->assertNull(virtual_meeting_config_entity::repository()->find_by_name($vm1->id, 'none'));
        (clone $vm1)->delete();
        $this->assertNull(virtual_meeting_config_entity::repository()->find_by_name($vm1, 'test'));
        $this->assertNull(virtual_meeting_config_entity::repository()->find_by_name($vm1->id, 'test'));
        try {
            virtual_meeting_config_entity::repository()->find_by_name($vm2, 'none', true);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }
        try {
            virtual_meeting_config_entity::repository()->find_by_name($vm2->id, 'none', true);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }
    }
}
