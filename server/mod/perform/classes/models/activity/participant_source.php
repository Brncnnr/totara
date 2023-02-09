<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

/**
 * Convenience enum to represent the participant source.
 */
final class participant_source {
    public const INTERNAL = 0;
    public const EXTERNAL = 1;

    public const TEXT_INTERNAL = 'INTERNAL';
    public const TEXT_EXTERNAL = 'EXTERNAL';

    public const SOURCE_TEXT = [
        self::INTERNAL => self::TEXT_INTERNAL,
        self::EXTERNAL => self::TEXT_EXTERNAL,
    ];

    /**
     * Get all allowed values.
     *
     * @return string[] the allowed values.
     */
    public static function get_allowed(): array {
        return [
            self::INTERNAL,
            self::EXTERNAL
        ];
    }
}
