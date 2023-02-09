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

use core\orm\query\sql\sql;
use totara_msteams\page_helper;

defined('MOODLE_INTERNAL') || die;

/**
 * @coversDefaultClass \totara_msteams\page_helper
 */
class totara_msteams_page_helper_testcase extends advanced_testcase {
    /**
     * @covers ::find_block_instance
     */
    public function test_find_block_instance() {
        $blockname = 'kei_te_pai'; // block name is not validated
        $gen = $this->getDataGenerator();
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $user3 = $gen->create_user();
        $sysctx = context_system::instance();
        $user1ctx = context_user::instance($user1->id);
        $user2ctx = context_user::instance($user2->id);

        $this->assertNull(page_helper::find_block_instance($blockname, $user1));

        $records = [
            (object)[
                'blockname' => $blockname,
                'parentcontextid' => $user1ctx->id,
                'showinsubcontexts' => 0,
                'defaultweight' => 0,
                'configdata' => 'correct1',
                'timecreated' => 1000,
                'timemodified' => 3000,
            ],
            (object)[
                'blockname' => $blockname,
                'parentcontextid' => $user1ctx->id,
                'showinsubcontexts' => 0,
                'defaultweight' => 0,
                'configdata' => 'wrong1',
                'timecreated' => 2000,
                'timemodified' => 2000,
            ],
            (object)[
                'blockname' => $blockname,
                'parentcontextid' => $user2ctx->id,
                'showinsubcontexts' => 0,
                'defaultweight' => 0,
                'configdata' => 'correct2',
                'timecreated' => 2000,
                'timemodified' => 3000,
            ],
            (object)[
                'blockname' => $blockname,
                'parentcontextid' => $sysctx->id,
                'showinsubcontexts' => 0,
                'defaultweight' => 0,
                'configdata' => 'system',
                'timecreated' => 1000,
                'timemodified' => 4000,
            ],
        ];
        sql::get_db()->insert_records('block_instances', $records);

        $instance = page_helper::find_block_instance($blockname, $user1);
        $this->assertNotNull($instance);
        $this->assertEquals('correct1', $instance->configdata);
        $instance = page_helper::find_block_instance($blockname, $user2);
        $this->assertNotNull($instance);
        $this->assertEquals('correct2', $instance->configdata);
        $instance = page_helper::find_block_instance($blockname, $user3);
        $this->assertNotNull($instance);
        $this->assertEquals('system', $instance->configdata);

        /** @var \totara_tenant\testing\generator */
        $tengen = $gen->get_plugin_generator('totara_tenant');
        $tengen->enable_tenants();
        $tenant = $tengen->create_tenant();
        $tenctx = context_tenant::instance($tenant->id);
        $tengen->migrate_user_to_tenant($user1->id, $tenant->id);
        $tengen->migrate_user_to_tenant($user2->id, $tenant->id);
        $tengen->migrate_user_to_tenant($user3->id, $tenant->id);
        $user1 = core_user::get_user($user1->id, '*', MUST_EXIST); // refill the user1 record
        $user2 = core_user::get_user($user2->id, '*', MUST_EXIST); // refill the user2 record
        $user3 = core_user::get_user($user3->id, '*', MUST_EXIST); // refill the user3 record

        $records = [
            (object)[
                'blockname' => $blockname,
                'parentcontextid' => $tenctx->id,
                'showinsubcontexts' => 0,
                'defaultweight' => 0,
                'configdata' => 'tenant',
                'timecreated' => 4000,
                'timemodified' => 5000,
            ],
        ];
        sql::get_db()->insert_records('block_instances', $records);

        $instance = page_helper::find_block_instance($blockname, $user1);
        $this->assertNotNull($instance);
        $this->assertEquals('correct1', $instance->configdata);
        $instance = page_helper::find_block_instance($blockname, $user2);
        $this->assertNotNull($instance);
        $this->assertEquals('correct2', $instance->configdata);
        $instance = page_helper::find_block_instance($blockname, $user3);
        $this->assertNotNull($instance);
        $this->assertEquals('tenant', $instance->configdata);
    }
}
