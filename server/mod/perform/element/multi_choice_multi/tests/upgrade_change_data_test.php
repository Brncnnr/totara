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

use mod_perform\models\activity\element;
use container_perform\perform;

require_once(__DIR__ . '/../db/upgradelib.php');

/**
 * @group perform
 * @group perform_element
 */
class mod_perform_upgrade_change_data_test_testcase extends advanced_testcase {

    public function test_change_data(): void {
        $default_context = context_coursecat::instance(perform::get_default_category_id());
        $data_2 = [
            'options'  => [['name' => 'key1', 'value' => 1], ['name' => 'key2', 'value' => 2]],
            'settings' => [['name' => 'min', 'value' => 1], ['name' => 'max', 'value' => 2]],
        ];
        $data_3 = [
            'options'  => [['name' => 'key1', 'value' => 1], ['name' => 'key2', 'value' => 2]],
            'min' => 1,
            'max' => 2,
        ];
        $element1 = $element = element::create(
            $default_context,
            'short_text',
            'short_text',
            'AAA',
            null,
            true
        );
        $element2 = $element = element::create(
            $default_context,
            'multi_choice_multi',
            'multi choice multi two',
            'BBB',
            json_encode($data_2),
            true
        );

        $element3 = $element = element::create(
            $default_context,
            'multi_choice_multi',
            'multi choice multi three',
            'BBB',
            json_encode($data_3),
            true
        );

        performelement_multi_choice_multi_change_data();
        $update_element2 = element::load_by_id($element2->id);
        $update_element2_data = json_decode($update_element2->data, true);
        $this->assertNotContains('settings', $update_element2_data);
        $this->assertArrayHasKey('min', $update_element2_data);
        $this->assertArrayHasKey('max', $update_element2_data);

        $update_element3 = element::load_by_id($element3->id);
        $update_element3_data = json_decode($update_element3->data, true);
        $this->assertNotContains('settings', $update_element3_data);
        $this->assertArrayHasKey('min', $update_element3_data);
        $this->assertArrayHasKey('max', $update_element3_data);
    }
}