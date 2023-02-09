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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

use container_perform\perform;
use mod_perform\entity\activity\element_identifier;
use mod_perform\models\activity\element_identifier as element_identifier_model;
use mod_perform\models\activity\element;
use mod_perform\models\activity\section;
use mod_perform\task\cleanup_unused_element_identifiers_task;

/**
 * @group perform
 */
class mod_perform_element_identifier_respository_testcase extends advanced_testcase {

    public function test_element_identifier_repository() {
        $default_context = context_coursecat::instance(perform::get_default_category_id());

        $data_generator = $this->getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        $this->setAdminUser();

        $activity1 = $perform_generator->create_activity_in_container([
            'create_section' => true,
        ]);

        $section1 = $activity1->sections->first();
        $element1 = element::create(
            $default_context,
            'short_text',
            'Title',
            'A1 Element'
        );
        $section1->get_section_element_manager()->add_element_after($element1);

        $activity2 = $perform_generator->create_activity_in_container([
            'create_section' => true,
        ]);

        /** @var section $section2 */
        $section2 = $activity2->sections->first();
        $element2 = element::create(
            $default_context,
            'short_text',
            'Title',
            'A2 Element'
        );
        $section2->get_section_element_manager()->add_element_after($element2);

        // Create an identifier not attached to any elements.
        $unused_element_identifier = element_identifier_model::create(
            'unused identifier'
        );


        // There should be three identifiers.
        $all_identifiers = (element_identifier::repository())->get();
        $this->assertEquals(3, $all_identifiers->count());

        // There should be one and it should match.
        $match = (element_identifier::repository())->filter_by_identifier('A1 Element')->get();
        $this->assertEquals(1, $match->count());
        $this->assertEquals($element1->element_identifier->identifier, ($match->first())->identifier);

        // Should get no results if no identifier matches.
        $match = (element_identifier::repository())->filter_by_identifier('missing identifier')->get();
        $this->assertEquals(0, $match->count());

        // You can request multiple by passing in an array.
        $matches = (element_identifier::repository())->filter_by_identifier(['A1 Element', 'A2 Element'])->get();
        $this->assertEquals(2, $matches->count());

        // Unused identifiers can be found.
        $unused_identifiers = (element_identifier::repository())->filter_by_unused_identifiers()->get();
        $this->assertEquals(1, $unused_identifiers->count());
        $this->assertSame($unused_element_identifier->identifier, ($unused_identifiers->first())->identifier);

        // Can request by identifier id too.
        $match = (element_identifier::repository())->filter_by_identifier_id($unused_element_identifier->id)->get();
        $this->assertEquals(1, $match->count());
        $this->assertSame($unused_element_identifier->identifier, ($match->first())->identifier);

        // Execute cleanup task - this should remove unused identifiers only.
        (new cleanup_unused_element_identifiers_task())
            ->execute();

        // All unused are deleted.
        $unused = (element_identifier::repository())->filter_by_unused_identifiers()->get();
        $this->assertEquals(0, $unused->count());

        // Used are not deleted.
        $all = (element_identifier::repository())->get();
        $this->assertEquals(2, $all->count());

        // Unused was amongst those deleted.
        $match = (element_identifier::repository())->filter_by_identifier('unused identifier')->get();
        $this->assertEquals(0, $match->count());
    }
}