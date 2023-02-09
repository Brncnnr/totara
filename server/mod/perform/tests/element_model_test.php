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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */
use container_perform\perform;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\models\activity\element;

require_once(__DIR__ . '/child_element_manager_testcase.php');

/**
 * @group perform
 */
class mod_perform_element_model_testcase extends child_element_manager_testcase {

    public function test_create() {
        $default_context = context_coursecat::instance(perform::get_default_category_id());

        $element = element::create(
            $default_context,
            'short_text',
            'test element 1 title',
            'test identifier',
            null,
            true
        );

        // Reload, just to make sure that we're getting it out of the DB.
        /** @var element $actual_element */
        $element_model = element::load_by_id($element->id);

        $this->assertSame('short_text', $element_model->plugin_name);
        $this->assertSame('test element 1 title', $element_model->title);
        $this->assertSame('test identifier', $element_model->identifier);
        $this->assertTrue($element_model->is_required);
    }

    public function validation_data_provider() {
        return [
            ['multi_choice_single', 'Test-ID'],
            ['short_text', 'Test-ID'],
            ['multi_choice_single', ''],
            ['multi_choice_single', '', 'short_text', 'Test-ID'],
            ['short_text', ''],
        ];
    }

    /**
     * @dataProvider validation_data_provider
     * @param string $plugin1
     * @param string $id1
     */
    public function test_validate(string $plugin1, string $id1) {
        $default_context = context_coursecat::instance(perform::get_default_category_id());

        element::create(
            $default_context,
            $plugin1,
            'test title',
            $id1,
            null,
            true
        );
    }

    public function test_deleting_parent_element_deletes_child_elements() {
        $data = $this->generate_data();
        /** @var element $parent */
        $parent = $data['parent'];
        $elements = element_entity::repository()->get();
        $this->assertEquals(4, $elements->count());
        $parent->delete();

        $elements = element_entity::repository()->get();
        $this->assertEquals(0, $elements->count());
    }
}