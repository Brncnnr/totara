<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_core
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests checking feature flags
 */
class totara_core_advanced_feature_testcase extends advanced_testcase {

    public function test_deprecated_hidden_checks() {
        global $CFG;

        unset_config('enablepositions');
        unset_config('enablecompetencies');

        $this->assertObjectNotHasAttribute('enablepositions', $CFG);
        $this->assertObjectNotHasAttribute('enablecompetencies', $CFG);

        $hiding_debug_msg = 'Hiding features is not supported anymore, features can only be enabled or disabled.';

        $this->assertFalse(totara_feature_hidden('positions'));
        $this->assertDebuggingCalled($hiding_debug_msg);
        $this->assertFalse(totara_feature_hidden('competencies'));
        $this->assertDebuggingCalled($hiding_debug_msg);

        set_config('enablepositions', advanced_feature::DISABLED);
        set_config('enablecompetencies', advanced_feature::DISABLED);

        $this->assertFalse(totara_feature_hidden('positions'));
        $this->assertDebuggingCalled($hiding_debug_msg);
        $this->assertFalse(totara_feature_hidden('competencies'));
        $this->assertDebuggingCalled($hiding_debug_msg);

        set_config('enablepositions', TOTARA_HIDEFEATURE);
        set_config('enablecompetencies', TOTARA_HIDEFEATURE);

        $this->assertTrue(totara_feature_hidden('positions'));
        $this->assertDebuggingCalled($hiding_debug_msg);
        $this->assertTrue(totara_feature_hidden('competencies'));
        $this->assertDebuggingCalled($hiding_debug_msg);

        set_config('enablepositions', advanced_feature::ENABLED);
        set_config('enablecompetencies', advanced_feature::ENABLED);

        $this->assertFalse(totara_feature_hidden('positions'));
        $this->assertDebuggingCalled($hiding_debug_msg);
        $this->assertFalse(totara_feature_hidden('competencies'));
        $this->assertDebuggingCalled($hiding_debug_msg);

        $feature = 'iamanunknownadvancedfeature';
        try {
            totara_feature_hidden($feature);
            $this->fail('expected hidden check to fail');
        } catch (coding_exception $exception) {
            $this->assertDebuggingCalled($hiding_debug_msg);
            $this->assertMatchesRegularExpression("/'{$feature}' not supported by Totara feature checking code./", $exception->getMessage());
        }
    }

    /**
     * Pairs of config key names and their corresponding advanced feature keys
     *
     * @return string[][]
     */
    public function feature_checks_provider(): array {
        return [
            'enablepositions' => ['enablepositions', 'positions'],
            'enablecompetencies' => ['enablecompetencies', 'competencies'],
            'enableorganisations' => ['enableorganisations', 'organisations'],
        ];
    }

    /**
     * @dataProvider feature_checks_provider
     * @param string $config_key
     * @param string $feature_key
     */
    public function test_feature_checks(string $config_key, string $feature_key) {
        global $CFG;

        // If there's no config setting at all it's neither disabled not visible nor hidden
        unset_config($config_key);
        $this->assertObjectNotHasAttribute($config_key, $CFG);
        $this->assertFalse(advanced_feature::is_disabled($feature_key));
        $this->assertFalse(advanced_feature::is_enabled($feature_key));

        // If config exists and is set to disabled, then it should not show as enabled.
        set_config($config_key, advanced_feature::DISABLED);
        $this->assertTrue(advanced_feature::is_disabled($feature_key));
        $this->assertFalse(advanced_feature::is_enabled($feature_key));

        // If config exists and is set to enabled, then it should not show as disabled.
        set_config($config_key, advanced_feature::ENABLED);
        $this->assertFalse(advanced_feature::is_disabled($feature_key));
        $this->assertTrue(advanced_feature::is_enabled($feature_key));
    }

    public function test_unknown_feature() {
        $feature = 'iamanunknownadvancedfeature';
        $expected_msg = "'{$feature}' not supported by Totara feature checking code.";

        try {
            advanced_feature::is_disabled($feature);
            $this->fail('Feature check should throw an exception!');
        } catch (coding_exception $exception) {
            $this->assertMatchesRegularExpression("/$expected_msg/", $exception->getMessage());
        }

        try {
            advanced_feature::is_enabled($feature);
            $this->fail('Feature check should throw an exception!');
        } catch (coding_exception $exception) {
            $this->assertMatchesRegularExpression("/$expected_msg/", $exception->getMessage());
        }
    }

    public function test_require() {
        set_config('enablepositions', advanced_feature::DISABLED);

        try {
            advanced_feature::require('positions');
            $this->fail('Feature check should throw an exception!');
        } catch (\totara_core\feature_not_available_exception $exception) {
            // this is expected, do nothing
        }

        set_config('enablepositions', advanced_feature::ENABLED);
        // No exception should be thrown
        advanced_feature::require('positions');
    }

    public function test_enable_disable() {
        set_config('enablepositions', advanced_feature::DISABLED);
        $this->assertTrue(advanced_feature::is_disabled('positions'));
        $this->assertFalse(advanced_feature::is_enabled('positions'));

        advanced_feature::enable('positions');
        $this->assertFalse(advanced_feature::is_disabled('positions'));
        $this->assertTrue(advanced_feature::is_enabled('positions'));

        advanced_feature::disable('positions');
        $this->assertTrue(advanced_feature::is_disabled('positions'));
        $this->assertFalse(advanced_feature::is_enabled('positions'));
    }

    public function test_enable_unknown_feature() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("'dsfdsfsdf' not supported by Totara feature checking code.");

        advanced_feature::enable('dsfdsfsdf');
    }

    public function test_disable_unknown_feature() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("'dsfdsfsdf' not supported by Totara feature checking code.");

        advanced_feature::disable('dsfdsfsdf');
    }

}
