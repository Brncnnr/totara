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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */

namespace totara_engage\query\option;

use totara_engage\query\provider\helper as provider_helper;
use totara_engage\query\provider\queryable;
use totara_engage\query\query;

class section implements option {

    /**
     * @var int
     */
    public const YOURRESOURCES = 1;

    /**
     * @var int
     */
    public const SHAREDWITHYOU = 2;

    /**
     * @var int
     */
    public const SAVEDRESOURCES = 3;

    /**
     * @var int
     */
    public const ALLSITE = 4;

    /**
     * type constructor.
     */
    private function __construct() {
        // Prevent this class from being instantiated.
    }

    /**
     * Get all section options.
     *
     * This function will try to invoke {@see queryable::provide_query_section()}
     * @param query $query
     * @return array
     */
    public static function get_all_options(query $query): array {
        $options = [
            [
                'id' => null,
                'value' => null,
                'label' => get_string('yourlibrary', 'totara_engage')
            ],
            [
                'id' => self::YOURRESOURCES,
                'value' => self::get_code(self::YOURRESOURCES),
                'label' => self::get_string(self::YOURRESOURCES)
            ],
            [
                'id' => self::SHAREDWITHYOU,
                'value' => self::get_code(self::SHAREDWITHYOU),
                'label' => self::get_string(self::SHAREDWITHYOU)
            ],
            [
                'id' => self::SAVEDRESOURCES,
                'value' => self::get_code(self::SAVEDRESOURCES),
                'label' => self::get_string(self::SAVEDRESOURCES)
            ]
        ];

        $classes = provider_helper::get_providers();
        foreach ($classes as $class) {
            /** @var queryable $provider */
            $provider = new $class();
            $options = array_merge($options, $provider->get_section_options($query));
        }

        // If this is for the whole site then include all sections.
        if ($query->is_adder()) {
            $options[] = [
                'id' => self::ALLSITE,
                'value' => self::get_code(self::ALLSITE),
                'label' => self::get_string(self::ALLSITE)
            ];
        }

        return $options;
    }

    /**
     * @param int|null $section
     * @return bool
     */
    public static function is_yourresources(?int $section): bool {
        if ($section) {
            return $section === self::YOURRESOURCES;
        }
        return false;
    }

    /**
     * @param int|null $section
     * @return bool
     */
    public static function is_sharedwithyou(?int $section): bool {
        if ($section) {
            return $section === self::SHAREDWITHYOU;
        }
        return false;
    }

    /**
     * @param int|null $section
     * @return bool
     */
    public static function is_savedresources(?int $section): bool {
        if ($section) {
            return $section === self::SAVEDRESOURCES;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function get_code(int $constant): string {
        switch ($constant) {
            case self::YOURRESOURCES:
                return 'YOURRESOURCES';

            case self::SHAREDWITHYOU:
                return 'SHAREDWITHYOU';

            case self::SAVEDRESOURCES:
                return 'SAVEDRESOURCES';

            case self::ALLSITE:
                return 'ALLSITE';

            default:
                throw new \coding_exception("Invalid constant value '{$constant}'");
        }
    }

    /**
     * @inheritDoc
     */
    public static function get_string(int $constant): string {
        switch ($constant) {
            case self::YOURRESOURCES:
                return get_string('yourresources', 'totara_engage');

            case self::SHAREDWITHYOU:
                return get_string('sharedwithyou', 'totara_engage');

            case self::SAVEDRESOURCES:
                return get_string('savedresources', 'totara_engage');

            case self::ALLSITE:
                return get_string('allsite', 'totara_engage');

            default:
                debugging("Invalid value '{$constant}' for section", DEBUG_DEVELOPER);
                return '';
        }
    }

    /**
     * @inheritDoc
     */
    public static function get_value(string $constantname): int {
        $constantname = strtoupper($constantname);
        $constant = "static::{$constantname}";

        if (!defined($constant)) {
            $cls = static::class;
            throw new \coding_exception(
                "No constant found for name '{$constantname}' within the class {$cls}"
            );
        }

        return constant($constant);
    }

    /**
     * @param int|null $constantname
     * @return bool
     */
    public static function is_valid(?int $section): bool {
        if ($section) {
            return in_array($section, [
                self::YOURRESOURCES,
                self::SHAREDWITHYOU,
                self::SAVEDRESOURCES,
                self::ALLSITE,
            ]);
        }

        // No section filter applied so we don't need to check it.
        return true;
    }

}