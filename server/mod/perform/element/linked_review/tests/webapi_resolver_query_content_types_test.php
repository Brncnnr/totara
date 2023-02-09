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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_linked_review
 */

use mod_perform\testing\generator as perform_generator;
use performelement_linked_review\content_type;
use performelement_linked_review\content_type_factory;
use totara_competency\performelement_linked_review\competency_assignment;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 * @group perform_element
 */
class performelement_linked_review_query_content_types_testcase extends advanced_testcase {

    private const QUERY = 'performelement_linked_review_content_types';

    private const TYPE = 'performelement_linked_review_content_type';

    use webapi_phpunit_helper;

    public function test_resolve_query_successful(): void {
        self::setAdminUser();
        $activity = perform_generator::instance()->create_activity_in_container();
        $section = perform_generator::instance()->find_or_create_section($activity);

        $result = $this->resolve_graphql_query(self::QUERY, ['section_id' => $section->id]);
        $this->assertEquals(content_type_factory::get_all_enabled(), $result);
    }

    public function test_resolve_type_successful(): void {
        if (!class_exists('\totara_competency\performelement_linked_review\competency_assignment')) {
            $this->markTestSkipped('Test requires totara_competency');
        }

        self::setAdminUser();
        $activity = perform_generator::instance()->create_activity_in_container();

        /** @var content_type|string $competency_type */
        $competency_type = competency_assignment::class;

        $value_map = [
            'identifier' => $competency_type::get_identifier(),
            'display_name' => $competency_type::get_display_name(),
            'is_enabled' => $competency_type::is_enabled(),
            'available_settings' => json_encode($competency_type::get_available_settings()),
            'admin_settings_component' => $competency_type::get_admin_settings_component(),
            'admin_view_component' => $competency_type::get_admin_view_component(),
            'content_picker_component' => $competency_type::get_content_picker_component(),
            'participant_content_component' => $competency_type::get_participant_content_component(),
        ];

        foreach ($value_map as $field_name => $expected_value) {
            $this->assertEquals(
                $expected_value,
                $this->resolve_graphql_type(self::TYPE, $field_name, $competency_type, [], $activity->get_context())
            );
        }

        $this->expectException(invalid_parameter_exception::class);
        $this->resolve_graphql_type(self::TYPE, 'not a field', $competency_type);
    }

    public function test_feature_disabled(): void {
        advanced_feature::disable('performance_activities');
        self::setAdminUser();

        $this->expectException(feature_not_available_exception::class);
        $this->expectExceptionMessage('Feature performance_activities is not available.');

        $this->resolve_graphql_query(self::QUERY);
    }

    public function test_require_login(): void {
        $this->expectException(require_login_exception::class);
        $this->resolve_graphql_query(self::QUERY);
    }

    public function test_require_manage_activity_capability(): void {
        self::setAdminUser();
        $activity = perform_generator::instance()->create_activity_in_container();
        $section = perform_generator::instance()->find_or_create_section($activity);

        $user = self::getDataGenerator()->create_user();
        self::setUser($user);

        $this->expectException(moodle_exception::class);
        $this->resolve_graphql_query(self::QUERY, ['section_id' => $section->id]);
    }

}
