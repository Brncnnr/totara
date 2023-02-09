<?php
/**
 * This file is part of Totara Learn
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_tag
 */
namespace core_tag\entity;

use core\orm\entity\entity;
use core_tag\repository\tag_area_repository;

/**
 * @property string         $component
 * @property string         $itemtype       The table of actual instance that want to map with the tag
 * @property bool           $enabled        Whether the area is enabled or not
 * @property int            $tagcollid
 * @property string|null    $callback
 * @property string|null    $callbackfile
 * @property int            $showstandard
 */
final class tag_area extends entity {
    /**
     * @var string
     */
    public const TABLE = 'tag_area';

    /**
     * @param int|bool $value
     * @return void
     */
    protected function set_enabled_attribute($value): void {
        if (is_bool($value)) {
            $value = (int) $value;
        } else if (1 != $value && 0 != $value) {
            // This will be right for all other invalid value like 42 or 322.
            $value = 0;
        }

        $this->set_attribute_raw('enabled', $value);
    }

    /**
     * @param int|bool $value
     * @return bool
     */
    protected function get_enabled_attribute($value): bool {
        if (is_bool($value)) {
            return $value;
        }

        return (bool) $value;
    }

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return tag_area_repository::class;
    }
}