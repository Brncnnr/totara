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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_notification
 */

use core_phpunit\testcase;
use totara_notification\entity\notifiable_event_user_preference;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;

/**
 * @group totara_notification
 */
class totara_notification_user_delivery_channels_testcase extends testcase {

    /**
     * @return array
     */
    public function provide_test_data(): array {
        return [
            [',second,', ['second']],
            [',,', []],
            [',third,ninth,', ['third', 'ninth']],
            [null, null],
        ];
    }

    /**
     * Test delivery channel values stored in the entity are converted back to objects.
     *
     * @dataProvider provide_test_data
     * @param string|null $test_case
     * @param array|null $expected
     */
    public function test_delivery_channels_convert_from_db(?string $test_case, ?array $expected): void {
        global $DB;
        $DB->delete_records(notifiable_event_user_preference::TABLE);

        // Create a new entity (directly in the DB) then load it, and check the db converts correctly
        $record = [
            'resolver_class_name' => mock_resolver::class,
            'context_id' => 1,
            'enabled' => true,
            'delivery_channels' => $test_case,
        ];
        $record_id = $DB->insert_record(notifiable_event_user_preference::TABLE, $record);

        /** @var notifiable_event_user_preference $entity */
        $entity = notifiable_event_user_preference::repository()
            ->where('id', $record_id)
            ->order_by('id')
            ->first_or_fail();
        self::assertSame($expected, $entity->delivery_channels);
    }

    /**
     * @dataProvider provide_test_data
     * @param string|null $expected
     * @param array|null $test_case
     */
    public function test_delivery_channels_convert_to_db(?string $expected, ?array $test_case): void {
        global $DB;
        $DB->delete_records(notifiable_event_user_preference::TABLE);

        // Create a new entity
        $entity = new notifiable_event_user_preference();
        $entity->resolver_class_name = mock_resolver::class;
        $entity->context_id = 1;
        $entity->enabled = true;
        $entity->delivery_channels = $test_case;
        /** @var notifiable_event_user_preference $entity */
        $entity = notifiable_event_user_preference::repository()->create_entity($entity);

        // Lookup directly in the DB
        $delivery_channels = $DB->get_field(
            notifiable_event_user_preference::TABLE,
            'delivery_channels',
            ['id' => $entity->id]
        );
        self::assertSame($expected, $delivery_channels);
    }
}