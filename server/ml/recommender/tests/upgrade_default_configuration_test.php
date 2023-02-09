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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package ml_recommender
 */
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/ml/recommender/db/upgradelib.php');

class ml_recommender_upgrade_default_configuration_testcase extends advanced_testcase {

    public function test_upgrade_default_configuration_with_disabled_feature(): void {
        advanced_feature::disable('ml_recommender');
        self::assertTrue(advanced_feature::is_disabled('ml_recommender'));

        $this->run_upgrade_test();
    }

    public function test_upgrade_default_configuration_with_feature_abled(): void {
        advanced_feature::enable('ml_recommender');
        self::assertTrue(advanced_feature::is_enabled('ml_recommender'));

        $this->run_upgrade_test();
    }

    private function run_upgrade_test(): void {
        ml_recommender_upgrade_default_configuration();

        $new_config = [
            'query' => 'hybrid',
            'user_result_count' => 5,
            'item_result_count' => 5,
            'related_items_count' => 5
        ];

        foreach ($new_config as $key => $value) {
            $config_value = get_config('ml_recommender', $key);
            self::assertEquals($value, $config_value);
        }
    }

    public function test_upgrade_default_configuration_with_config_changed_by_admin(): void {
        // Create some config that has already changed by admin
        set_config('user_result_count', 9, 'ml_recommender');
        set_config('related_items_count', 11, 'ml_recommender');

        self::assertEquals(9, get_config('ml_recommender', 'user_result_count'));
        self::assertEquals(11, get_config('ml_recommender', 'related_items_count'));

        $new_config = [
            'query' => 'hybrid',
            'user_result_count' => 9,
            'item_result_count' => 5,
            'related_items_count' => 11
        ];

        ml_recommender_upgrade_default_configuration();

        foreach ($new_config as $key => $value) {
            $config_value = get_config('ml_recommender', $key);
            self::assertEquals($value, $config_value);
        }
    }
}
