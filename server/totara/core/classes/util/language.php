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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\util;

/**
 * Convert language code between the Totara format and the IETF BCP-47 format.
 */
final class language {
    /**
     * Get the language code of an installed language pack Totara can understand.
     *
     * @param string $language language code such as en, en-NZ, en_nz
     * @param boolean $includeall include also disabled translations
     * @return string|null the language code
     */
    public static function convert_to_totara_format(string $language, bool $includeall = true): ?string {
        $sm = get_string_manager();
        $language = strtolower($language);
        // First try xx-yy or xx_yy.
        if (preg_match('/^(\w+)[\-_](\w+)$/', $language, $matches)) {
            $primary = $matches[1];
            $region = $matches[2];
            // Convert zh-TW into zh_tw.
            $desired = "{$primary}_{$region}";
            if ($sm->translation_exists($desired, $includeall)) {
                return $desired;
            }
            // Convert en-NZ, en-IE, en-ZA into en.
            if ($sm->translation_exists($primary, $includeall)) {
                return $primary;
            }
        }
        // Then try the whole string.
        if ($sm->translation_exists($language, $includeall)) {
            return $language;
        }
        return null;
    }

    /**
     * Get the language code as the IETF BCP-47 format.
     *
     * @param string $language
     * @return string
     */
    public static function convert_to_ietf_format(string $language): string {
        $language = strtolower($language);
        // Try xx-yy or xx_yy.
        if (preg_match('/^(\w+)[\-_](\w+)$/', $language, $matches)) {
            $primary = $matches[1];
            $region = strtoupper($matches[2]);
            return "{$primary}-{$region}";
        }
        // Return the original string in lowercase.
        return $language;
    }
}
