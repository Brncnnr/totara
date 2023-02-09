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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\notification;

use coding_exception;
use core\orm\query\builder;
use mod_perform\constants;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\relationship;

/**
 * The recipient types and helper functions.
 */
final class recipient {
    /** core relationships such as subject, manager, appraiser */
    public const STANDARD = 1;

    /** manual relationships such as peer, mentor, reviewer, excluding external relationship */
    public const MANUAL = 2;

    /** external relationship */
    public const EXTERNAL = 4;

    /** all supported relationships */
    public const ALL = self::STANDARD | self::MANUAL | self::EXTERNAL;

    // external  manual  standard   conditions
    // --------  ------  --------   ----------
    //     -       -        -       INVALID
    //     -       -        x       r.type = STANDARD AND r.idnumber != 'external'
    //     -       x        -       r.type = MANUAL AND r.idnumber != 'external'
    //     -       x        x       r.type in (STANDARD, MANUAL) AND r.idnumber != 'external'
    //     x       -        -       r.idnumber = 'external'
    //     x       -        x       r.type = STANDARD OR r.idnumber = 'external'
    //     x       x        -       r.type = MANUAL OR r.idnumber = 'external'
    //     x       x        x       r.type in (STANDARD, MANUAL)

    /** @var array<integer, integer> */
    private static $relationship_types = [
        self::STANDARD => relationship_entity::TYPE_STANDARD,
        self::MANUAL => relationship_entity::TYPE_MANUAL,
        // EXTERNAL is not defined here.
    ];

    /**
     * @param integer $recipients
     * @return boolean
     */
    private static function is_internal_only(int $recipients): bool {
        return ($recipients & self::EXTERNAL) !== self::EXTERNAL;
    }

    /**
     * @param integer $recipients
     * @return boolean
     */
    private static function is_external_only(int $recipients): bool {
        return $recipients === self::EXTERNAL;
    }

    /**
     * Get an array of totara_core_relationship.type's from the recipient constants.
     *
     * @param integer $recipients
     * @return integer[]
     */
    private static function get_types(int $recipients): array {
        return array_values(array_filter(self::$relationship_types, function (int $value) use ($recipients) {
            return ($recipients & $value) === $value;
        }, ARRAY_FILTER_USE_KEY));
    }

    /**
     * Return whether the relationship is allowed as the recipients.
     *
     * @param integer $recipients
     * @param relationship $relationship
     * @return boolean
     */
    public static function is_available(int $recipients, relationship $relationship): bool {
        if ($recipients === 0) {
            throw new coding_exception('recipients are not set');
        }
        if (self::is_external_only($recipients)) {
            return $relationship->idnumber === constants::RELATIONSHIP_EXTERNAL;
        } else {
            $has_type = in_array($relationship->type, self::get_types($recipients));
            if (self::is_internal_only($recipients)) {
                return $has_type && $relationship->idnumber !== constants::RELATIONSHIP_EXTERNAL;
            } else {
                return $has_type || $relationship->idnumber === constants::RELATIONSHIP_EXTERNAL;
            }
        }
    }

    /**
     * Add where clauses to filter out relationships.
     *
     * @param integer $recipients
     * @param builder $builder
     * @param string|null $table_name If not specified, defaults to the alias defined in the builder
     */
    public static function where_available(int $recipients, builder $builder, string $table_name = null): void {
        if ($recipients === 0) {
            throw new coding_exception('recipients are not set');
        }

        $table_name = $table_name ?? $builder->get_alias();

        // This is basically the SQL version of the is_available() function.
        $builder->where(function (builder $inner) use ($recipients, $table_name) {
            if (self::is_external_only($recipients)) {
                $inner->where($table_name . '.idnumber', '=', constants::RELATIONSHIP_EXTERNAL);
            } else {
                $inner->where_in($table_name . '.type', self::get_types($recipients));
                if ($recipients !== recipient::ALL) {
                    if (self::is_internal_only($recipients)) {
                        $inner->where($table_name . '.idnumber', '!=', constants::RELATIONSHIP_EXTERNAL);
                    } else {
                        $inner->or_where($table_name . '.idnumber', '=', constants::RELATIONSHIP_EXTERNAL);
                    }
                }
            }
        });
    }
}
