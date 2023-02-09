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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package core
 */

namespace core\formatter;

use core\webapi\formatter\formatter;
use core_user\profile\display_setting;

/**
 * Maps the card_display class into the GraphQL core_user_card_display type.
 */
class user_card_formatter extends formatter {
    /**
     * {@inheritdoc}
     */
    protected function get_map(): array {
        return [
            'profile_picture_url' => null,
            'profile_picture_alt' => null,
            'profile_url' => null,
            'display_fields' => null
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_field(string $field) {
        $show_picture = display_setting::display_user_picture();
        $source = $this->object->get_resolver();

        switch ($field) {
            case 'profile_picture_url':
                return $show_picture
                    ? $source->get_field_value('profileimageurl')
                    : null;

            case 'profile_picture_alt':
                return $show_picture
                    ? $source->get_field_value('profileimagealt')
                    : null;

            case 'profile_url':
                return $source->get_field_value('profileurl');

            case 'display_fields':
                return $this->object->get_card_display_fields();

            default:
                return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function has_field(string $field): bool {
        $recognized = [
            'profile_picture_url',
            'profile_picture_alt',
            'profile_url',
            'display_fields'
        ];

        return in_array($field, $recognized);
    }
}