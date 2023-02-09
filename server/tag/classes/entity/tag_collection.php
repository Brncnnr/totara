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

/**
 * @property string|null    $name
 * @property bool           $isdefault
 * @property string         $component
 * @property int            $sortorder
 * @property bool           $searchable
 * @property string|null    $customurl
 */
final class tag_collection extends entity {
    /**
     * @var string
     */
    public const TABLE = 'tag_coll';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'timemodified';

    /**
     * @var string
     */
    public const UPDATED_TIMESTAMP = 'timemodified';

    /**
     * @param int|bool $value
     * @return bool
     */
    private function parse_boolean($value): bool {
        if (is_bool($value)) {
            return $value;
        }

        return (bool) $value;
    }

    /**
     * @param int|bool $value
     * @return void
     */
    protected function set_isdefault_attribute($value): void {
        if (is_bool($value)) {
            $value = (int) $value;
        } else if (1 != $value && 0 != $value) {
            $value = 0;
        }

        $this->set_attribute_raw('isdefault', $value);
    }

    /**
     * @param int|bool $value
     * @return bool
     */
    protected function get_isdefault_attribute($value): bool {
        return $this->parse_boolean($value);
    }

    /**
     * @param int|bool $value
     * @return void
     */
    protected function set_searchable_attribute($value): void {
        if (is_bool($value)) {
            $value = (int) $value;
        } else if (1 != $value && 0 != $value) {
            $value = 0;
        }

        $this->set_attribute_raw('searchable', $value);
    }

    /**
     * @param int|bool $value
     * @return bool
     */
    protected function get_searchable_attribute($value): bool {
        return $this->parse_boolean($value);
    }
}