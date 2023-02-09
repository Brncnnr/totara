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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */

use totara_core\extended_context;
use core_phpunit\testcase;
use totara_notification\model\notification_preference_value;
use totara_notification\testing\generator;
use totara_notification_mock_built_in_notification as mock_built_in;
use totara_notification_mock_notifiable_event_resolver as mock_resolver;

class totara_notification_notification_preference_value_testcase extends testcase {
    /**
     * @return void
     */
    public function test_instantiate_from_built_in(): void {
        $generator = generator::instance();
        $generator->include_mock_built_in_notification();

        $preference_value = notification_preference_value::from_built_in_notification(mock_built_in::class);
        self::assertEquals(
            mock_built_in::get_title(),
            $preference_value->get_title(),
        );

        self::assertEquals(
            mock_built_in::get_default_body_format(),
            $preference_value->get_body_format()
        );

        self::assertEquals(
            mock_built_in::get_default_body()->out(),
            $preference_value->get_body()
        );

        self::assertEquals(
            mock_built_in::get_default_subject()->out(),
            $preference_value->get_subject()
        );

        self::assertEquals(
            mock_built_in::get_default_subject_format(),
            $preference_value->get_subject_format()
        );
    }

    /**
     * @return void
     */
    public function test_instiantiate_from_preference_instance(): void {
        $generator = generator::instance();
        $generator->include_mock_notifiable_event_resolver();
        $generator->include_mock_recipient();

        $preference = $generator->create_notification_preference(
            mock_resolver::class,
            extended_context::make_with_context(context_system::instance()),
            ['recipient' => totara_notification_mock_recipient::class, 'recipients' => [totara_notification_mock_recipient::class]]
        );

        $preference_value = notification_preference_value::from_parent_notification_preference($preference);

        self::assertEquals(
            $preference->get_title(),
            $preference_value->get_title()
        );

        self::assertEquals(
            $preference->get_subject(),
            $preference_value->get_subject()
        );

        self::assertEquals(
            $preference->get_body_format(),
            $preference_value->get_body_format()
        );

        self::assertEquals(
            $preference->get_body(),
            $preference_value->get_body()
        );

        self::assertEquals(
            $preference->get_subject_format(),
            $preference_value->get_subject_format()
        );
    }
}
