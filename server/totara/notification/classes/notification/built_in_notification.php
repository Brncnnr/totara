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
 * @author  Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\notification;

use coding_exception;
use lang_string;
use totara_notification\local\helper;

/**
 * A static class that define the built-in configuration for the notification(s), which are belonging
 * to the notifiable event. Many to one relationship.
 * This class is for developer to define the default notification setting out of the box.
 */
abstract class built_in_notification {
    /**
     * Returning the event resolver class name which this notification is belonging to.
     * It is a one-to-many relationship, meaning that one event can produce multiple
     * notifications (like the children of this one).
     *
     * @return string
     */
    abstract public static function get_resolver_class_name(): string;

    /**
     * Returning the notification's title.
     * Note this does not use any lang_string because we don't need to do sort
     * of placeholders for the title of the built in notification.
     *
     * Please do not use placeholders with title. It has to be a static data, and must
     * come from the language pack.
     *
     * @return string
     */
    abstract public static function get_title(): string;

    /**
     * Returns the notification's additional_criteria, if required.
     *
     * @return string|null json encoded
     */
    public static function get_additional_criteria(): ?string {
        return null;
    }

    /**
     * Return the recipient class name.
     *
     * @return string
     */
    abstract public static function get_recipient_class_name(): string;

    /**
     * Return the recipient class names.
     *
     * @return string[]
     */
    public static function get_recipient_class_names(): array {
        return [static::get_recipient_class_name()];
    }

    /**
     * @return lang_string
     */
    abstract public static function get_default_body(): lang_string;

    /**
     * @return lang_string
     */
    abstract public static function get_default_subject(): lang_string;

    /**
     * Returns the schedule offset value, translated for storage.
     * Note: it must be in seconds unit.
     *
     * @return int
     */
    abstract public static function get_default_schedule_offset(): int;

    /**
     * Returns the list of delivery channels by its component.
     * By default nothing is forced. Extends this function at lower
     * child class to have its own forced delivery channels.
     *
     * @return string[]
     */
    public static function get_default_forced_delivery_channels(): array {
        return [];
    }

    /**
     * The function should only return the following values:
     * + @see FORMAT_MOODLE
     * + @see FORMAT_HTML
     * + @see FORMAT_PLAIN
     * + @see FORMAT_MARKDOWN
     * + @see FORMAT_JSON_EDITOR
     *
     * We default to FORMAT_JSON_EDITOR.
     * @return int
     */
    public static function get_default_body_format(): int {
        return helper::get_preferred_editor_format(FORMAT_JSON_EDITOR);
    }

    /**
     * The function should only return the following values:
     * + @see FORMAT_MOODLE
     * + @see FORMAT_HTML
     * + @see FORMAT_PLAIN
     * + @see FORMAT_MARKDOWN
     * + @see FORMAT_JSON_EDITOR
     *
     * We default to FORMAT_JSON_EDITOR.
     * @return int
     */
    public static function get_default_subject_format(): int {
        return helper::get_preferred_editor_format(FORMAT_JSON_EDITOR);
    }

    /**
     * @return bool
     */
    public static function get_default_enabled(): bool {
        return true;
    }
}