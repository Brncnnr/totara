<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

use core_phpunit\testcase;
use totara_notification\placeholder\option;

use mod_perform\totara_notification\placeholder\perform_activity as perform_activity_placeholder;
use mod_perform\testing\generator as perform_generator;

defined('MOODLE_INTERNAL') || die();

/**
 * @group mod_perform
 * @group totara_notification
 */
class mod_perform_totara_notification_perform_activity_placeholder_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        self::setAdminUser();
    }

    public function test_placeholder() {
        $perform_generator = perform_generator::instance();
        $activity = $perform_generator->create_activity_in_container(
            [
                'activity_name' => 'Lorem ipsum dolor sit amet consectetuer adipiscing elit',
                'activity_type' => 'feedback'
            ]
        );
        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, perform_activity_placeholder::get_options());
        self::assertEqualsCanonicalizing(
            [
                'name',
                'type'
            ],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        $placeholder = perform_activity_placeholder::from_id($activity->id);

        self::assertEquals('Lorem ipsum dolor sit amet consectetuer adipiscing elit', $placeholder->do_get('name'));
        self::assertEquals('Feedback', $placeholder->do_get('type'));
    }
}
