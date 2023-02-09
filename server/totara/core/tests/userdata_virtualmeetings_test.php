<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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

use totara_core\entity\virtual_meeting as virtual_meeting_entity;
use totara_core\entity\virtual_meeting_auth as virtual_meeting_auth_entity;
use totara_core\entity\virtual_meeting_config as virtual_meeting_config_entity;
use totara_core\userdata\virtualmeetings;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * @group virtualmeeting
 * @coversDefaultClass totara_core\userdata\virtualmeetings
 */
class totara_core_userdata_virtualmeetings_testcase extends advanced_testcase {
    /**
     * @covers ::is_purgeable
     */
    public function test_is_purgeable(): void {
        $this->assertTrue(virtualmeetings::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(virtualmeetings::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(virtualmeetings::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * @covers ::purge
     * @covers ::get_builder_virtual_meeting
     * @covers ::get_builder_virtual_meeting_auth
     */
    public function test_purge(): void {
        [$user1, $user2] = $this->seed_data();
        $result = virtualmeetings::execute_purge(new target_user($user1), context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEquals(0, virtual_meeting_entity::repository()->where('userid', $user1->id)->count());
        $this->assertEquals(0, virtual_meeting_auth_entity::repository()->where('userid', $user1->id)->count());
        $this->assertEquals(0, virtual_meeting_config_entity::repository()->join([virtual_meeting_entity::TABLE, 'v'], 'virtualmeetingid', 'id')->where('v.userid', $user1->id)->count());
        $this->assertEquals(2, virtual_meeting_entity::repository()->where('userid', $user2->id)->count());
        $this->assertEquals(1, virtual_meeting_auth_entity::repository()->where('userid', $user2->id)->count());
        $this->assertEquals(3, virtual_meeting_config_entity::repository()->join([virtual_meeting_entity::TABLE, 'v'], 'virtualmeetingid', 'id')->where('v.userid', $user2->id)->count());
    }

    /**
     * @covers ::count
     * @covers ::get_builder_virtual_meeting
     */
    public function test_count(): void {
        [$user1, $user2] = $this->seed_data();
        $result = virtualmeetings::execute_count(new target_user($user1), context_system::instance());
        $this->assertEquals(3, $result);
    }

    /**
     * @covers ::export
     * @covers ::get_builder_virtual_meeting
     */
    public function test_export() {
        [$user1, $user2, $meetings1] = $this->seed_data();
        /** @var export */
        $result = virtualmeetings::execute_export(new target_user($user1), context_system::instance());
        $expected = array_map(function (virtual_meeting_entity $entity) {
            $names = ['poc_app' => 'Fake Dev App', 'poc_user' => 'Fake Dev User', 'poc_none' => ''];
            return (object)[
                'id' => $entity->id,
                'plugin' => $entity->plugin,
                'provider' => $names[$entity->plugin],
                'timecreated' => $entity->timecreated,
                'timemodified' => $entity->timemodified,
            ];
        }, $meetings1);
        $comparer = function ($x, $y) {
            return $x->id <=> $y->id;
        };
        $outcome = $result->data['instances'];
        usort($expected, $comparer);
        usort($outcome, $comparer);
        $this->assertEquals($expected, $outcome);
    }

    /**
     * @param string $plugin
     * @param object $user
     * @param array $configs [name => value, ...]
     * @return virtual_meeting_entity
     */
    private function add_virtual_meeting(string $plugin, object $user, array $configs): virtual_meeting_entity {
        $vm = new virtual_meeting_entity();
        $vm->plugin = $plugin;
        $vm->userid = $user->id;
        $vm->save();
        foreach ($configs as $name => $value) {
            $vmc = new virtual_meeting_config_entity();
            $vmc->virtualmeetingid = $vm->id;
            $vmc->name = $name;
            $vmc->value = $value;
            $vmc->save();
        }
        return $vm;
    }

    /**
     * @return array [user1, user2, meetings1, meetings2]
     */
    private function seed_data(): array {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        // seed data
        $meetings1 = [
            $this->add_virtual_meeting('poc_app', $user1, ['baa' => 'boo']),
            $this->add_virtual_meeting('poc_user', $user1, ['faa' => 'foo',' haa' => 'hoo']),
            $this->add_virtual_meeting('poc_none', $user1, ['paa' => 'poo']),
        ];
        $meetings2 = [
            $this->add_virtual_meeting('poc_app', $user2, ['taa' => 'too']),
            $this->add_virtual_meeting('poc_user', $user2, ['naa' => 'noo', 'yaa' => 'yoo']),
        ];
        $vma11 = new virtual_meeting_auth_entity();
        $vma11->plugin = 'poc_user';
        $vma11->access_token = 'kia ora';
        $vma11->refresh_token = 'kia kaha';
        $vma11->timeexpiry = 1010101010;
        $vma11->userid = $user1->id;
        $vma11->save();
        $vma12 = new virtual_meeting_auth_entity();
        $vma12->plugin = 'poc_none';
        $vma12->access_token = 'hell-o';
        $vma12->refresh_token = 'b3k1nD';
        $vma12->timeexpiry = 1234567890;
        $vma12->userid = $user1->id;
        $vma12->save();
        $vma21 = new virtual_meeting_auth_entity();
        $vma21->plugin = 'poc_user';
        $vma21->access_token = '><';
        $vma21->refresh_token = '//';
        $vma21->timeexpiry = 1357924680;
        $vma21->userid = $user2->id;
        $vma21->save();
        return [$user1, $user2, $meetings1, $meetings2];
    }
}
