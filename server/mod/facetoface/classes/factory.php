<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

use coding_exception;
use core\orm\query\builder;
use stdClass;

/**
 * The factory class.
 */
class factory {
    /** @var string[] */
    private static $db_tables = [
        'asset' => asset::DBTABLE,
        'facilitator' => facilitator::DBTABLE,
        'room' => room::DBTABLE,
    ];

    /** @var string[] */
    private static $list_classes = [
        'asset' => asset_list::class,
        'facilitator' => facilitator_list::class,
        'room' => room_list::class,
    ];

    /** @var string[] */
    private static $item_classes = [
        'asset' => asset::class,
        'facilitator' => facilitator_user::class,
        'room' => room::class,
    ];

    /**
     * Create a builder class.
     *
     * @param string $type asset, facilitator or room
     * @param string|null $as
     * @return builder
     */
    public static function create_resource_builder(string $type, ?string $as = null): builder {
        if (!isset(self::$db_tables[$type])) {
            throw new coding_exception('Unknown resource type: '.$type);
        }
        $dbtable = self::$db_tables[$type];
        return builder::table($dbtable, $as);
    }

    /**
     * Instantiate a list class with no items.
     *
     * @param string $type asset, facilitator or room
     * @return asset_list|facilitator_list|room_list cast to (asset|facilitator|room)_list
     */
    public static function create_resource_list(string $type) {
        if (!isset(self::$list_classes[$type])) {
            throw new coding_exception('Unknown resource type: '.$type);
        }
        // The room_list class has a different constructor.
        if ($type === 'room') {
            return new room_list('SELECT 1 FROM {facetoface_room} WHERE 1 != 1');
        } else {
            $listclass = self::$list_classes[$type];
            return new $listclass();
        }
    }

    /**
     * Instantiate a resource class.
     *
     * @param string $type asset, facilitator or room
     * @param stdClass $record
     * @return asset|facilitator_user|room cast to asset, facilitator_user or room
     */
    public static function create_resource_list_item_from_record(string $type, stdClass $record) {
        if (!isset(self::$item_classes[$type])) {
            throw new coding_exception('Unknown resource type: '.$type);
        }
        // The facilitator_list class requires facilitator_user instead of facilitator.
        if ($type === 'facilitator') {
            $fac = new facilitator();
            $fac->from_record($record);
            $item = new facilitator_user($fac);
        } else {
            $class = self::$item_classes[$type];
            $item = new $class();
            $item->from_record($record);
        }
        return $item;
    }
}
