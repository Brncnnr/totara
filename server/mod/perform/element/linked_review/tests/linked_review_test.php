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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package performelement_linked_review
 */

use mod_perform\constants;
use mod_perform\models\activity\element;
use performelement_linked_review\testing\generator as linked_review_generator;
use totara_core\relationship\relationship;

/**
 * @group perform
 * @group perform_element
 */
class performelement_linked_review_linked_review_testcase extends advanced_testcase {

    public function test_clean_and_validate() {
        self::setAdminUser();
        [$activity] = linked_review_generator::instance()->create_activity_with_section_and_review_element();

        $element2 = element::create($activity->get_context(), 'linked_review', 'title', '', json_encode([
            'content_type' => 'totara_competency',
            'content_type_settings' => [],
            'selection_relationships' => [relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT)->id],
            'components' => 'ishouldnotbehere',
            'selection_relationships_display' => 'me_neither',
            'content_type_display' => 'me_neither2',
            'content_type_settings_display' => 'me_neither3',
        ]));

        $data = json_decode($element2->get_data(), true);

        $this->assertEqualsCanonicalizing(
            [
                'content_type',
                'content_type_settings',
                'selection_relationships',
                'components',
                'selection_relationships_display',
                'compatible_child_element_plugins',
                'content_type_display',
                'content_type_settings_display',
            ],
            array_keys($data)
        );
        $this->assertNotEquals('ishouldnotbehere', $data['components']);
        $this->assertNotEquals('me_neither', $data['selection_relationships_display']);
        $this->assertNotEquals('me_neither2', $data['content_type_display']);
        $this->assertNotEquals('me_neither3', $data['content_type_settings_display']);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The saved data must contain and only contain these keys:');

        element::create($activity->get_context(), 'linked_review', 'title', '', json_encode([
            'content_type' => 'totara_competency',
            'content_type_settings' => [],
            'selection_relationships' => [relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT)->id],
            'ishouldnotbehere' => 'invalidvalue',
        ]));
    }

}
