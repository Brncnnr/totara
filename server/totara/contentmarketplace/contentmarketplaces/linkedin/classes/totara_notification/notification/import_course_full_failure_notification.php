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
namespace contentmarketplace_linkedin\totara_notification\notification;

use contentmarketplace_linkedin\totara_notification\recipient\actor;
use contentmarketplace_linkedin\totara_notification\resolver\import_course_full_failure;
use lang_string;
use totara_notification\notification\built_in_notification;
use totara_notification\schedule\schedule_on_event;

/**
 * Built in notification for event when ALL the courses are failed to be created.
 */
class import_course_full_failure_notification extends built_in_notification {
    /**
     * @return string
     */
    public static function get_resolver_class_name(): string {
        return import_course_full_failure::class;
    }

    /**
     * @return string
     */
    public static function get_title(): string {
        return get_string('import_course_full_failure_title', 'contentmarketplace_linkedin');
    }

    /**
     * @return string
     */
    public static function get_recipient_class_name(): string {
        return actor::class;
    }

    /**
     * @return lang_string
     */
    public static function get_default_body(): lang_string {
        return new lang_string('import_course_full_failure_body', 'contentmarketplace_linkedin');
    }

    /**
     * @return lang_string
     */
    public static function get_default_subject(): lang_string {
        return new lang_string('import_course_full_failure_subject', 'contentmarketplace_linkedin');
    }

    /**
     * @return int
     */
    public static function get_default_schedule_offset(): int {
        return schedule_on_event::default_value();
    }
}