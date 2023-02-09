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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_engage
 */

use core\webapi\execution_context;
use totara_core\advanced_feature;
use totara_webapi\graphql;

defined('MOODLE_INTERNAL') || die();

/**
 * Testing the advanced features graphql endpoint for engage
 */
class totara_engage_advanced_features_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_feature_toggles(): void {
        $features = [
            'workspaces' => 'container_workspace',
            'recommenders' => 'ml_recommender',
            'library' => 'engage_resources',
        ];

        // Test each feature
        $ec = execution_context::create('ajax', 'totara_engage_advanced_features');
        foreach ($features as $key => $feature) {
            // Check that it's on
            advanced_feature::enable($feature);
            $result = graphql::execute_operation($ec, []);

            $this->assertNotNull($result->data);
            $this->assertArrayHasKey('features', $result->data);
            $this->assertArrayHasKey($key, $result->data['features']);
            $this->assertTrue($result->data['features'][$key]);

            // Check that it's off
            advanced_feature::disable($feature);
            $result = graphql::execute_operation($ec, []);

            $this->assertNotNull($result->data);
            $this->assertArrayHasKey('features', $result->data);
            $this->assertArrayHasKey($key, $result->data['features']);
            $this->assertFalse($result->data['features'][$key]);
        }
    }
}