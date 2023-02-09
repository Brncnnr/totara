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
 * @author  Alastair Munro <alastair.munro@@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\entity;

use core\orm\entity\entity;
use totara_notification\repository\notifiable_event_preference_repository;

/**
 * An entity class that represent for a row of table "ttr_notifiable_event"
 *
 * @property int         $id
 * @property string      $resolver_class_name
 * @property int         $context_id
 * @property string      $component
 * @property string      $area
 * @property int         $item_id
 * @property int         $enabled
 * @property string      $default_delivery_channels
 *
 * @method static notifiable_event_preference_repository repository()
 */
class notifiable_event_preference extends entity {
    /**
     * @var string
     */
    public const TABLE = 'notifiable_event_preference';

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return notifiable_event_preference_repository::class;
    }
}