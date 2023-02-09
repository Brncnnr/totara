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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\format;
use mod_perform\models\activity\notification as notification_model;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\type\notification
 *
 * @group perform
 */
class mod_perform_webapi_type_notification_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private const TYPE = 'mod_perform_notification';

    /**
     * Create an activity for testing.
     *
     * @return array
     */
    private function create_test_data(): array {
        $this->setAdminUser();

        $perform_generator = \mod_perform\testing\generator::instance();

        $activity = $perform_generator->create_activity_in_container();
        $context = $activity->get_context();

        return [$activity, $context];
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_input(): void {
        [$activity, $context] = $this->create_test_data();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/notification/");
        $this->resolve_graphql_type(self::TYPE, 'id', new \stdClass());
    }

    public function data_class_keys(): array {
        return [
            ['instance_created'],
            ['instance_created_reminder'],
            ['due_date_reminder'],
            ['due_date'],
            ['completion'],
        ];
    }

    /**
     * @covers ::resolve
     * @dataProvider data_class_keys
     * @param string $class_key
     */
    public function test_invalid_field(string $class_key): void {
        [$activity, $context] = $this->create_test_data();

        $notification = notification_model::load_by_activity_and_class_key($activity, $class_key);
        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");
        $this->resolve_graphql_type(self::TYPE, $field, $notification, [], $context);
    }

    /**
     * @covers ::resolve
     * @dataProvider data_class_keys
     * @param string $class_key
     */
    public function test_resolve(string $class_key): void {
        // Note: cannot use dataproviders here because PHPUnit runs these before
        // everything else. Incredibly, if a dataprovider in a random testsuite
        // creates database records or sends messages, etc, those will also be
        // visible to _all_ tests. In other words, with dataproviders, current
        // and yet unborn tests do not start in a clean state!
        [$activity, $context] = $this->create_test_data();

        $notification = notification_model::load_by_activity_and_class_key($activity, $class_key);

        $testcases = [
            'id' => ['id', null, $notification->id],
            'name' => ['name', format::FORMAT_PLAIN, $notification->name],
            'active' => ['active', null, $notification->active],
            'trigger_label' => ['trigger_label', format::FORMAT_PLAIN, $notification->trigger_label],
            'trigger_type' => ['trigger_type', null, [null, 'BEFORE', 'AFTER'][$notification->trigger_type]],
            'triggers' => ['triggers', null, $notification->triggers],
        ];

        foreach ($testcases as $id => $testcase) {
            [$field, $format, $expected] = $testcase;
            $args = $format ? ['format' => $format] : [];

            $value = $this->resolve_graphql_type(self::TYPE, $field, $notification, $args, $context);
            $this->assertSame($expected, $value, "[$id] wrong value");
        }
    }
}
