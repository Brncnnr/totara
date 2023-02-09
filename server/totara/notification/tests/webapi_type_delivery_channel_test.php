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
 * @author  Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_notification\testing\generator;
use totara_notification\webapi\resolver\type\delivery_channel as delivery_channel_type;
use totara_notification_mock_delivery_channel as mock_channel;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_notification_webapi_type_delivery_channel_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @var delivery_channel_type
     */
    private $channel;

    /**
     * @dataProvider
     * @return array
     */
    public function mock_delivery_channel_data_provider(): array {
        return [
            ['component', 'test-component'],
            ['label', 'human readable label'],
            ['is_enabled', true],
            ['is_enabled', false],
            ['parent_component', 'parent_class_name'],
            ['is_sub_delivery_channel', true, ['parent_component' => 'has a parent']],
            ['display_order', 99],
        ];
    }

    /**
     * @dataProvider mock_delivery_channel_data_provider
     * @param string $field
     * @param $mock_value
     * @param array $extra_attrs
     * @return void
     */
    public function test_resolver_fields(string $field, $mock_value, array $extra_attrs = []): void {
        if (!empty($extra_attrs)) {
            foreach ($extra_attrs as $key => $value) {
                mock_channel::set_attribute($key, $value);
            }
        } else {
            mock_channel::set_attribute($field, $mock_value);
        }

        $value = $this->resolve_graphql_type(
            $this->get_graphql_name(delivery_channel_type::class),
            $field,
            $this->channel
        );

        self::assertEquals($mock_value, $value);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->channel = null;
        mock_channel::clear();
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        $generator = generator::instance();
        $generator->include_mock_delivery_channels();

        $this->channel = mock_channel::make(true);
    }
}