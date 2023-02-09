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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver\mutation;

use core\theme\settings as theme_settings;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\middleware\require_theme_settings;
use core\webapi\mutation_resolver;

/**
 * Mutation to update theme settings for a specific theme.
 */
class theme_settings_send_email_notification extends mutation_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        global $USER;

        $categories = [
            [
                'name' => 'brand',
                'properties' => [
                    [
                        'name' => 'formbrand_field_notificationshtmlheader',
                        'type' => 'html',
                        'value' => $args['html_header'] ?? '',
                    ],
                    [
                        'name' => 'formbrand_field_notificationshtmlfooter',
                        'type' => 'html',
                        'value' => $args['html_footer'] ?? '',
                    ],
                    [
                        'name' => 'formbrand_field_notificationstextfooter',
                        'type' => 'text',
                        'value' => $args['text_footer'] ?? '',
                    ],
                ],
            ]
        ];

        theme_settings::set_adhoc_categories($categories);
        $mail_format = $USER->mailformat;

        // Send HTML email.
        $USER->mailformat = 1;
        $html_email = email_to_user(
            $USER,
            $USER,
            get_string('test_email_notification_subject', 'totara_core'),
            get_string('test_email_notification_message', 'totara_core'),
        );

        // Send plain-text email.
        $USER->mailformat = 0;
        $text_email = email_to_user(
            $USER,
            $USER,
            get_string('test_email_notification_subject', 'totara_core'),
            get_string('test_email_notification_message', 'totara_core'),
        );

        // Restore user's mail format.
        $USER->mailformat = $mail_format;

        return $html_email && $text_email;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_theme_settings('tenant_id'),
        ];
    }
}
