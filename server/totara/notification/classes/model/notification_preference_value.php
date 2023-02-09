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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\model;

use coding_exception;
use lang_string;
use totara_notification\local\helper;
use totara_notification\notification\abstraction\additional_criteria_notification;
use totara_notification\notification\built_in_notification;

/**
 * A data holder class that is used to transfer data down
 * to graphql type resolver.
 */
class notification_preference_value {
    /**
     * @var string
     */
    private $body;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $additional_criteria;

    /**
     * @var string
     */
    private $title;

    /**
     * @var int
     */
    private $schedule_offset;

    /**
     * @var string
     * @depreated Since Totara 17.0
     */
    private $recipient;

    /**
     * @var array
     */
    private $recipients;

    /**
     * @var int
     */
    private $body_format;

    /**
     * @var int
     */
    private $subject_format;

    /**
     * @var bool|null
     */
    private $enabled;

    /**
     * The array of delivery channel identifiers.
     * @var string[]
     */
    private $forced_delivery_channels;

    /**
     * notification_preference_value constructor.
     * @param string   $body
     * @param string   $subject
     * @param string|null $additional_criteria
     * @param string   $title
     * @param int      $schedule_offset
     * @param string   $recipient
     * @param int|null $body_format
     * @param int|null $subject_format
     * @param bool|null $enabled
     * @param array    $forced_delivery_channels
     * @param array   $recipients
     *      Since Totara 17.0
     */
    private function __construct(
        string $body,
        string $subject,
        ?string $additional_criteria,
        string $title,
        int $schedule_offset,
        string $recipient,
        ?int $body_format = null,
        ?int $subject_format = null,
        ?bool $enabled = null,
        array $forced_delivery_channels = [],
        array $recipients = []
    ) {
        $this->body = $body;
        $this->subject = $subject;
        $this->additional_criteria = $additional_criteria;
        $this->title = $title;
        $this->schedule_offset = $schedule_offset;
        $this->recipient = $recipient;
        $this->body_format = $body_format ?? FORMAT_MOODLE;
        $this->subject_format = $subject_format ?? FORMAT_JSON_EDITOR;
        $this->enabled = $enabled;
        $this->forced_delivery_channels = $forced_delivery_channels;
        $this->recipients = $recipients;
    }

    /**
     * @param string $built_in_class_name
     * @return notification_preference_value
     */
    public static function from_built_in_notification(string $built_in_class_name): notification_preference_value {
        if (!helper::is_valid_built_in_notification($built_in_class_name)) {
            throw new coding_exception("Invalid built-in notification class name '{$built_in_class_name}'");
        }

        /**
         * @see built_in_notification::get_default_body()
         * @see built_in_notification::get_default_subject()
         * @see built_in_notification::get_default_additional_criteria()
         * @see built_in_notification::get_title()
         * @see built_in_notification::get_default_body_format()
         * @see built_in_notification::get_default_subject_format()
         * @see built_in_notification::get_default_schedule_offset()
         * @see built_in_notification::get_recipient_class_name()
         * @see built_in_notification::get_default_enabled()
         * @see built_in_notification::get_default_forced_delivery_channels()
         * @see built_in_notification::get_recipient_class_names()
         *
         * @var string      $built_in_class_name
         * @var lang_string $body
         * @var lang_string $subject
         * @var string      $title
         * @var int         $body_format
         * @var bool        $enabled
         */
        /** @var built_in_notification $built_in_class_name */
        $body = $built_in_class_name::get_default_body();
        $subject = $built_in_class_name::get_default_subject();
        $title = $built_in_class_name::get_title();
        $body_format = $built_in_class_name::get_default_body_format();
        $schedule_offset = $built_in_class_name::get_default_schedule_offset();
        $recipient = $built_in_class_name::get_recipient_class_name();
        $subject_format = $built_in_class_name::get_default_subject_format();
        $enabled = $built_in_class_name::get_default_enabled();
        $recipients = $built_in_class_name::get_recipient_class_names();

        if (is_a($built_in_class_name, additional_criteria_notification::class, true)) {
            $additional_criteria = $built_in_class_name::get_default_additional_criteria();
        } else {
            $additional_criteria = null;
        }

        return new static(
            $body,
            $subject,
            $additional_criteria,
            $title,
            $schedule_offset,
            $recipient,
            $body_format,
            $subject_format,
            $enabled,
            [],
            $recipients
        );
    }

    /**
     * Please note that the $model that you are passing down to this function
     * is the parent model.
     *
     * @param notification_preference $model
     * @return notification_preference_value
     */
    public static function from_parent_notification_preference(notification_preference $model): notification_preference_value {
        $recipients = $model->get_recipients();
        $recipient = !empty($recipients) ? reset($recipients) : '';
        return new static(
            $model->get_body(),
            $model->get_subject(),
            $model->get_additional_criteria(),
            $model->get_title(),
            $model->get_schedule_offset(),
            $recipient,
            $model->get_body_format(),
            $model->get_subject_format(),
            $model->get_enabled(),
            $model->get_forced_delivery_channels(),
            $recipients
        );
    }

    /**
     * @return string
     */
    public function get_body(): string {
        return $this->body;
    }

    /**
     * @return string|null json encoded
     */
    public function get_additional_criteria(): ?string {
        return $this->additional_criteria;
    }

    /**
     * @return string
     */
    public function get_title(): string {
        return $this->title;
    }

    /**
     * @return string
     */
    public function get_subject(): string {
        return $this->subject;
    }

    /**
     * @return int
     */
    public function get_body_format(): int {
        return $this->body_format;
    }

    /**
     * @return int
     */
    public function get_scheduled_offset(): int {
        return $this->schedule_offset;
    }

    /**
     * @return int
     */
    public function get_subject_format(): int {
        return $this->subject_format;
    }

    /**
     * @deprecated Since Totara 17.0
     * @return string
     */
    public function get_recipient(): string {
        debugging('Do not use notification_preference_value::get_recipient any more, use notification_preference_value::get_recipients instead', DEBUG_DEVELOPER);
        return $this->recipient;
    }

    /**
     * @return bool
     */
    public function get_enabled(): ?bool {
        return $this->enabled;
    }

    /**
     * @return string[]
     */
    public function get_forced_delivery_channels(): array {
        return $this->forced_delivery_channels;
    }

    /**
     * @return array
     */
    public function get_recipients(): array {
        return $this->recipients;
    }
}