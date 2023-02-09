<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\totara_notification\resolver;

use contentmarketplace_linkedin\totara_notification\placeholder\catalog_page;
use contentmarketplace_linkedin\totara_notification\recipient\actor;
use lang_string;
use totara_core\extended_context;
use totara_notification\placeholder\placeholder_option;
use totara_notification\resolver\notifiable_event_resolver;

class import_course_full_failure extends notifiable_event_resolver {
    /**
     * @return string
     */
    public static function get_notification_title(): string {
        return get_string('import_course_full_failure_resolver_name', 'contentmarketplace_linkedin');
    }

    /**
     * @return string[]
     */
    public static function get_notification_available_recipients(): array {
        return [actor::class];
    }

    /**
     * @return string[]
     */
    public static function get_notification_default_delivery_channels(): array {
        return ['email', 'popup'];
    }

    /**
     * @return array
     */
    public static function get_notification_available_placeholder_options(): array {
        return [
            placeholder_option::create(
                'catalog_import',
                catalog_page::class,
                new lang_string("catalog_import_page_placeholder_group", "contentmarketplace_linkedin"),
                function (): catalog_page {
                    return new catalog_page();
                }
            )
        ];
    }

    /**
     * @return extended_context
     */
    public function get_extended_context(): extended_context {
        return extended_context::make_system();
    }

    /**
     * @inheritDoc
     */
    public function get_notification_log_display_string_key_and_params(): array {
        return [
            'key' => 'import_course_full_failure_resolver_name',
            'component' => 'contentmarketplace_linkedin',
        ];
    }

}