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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model;

use lang_string;
use mod_approval\exception\model_exception;

/**
 * Class status supports enum for the status field in several approval workflow model classes
 *
 * @package mod_approval\model
 */
class status {

    public const DRAFT = 1;
    public const ACTIVE = 2;
    public const ARCHIVED = 3;

    public const DRAFT_ENUM = 'draft';
    public const ACTIVE_ENUM = 'active';
    public const ARCHIVED_ENUM = 'archived';

    /**
     * Is status code valid?
     *
     * @param int $status
     * @return bool
     */
    public static function is_valid_code(int $status): bool {
        return in_array($status, [
            self::DRAFT,
            self::ACTIVE,
            self::ARCHIVED,
        ]);
    }

    /**
     * Is status enum valid?
     *
     * @param string $enum
     * @return bool
     */
    public static function is_valid_enum(string $enum): bool {
        return in_array(strtolower($enum), [
            self::DRAFT_ENUM,
            self::ACTIVE_ENUM,
            self::ARCHIVED_ENUM,
        ]);
    }

    /**
     * Gets label associated with status code.
     *
     * @param int $status
     * @return lang_string
     */
    public static function label(int $status): lang_string {
        $label_keys = [
            self::DRAFT => self::DRAFT_ENUM,
            self::ACTIVE => self::ACTIVE_ENUM,
            self::ARCHIVED => self::ARCHIVED_ENUM,
        ];
        if (!isset($label_keys[$status])) {
            throw new model_exception("Unknown status code");
        }
        return new lang_string('model_status_' . $label_keys[$status], 'mod_approval');
    }

    /**
     * Return for tui_view
     *
     * @return array
     */
    public static function get_list(): array {
        return [
            [
                'label' => get_string('filter_all', 'mod_approval'),
                'enum' => null,
            ],
            [
                'label' => self::label(self::DRAFT)->out(),
                'enum' => strtoupper(self::DRAFT_ENUM),
            ],
            [
                'label' => self::label(self::ACTIVE)->out(),
                'enum' => strtoupper(self::ACTIVE_ENUM),
            ],
            [
                'label' => self::label(self::ARCHIVED)->out(),
                'enum' => strtoupper(self::ARCHIVED_ENUM),
            ],
        ];
    }
}