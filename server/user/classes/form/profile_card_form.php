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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_user
 */
namespace core_user\form;

use core_user\profile\display_setting;
use core_user\profile\field\field_helper;
use core_user\profile\field\summary_field_provider;
use totara_form\form;
use totara_form\form\element\checkbox;
use totara_form\form\element\select;

/**
 * Form to set up the profile view card.
 */
final class profile_card_form extends form {
    /**
     * @return void
     */
    protected function definition(): void {
        $provider = new summary_field_provider();
        $user_fields = $provider->get_provide_fields_with_label();

        // Add empty value to this field, then sort the array by key.
        $user_fields[''] = '';
        ksort($user_fields);

        $this->model->add(
            new checkbox(
                'user_picture',
                get_string('profilefielduserpicture', 'admin')
            )
        );

        // Field zero is pretty much required
       $field_zero = $this->model->add(
            new select(
                field_helper::format_position_key(0),
                get_string('profilefieldfieldone', 'admin'),
                $user_fields
            )
        );

       // Requiring field zero to not be empty at all.
       $field_zero->set_attribute('required', true);

        $this->model->add(
            new select(
                field_helper::format_position_key(1),
                get_string('profilefieldfieldtwo', 'admin'),
                $user_fields
            )
        );

        $this->model->add(
            new select(
                field_helper::format_position_key(2),
                get_string('profilefieldfieldthree', 'admin'),
                $user_fields
            )
        );

        $this->model->add(
            new select(
                field_helper::format_position_key(3),
                get_string('profilefieldfieldfour', 'admin'),
                $user_fields
            )
        );

        $this->model->add_action_buttons(false);
    }

    /**
     * Only running check on hidden user fields, if the list is not empty.
     *
     * @param array $data
     * @param array $files
     *
     * @return array
     */
    public function validation(array $data, array $files) {
        global $CFG;

        $errors = [];
        $hidden_user_fields = [];
        $existing = [];

        if (!empty($CFG->hiddenuserfields)) {
            $hidden_user_fields = explode(',', $CFG->hiddenuserfields);
            $hidden_user_fields = array_map('trim', $hidden_user_fields);
        }

        $provider = new summary_field_provider();

        // Loops thru all the fields, and check if there are any duplicated or if the
        // values are appearing in the $hidden_user_fields.
        for ($i = 0; $i < display_setting::MAGIC_NUMBER_OF_DISPLAY_FIELDS; $i++) {
            $name = field_helper::format_position_key($i);

            if (!isset($data[$name])) {
                // Weird stuff.
                debugging("Cannot find field '{$name}' within the form", DEBUG_DEVELOPER);
                continue;
            }

            $field_value = $data[$name];
            if (empty($field_value)) {
                // Skip those empty values.
                continue;
            }

            if (in_array($field_value, $hidden_user_fields)) {
                $errors[$name] = get_string('profilefieldhidden', 'admin', $field_value);
            } else if (in_array($field_value, $existing)) {
                $field_metadata = $provider->get_field_metadata($field_value);
                if (null === $field_metadata) {
                    throw new \coding_exception("Cannot find field '{$field_value}'");
                }

                $errors[$name] = get_string('profilefieldduplication', 'admin', $field_metadata->get_label());
            }

            $existing[] = $field_value;
        }

        return $errors;
    }
}