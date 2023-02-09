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
 * @package totara_playlist
 */

namespace totara_playlist\totara_engage\query\option;

use totara_engage\query\option\section as engage_section;

class section extends engage_section {

    /**
     * @var int
     */
    public const YOURPLAYLISTS = 5;

    /**
     * @var int
     */
    public const SAVEDPLAYLISTS = 6;

    /**
     * Get all section options.
     *
     * @return array
     */
    public static function get_options(): array {
        return [
            [
                'id' => self::YOURPLAYLISTS,
                'value' => self::get_code(self::YOURPLAYLISTS),
                'label' => get_string('yourplaylists', 'totara_playlist')
            ],
            [
                'id' => self::SAVEDPLAYLISTS,
                'value' => self::get_code(self::SAVEDPLAYLISTS),
                'label' => get_string('savedplaylists', 'totara_playlist')
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_code(int $constant): string {
        switch ($constant) {
            case self::YOURPLAYLISTS:
                return 'YOURPLAYLISTS';

            case self::SAVEDPLAYLISTS:
                return 'SAVEDPLAYLISTS';

            default:
                return parent::get_code($constant);
        }
    }

    /**
     * @inheritDoc
     */
    public static function get_string(int $constant): string {
        switch ($constant) {
            case static::YOURPLAYLISTS:
                return get_string('yourplaylists', 'totara_playlist');

            case static::SAVEDPLAYLISTS:
                return get_string('savedplaylists', 'totara_playlist');

            default:
                return parent::get_string($constant);
        }
    }

    /**
     * @param int|null $section
     * @return bool
     */
    public static function is_yourplaylists(?int $section): bool {
        if ($section) {
            return $section === section::YOURPLAYLISTS;
        }
        return false;
    }

    /**
     * @param int|null $section
     * @return bool
     */
    public static function is_savedplaylists(?int $section): bool {
        if ($section) {
            return $section === section::SAVEDPLAYLISTS;
        }
        return false;
    }

    /**
     * @param int|null $section
     * @return bool
     */
    public static function is_valid(?int $section): bool {
        if ($section) {
            return in_array($section, [
                self::SHAREDWITHYOU,
                self::YOURPLAYLISTS,
                self::SAVEDPLAYLISTS,
                self::ALLSITE,
            ]);
        }
        return true;
    }

}