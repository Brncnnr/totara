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

use mod_perform\models\activity\activity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\helpers\section_element_manager;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_element;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\entity\activity\section as section_entity;
use mod_perform\state\activity\draft;
use performelement_redisplay\redisplay;

/**
 * @group perform
 * @group perform_element
 */
class performelement_redisplay_redisplay_data_testcase extends advanced_testcase {

    /**
     * @var activity
    */
    private $activity;

    /**
     * @var array
     */
    private $redisplay_extra_data = [
        'activityName',
        'activityStatus',
        'elementTitle',
        'elementPluginName',
        'relationships',
        redisplay::SOURCE_SECTION_ELEMENT_ID,
    ];

    public function test_redisplay_element_adds_extra_info_to_data(): void {
        $data = $this->create_test_data();

        /** @var $other_section_element section_element*/
        $other_section_element = $data['other'];
        self::assertNull($other_section_element->element->data);

        /** @var $redisplay_section_element section_element*/
        $redisplay_section_element = $data['redisplay'];
        $redisplay_data = json_decode($redisplay_section_element->element->get_data(), true, 512, JSON_THROW_ON_ERROR);


        /* @type section_element_entity $redisplay_section_element_entity */
        $redisplay_section_element_entity = section_element_entity::repository()->find_or_fail($redisplay_section_element->id);
        self::assertNull($redisplay_section_element_entity->element->data, 'Element should not save any json data (all data is in reference table)');

        $this->assert_extra_fields_exist($redisplay_data);

        self::assertEquals($this->activity->name, $redisplay_data['activityName']);
        self::assertEquals(draft::get_display_name(), $redisplay_data['activityStatus']);
        self::assertEquals($other_section_element->id, $redisplay_data[redisplay::SOURCE_SECTION_ELEMENT_ID]);
        self::assertEquals('Projected performance', $redisplay_data['elementTitle']);
        self::assertNotEmpty($redisplay_data['elementPluginName']);
        self::assertStringContainsString('No responding relationships added yet', $redisplay_data['relationships']);

        $this->assert_relationship_string_is_anonymized($redisplay_section_element);
    }

    private function assert_extra_fields_exist(array $redisplay_data): void {
        foreach ($this->redisplay_extra_data as $redisplay_extra_datum) {
            self::assertArrayHasKey($redisplay_extra_datum, $redisplay_data);
        }
    }

    private function assert_relationship_string_is_anonymized(section_element $redisplay_section_element): void {
        $this->activity->set_anonymous_setting(true);
        $this->activity->update();

        $redisplay_data = json_decode($redisplay_section_element->element->get_data(), true, 512, JSON_THROW_ON_ERROR);
        $this->assert_extra_fields_exist($redisplay_data);
        self::assertEquals(
            get_string('responses_from_anonymous_relationships', 'performelement_redisplay'),
            $redisplay_data['relationships']
        );
    }

    public function tearDown(): void {
        $this->activity = null;
        $this->redisplay_extra_data = null;
    }

    private function create_test_data(): array {
        self::setAdminUser();

        $perform_generator = \mod_perform\testing\generator::instance();

        $this->activity = $perform_generator->create_activity_in_container(
            [
                'create_section' => false,
                'activity_name' => 'My redisplay test activity',
                'activity_status' => draft::get_code()
            ]
        );

        $section_1 = section::create($this->activity, 'First section');

        $short_text_element_1 = element::create(
            $this->activity->get_context(),
            'short_text',
            'Projected performance',
            'A2 Element'
        );

        /** @var section_entity $section1_entity */
        $section1_entity = section_entity::repository()->find($section_1->get_id());
        $section1_element_manager = new section_element_manager($section1_entity);
        $section_element_1 = $section1_element_manager->add_element_after($short_text_element_1);

        $redisplay_element = $this->get_redisplay_element($section_element_1->id, 'Performance analysis');

        $redisplay_section_element = $section1_element_manager->add_element_after(
            $redisplay_element, $short_text_element_1->get_id()
        );

        return [
            'redisplay' => $redisplay_section_element,
            'other' => $section_element_1,
        ];
    }

    private function get_redisplay_element($section_element_id, $name): element {
        return element::create(
            $this->activity->get_context(),
            'redisplay',
            $name,
            'A2 Element',
            json_encode([
                redisplay::SOURCE_SECTION_ELEMENT_ID => $section_element_id,
            ], JSON_THROW_ON_ERROR)
        );
    }
}