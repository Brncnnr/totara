<?php
/*
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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\models\activity\element_plugin;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\element_plugins
 *
 * @group perform
 */
class mod_perform_webapi_resolver_query_element_plugins_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_element_plugins';

    use webapi_phpunit_helper;

    public function test_get_element_plugins() {
        $this->setAdminUser();

        $element_plugins = $this->resolve_graphql_query(self::QUERY);
        foreach ($element_plugins as $element_plugin) {
            $this->assertInstanceOf(element_plugin::class, $element_plugin);
            $this->assertNotEmpty($element_plugin->get_plugin_name());
            $this->assertNotEmpty($element_plugin->get_name());
            $this->assertNotEmpty($element_plugin->get_admin_edit_component());
            $this->assertNotEmpty($element_plugin->get_admin_view_component());
            $this->assertNotEmpty($element_plugin->get_admin_summary_component());
            $this->assertNotEmpty($element_plugin->get_participant_form_component());
        }
    }

    public function test_successful_ajax_call(): void {
        $this->setAdminUser();
        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_successful($result);

        $element_plugins = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($element_plugins, "no element plugins");
        foreach ($element_plugins as $element_plugin) {
            $this->assertNotEmpty($element_plugin['plugin_name']);
            $this->assertNotEmpty($element_plugin['name']);
            $this->assertNotEmpty($element_plugin['plugin_config']);
            $this->assertIsBool($element_plugin['plugin_config']['has_title']);
            $this->assertIsBool($element_plugin['plugin_config']['is_respondable']);
            $this->assertIsBool($element_plugin['plugin_config']['has_reporting_id']);
            $this->assertNotEmpty($element_plugin['plugin_config']['title_text']);
            $this->assertIsBool($element_plugin['plugin_config']['is_title_required']);
            $this->assertIsBool($element_plugin['plugin_config']['is_response_required_enabled']);
            $this->assertNotEmpty($element_plugin['admin_edit_component']);
            $this->assertNotEmpty($element_plugin['admin_view_component']);
            $this->assertNotEmpty($element_plugin['admin_summary_component']);
            $this->assertNotEmpty($element_plugin['participant_form_component']);
        }
    }

    public function test_failed_ajax_query(): void {
        $this->setAdminUser();
        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $this->setUser();
        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($result, 'not logged in');
    }
}
