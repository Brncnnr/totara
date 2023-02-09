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
namespace core_user\output;

use core\output\template;
use core_user\profile\display_setting;
use core_user\profile\field\summary_field_provider;
use core_user\profile\user_field_resolver;

/**
 * User profile card component
 */
final class profile_card extends template {
    /**
     * @param \stdClass $target_user_record
     * @param int|null  $course_id
     *
     * @return profile_card
     */
    public static function create(\stdClass $target_user_record, ?int $course_id = null): profile_card {
        $resolver = user_field_resolver::from_record($target_user_record, $course_id);
        $data = [
            'profile_picture_url' => null,
            'profile_picture_alt' => null,
            'profile_url' => $resolver->get_field_value('profileurl'),
            'fields' => [],
        ];

        if (display_setting::display_user_picture()) {
            $data['profile_picture_url'] = $resolver->get_field_value('profileimageurl');
            $data['profile_picture_alt'] = $resolver->get_field_value('profileimagealt');
        }

        $provider = new summary_field_provider();

        // Getting display fields and reset it to normal indexes.
        $display_fields = display_setting::get_display_fields();
        $display_fields = array_values($display_fields);

        foreach ($display_fields as $i => $display_field) {
            if (null === $display_field) {
                $data['fields'][] = [
                    'value' => null,
                    'associate_url' => null,
                    'is_title' => false
                ];

                continue;
            }

            $field_metadata = $provider->get_field_metadata($display_field);

            $value = null;
            $associate_url = null;

            if ($field_metadata->is_custom_field()) {
                $value = $resolver->get_custom_field_value($field_metadata->get_original_key_value());
            } else {
                $value = $resolver->get_field_value($field_metadata->get_key());
            }

            $url_field = $field_metadata->get_associate_url_field();
            if (null !== $url_field && 'fullname' !== $display_field) {
                // Skip the associate url for `fullname` field for now.
                // As this card is being used within profile page, and the associate url
                // field for fullname is pointing to the same profile page.
                // Hence there is no point. include the url.
                $associate_url = $resolver->get_field_value($url_field);
            }

            $data['fields'][] = [
                'value' => $value,
                'associate_url' => $associate_url,
                'is_title' => 0 === $i
            ];
        }

        return new static($data);
    }
}
