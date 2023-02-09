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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Qingyang.liu <qingyang.liu@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_notification\model\notification_preference as model;
use totara_notification\testing\generator;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_notification_webapi_type_extended_context_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @var model|null
     */
    private $system_built_in;

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->system_built_in = null;
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $generator->include_mock_notifiable_event();
        $this->system_built_in = $generator->add_mock_built_in_notification_for_component();
    }

    /**
     * @return void
     */
    public function test_resolve_field_context_id(): void {
        self::assertEquals(
            $this->system_built_in->get_extended_context()->get_context_id(),
            $this->resolve_graphql_type(
                'totara_notification_extended_context',
                'context_id',
                $this->system_built_in->get_extended_context()
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_area(): void {
        self::assertEquals(
            $this->system_built_in->get_extended_context()->get_area(),
            $this->resolve_graphql_type(
                'totara_notification_extended_context',
                'area',
                $this->system_built_in->get_extended_context()
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_item_id(): void {
        self::assertEquals(
            $this->system_built_in->get_extended_context()->get_item_id(),
            $this->resolve_graphql_type(
                'totara_notification_extended_context',
                'item_id',
                $this->system_built_in->get_extended_context()
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_component(): void {
        self::assertEquals(
            $this->system_built_in->get_extended_context()->get_component(),
            $this->resolve_graphql_type(
                'totara_notification_extended_context',
                'component',
                $this->system_built_in->get_extended_context()
            )
        );
    }

}