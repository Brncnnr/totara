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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\data_providers\activity\activity_settings;
use mod_perform\models\activity\activity_setting;
use mod_perform\entity\activity\activity_setting as activity_setting_entity;

/**
 * @coversDefaultClass \mod_perform\data_providers\activity\activity_settings
 *
 * @group perform
 */
class mod_perform_activity_settings_data_provider_testcase extends advanced_testcase {
    /**
     * @covers ::get
     * @covers ::lookup
     * @covers ::update
     * @covers ::remove
     * @covers ::clear
     */
    public function test_crud(): void {
        $settings = $this->create_test_data();
        $this->verify_settings([], $settings);

        $initial = [
            activity_setting::MULTISECTION => true
        ];
        $settings->update($initial);
        $this->verify_settings($initial, $settings);

        $updated = [
            activity_setting::MULTISECTION => false,
            activity_setting::CLOSE_ON_COMPLETION => true
        ];
        $settings->update($updated);

        $after_update = array_merge($initial, $updated);
        $this->verify_settings($after_update, $settings);

        $more_updated = [
            activity_setting::MULTISECTION => false,
            activity_setting::CLOSE_ON_COMPLETION => true,
            activity_setting::CLOSE_ON_DUE_DATE => false
        ];
        $settings->update($more_updated);

        $after_update = array_merge($after_update, $more_updated);
        $this->verify_settings($after_update, $settings);

        $removed = [
            activity_setting::MULTISECTION
        ];
        $settings->remove($removed);

        $after_remove = array_filter(
            $after_update,
            function (string $key) use ($removed): bool {
                return !in_array($key, $removed);
            },
            ARRAY_FILTER_USE_KEY
        );
        $this->verify_settings($after_remove, $settings);

        foreach ($removed as $name) {
            $this->assertEquals(999, $settings->lookup($name, 999), 'wrong value');
        }

        $settings->clear();
        $this->verify_settings([], $settings);
    }

    /**
     * @covers ::update
     */
    public function test_access_by_activity(): void {
        $activity = $this->create_test_data()->get_activity();

        $values = [
            activity_setting::MULTISECTION => true,
            activity_setting::CLOSE_ON_COMPLETION => true,
            activity_setting::CLOSE_ON_DUE_DATE => false
        ];
        $settings_via_activity = $activity->settings->update($values);
        $this->verify_settings($values, $settings_via_activity);
    }

    /**
     * @covers ::update
     */
    public function test_invalid_setting_name(): void {
        $settings = $this->create_test_data();
        $this->verify_settings([], $settings);

        $name = "aaa";
        $updated = [
            activity_setting::MULTISECTION => true,
            $name => "testing",
            activity_setting::CLOSE_ON_COMPLETION => true,
            activity_setting::CLOSE_ON_DUE_DATE => false
        ];

        $this->expectExceptionMessageMatches("/$name/");
        $settings->update($updated);
    }

    /**
     * Convenience function check the expected setting values against the actual
     * ones.
     *
     * @param array $expected mapping of setting names to expected values.
     * @param activity_settings $settings object under test.
     */
    private function verify_settings(array $expected, activity_settings $settings): void {
        $activity_id = $settings->get_activity()->id;

        $db_count = activity_setting_entity::repository()
            ->where('activity_id', $activity_id)
            ->count();
        $this->assertEquals(count($expected), $db_count, 'wrong db settings count');
        $this->assertEquals(count($expected), $settings->get()->count(), 'wrong setting count');

        foreach ($expected as $name => $value) {
            $value = is_bool($value) ? (int)$value : $value;
            $this->assertEquals($settings->lookup($name), (string)$value, 'wrong value');
        }
    }

    /**
     * Generates test data.
     *
     * @return activity_settings activity settings object to use for testing.
     */
    private function create_test_data(): activity_settings {
        $this->setAdminUser();

        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container();

        return new activity_settings($activity);
    }
}
