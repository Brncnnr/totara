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
namespace totara_notification\entity;

use core\orm\entity\entity;
use totara_core\extended_context;
use totara_notification\repository\notification_preference_repository;

/**
 * An entity class that represent for a row of table "ttr_notification_preference"
 *
 * @property int         $id
 * @property int|null    $ancestor_id
 * @property string      $resolver_class_name
 * @property string      $notification_class_name
 * @property int         $context_id
 * @property string      $component
 * @property string      $area
 * @property int         $item_id
 * @property string|null $title
 * @property string|null $additional_criteria json encoded
 * @property string|null $recipient
 * @property string|null $subject
 * @property string|null $subject_format
 * @property string|null $body
 * @property int|null    $body_format
 * @property int         $time_created
 * @property int         $schedule_offset
 * @property bool|null   $enabled
 * @property string|null $forced_delivery_channels
 * @property array|null $recipients
 * @property string|null $subject_backup
 * @property string|null $body_backup
 *
 * @method static notification_preference_repository repository()
 */
class notification_preference extends entity {
    /**
     * @var string
     */
    public const TABLE = 'notification_preference';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'time_created';

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return notification_preference_repository::class;
    }

    /**
     * @return extended_context
     */
    public function get_extended_context(): extended_context {
        return extended_context::make_with_id(
            $this->context_id,
            $this->component,
            $this->area,
            $this->item_id
        );
    }

    /**
     * @param extended_context $extended_context
     * @return void
     */
    public function set_extended_context(extended_context $extended_context): void {
        $this->set_attribute('context_id', $extended_context->get_context_id());
        $this->set_attribute('component', $extended_context->get_component());
        $this->set_attribute('area', $extended_context->get_area());
        $this->set_attribute('item_id', $extended_context->get_item_id());
    }

    /**
     * Given the array of channel's identifier names. The function encodes that
     * array of strings into a json string and save it to the field to be stored
     * on the database.
     *
     * @param string[] $channels
     * @return void
     */
    public function set_decoded_forced_delivery_channels(array $channels): void {
        $json_string = json_encode($channels, JSON_THROW_ON_ERROR);
        $this->set_attribute_raw('forced_delivery_channels', $json_string);
    }

    /**
     * Return encoded json string of array of channel's identifier names .
     * @return string[]
     */
    public function get_decoded_forced_delivery_channels(): array {
        if (empty($this->forced_delivery_channels)) {
            return [];
        }

        return json_decode(
            $this->forced_delivery_channels,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}