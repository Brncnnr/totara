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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use core\collection;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\element_plugin;
use mod_perform\models\activity\section;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 * @group perform_element
 */
class performelement_redisplay_webapi_resolver_query_source_activity_question_elements_testcase extends advanced_testcase {

    private const QUERY = 'performelement_redisplay_source_activity_question_elements';

    /**
     * @var activity
     */
    private $activity;

    /**
     * @var string
     */
    private $hidden_section_name = 'section with only non-respondable element';

    use webapi_phpunit_helper;

    public function test_get_question_elements_for_activity_shows_only_sections_with_respondable_elements() {
        $this->create_test_data();

        $result = $this->resolve_graphql_query(self::QUERY, [
            'input' => [
                'activity_id' => $this->activity->id,
            ]
        ]);
        $this->assert_only_sections_with_respondable_elements_are_loaded($result['sections']);

        foreach ($result['sections'] as $section) {
            $this->assertEquals($this->activity->id, (int)$section->activity_id);
            $this->assert_only_respondable_elements_are_loaded($section);
        }
    }

    protected function tearDown(): void {
        $this->activity = null;
        $this->hidden_section_name = null;
    }

    private function assert_only_sections_with_respondable_elements_are_loaded(collection $sections) {
        foreach ($sections as $section) {
            $this->assertNotEquals($this->hidden_section_name, $section->get_display_title());
        }
    }

    private function assert_only_respondable_elements_are_loaded(section $section) {
        $non_respondable_plugins = element_plugin::get_element_plugins(false, true);

        foreach ($section->respondable_section_elements as $section_element) {
            if (in_array($section_element->element->plugin_name, array_keys($non_respondable_plugins), true)) {
                $this->fail('Non-respondable elements listed in redisplay source element options.');
            }
        }
    }

    private function create_test_data() {
        $this->setAdminUser();

        /** @var $perform_generator \mod_perform\testing\generator*/
        $perform_generator = \mod_perform\testing\generator::instance();

        $activity = $perform_generator->create_activity_in_container(['create_section' => false]);
        $this->activity = $activity;

        $section_1 = section::create($activity, 'First section');
        $section_2 = section::create($activity, $this->hidden_section_name);
        $section_3 = section::create($activity, 'Third section');

        $short_text_element_1 = element::create(
            $activity->get_context(),
            'short_text',
            'Projected performance',
            'A2 Element'
        );
        $short_text_element_2 = element::create(
            $activity->get_context(),
            'short_text',
            'Performance analysis',
            'A2 Element'
        );

        $static_content_element_1 = element::create(
            $activity->get_context(),
            'static_content',
            'In between the phases',
            'A2 Element',
            $this->create_static_content_data()
        );
        $static_content_element_2 = element::create(
            $activity->get_context(),
            'static_content',
            'Progress so far',
            'A2 Element',
            $this->create_static_content_data()
        );

        $section_1->get_section_element_manager()->add_element_after($short_text_element_1);
        $section_1->get_section_element_manager()->add_element_after($short_text_element_2);
        $section_1->get_section_element_manager()->add_element_after($static_content_element_1);

        $section_2->get_section_element_manager()->add_element_after($static_content_element_2);

        $section_3->get_section_element_manager()->add_element_after($short_text_element_2);
        $section_3->get_section_element_manager()->add_element_after($static_content_element_2);
    }

    private function create_static_content_data() {
        // First get unused draft id.
        $draft_id = file_get_unused_draft_itemid();

        // Create a file in draft area.
        $data['wekaDoc'] = json_encode([
            'type' => 'doc',
            'content' => []
        ]);
        $data['docFormat'] = 'FORMAT_JSON_EDITOR';
        $data['format'] = 'HTML';
        $data['draftId'] = $draft_id;

        return json_encode($data);
    }
}