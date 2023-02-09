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
 * @package totara_topic
 */

use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_topic\webapi\resolver\query\get_config;

class totara_topic_webapi_reslover_query_get_config_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_get_config(): void {
        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(get_config::class),
            [
                'component' => 'engage_article',
                'item_type' => 'engage_resource'
            ]
        );
        self::assertIsArray($result);
        self::assertTrue($result['enabled']);

        set_config('usetags', 0);

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(get_config::class),
            [
                'component' => 'engage_article',
                'item_type' => 'engage_resource'
            ]
        );
        self::assertFalse($result['enabled']);
    }

    public function test_get_config_item_type_false(): void {
        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(get_config::class),
            [
                'component' => 'engage_article',
                'item_type' => 'wrong_item_type'
            ]
        );
        self::assertIsArray($result);
        self::assertFalse($result['enabled']);
    }

    public function test_get_config_component_false(): void {
        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(get_config::class),
            [
                'component' => 'wrong_component',
                'item_type' => 'engage_resource'
            ]
        );
        self::assertIsArray($result);
        self::assertFalse($result['enabled']);
    }

    public function test_get_config_component_disabled(): void {
        global $DB;
        $tag_area_id = $DB->get_field('tag_area', 'id', [
                'component' => 'engage_article',
                'itemtype' => 'engage_resource'
            ]
        );
        $DB->update_record('tag_area', (object)['id' => $tag_area_id, 'enabled' => 0]);

        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $result = $this->resolve_graphql_query(
            $this->get_graphql_name(get_config::class),
            [
                'component' => 'engage_article',
                'item_type' => 'engage_resource'
            ]
        );
        self::assertIsArray($result);
        self::assertFalse($result['enabled']);
    }

    public function test_get_config_no_login(): void {

        $this->expectException(\moodle_exception::class);

        $this->resolve_graphql_query(
            $this->get_graphql_name(get_config::class),
            [
                'component' => 'engage_article',
                'item_type' => 'engage_resource'
            ]
        );
    }

}