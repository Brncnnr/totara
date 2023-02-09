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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\testing;

class util {

    /**
     * Generates a multi lang string for all currently installed languages based on the original string
     *
     * @return string the multilang string in form of <span class="de" class="multilang">DE $original_string</span>...
     */
    public static function generate_multilang_string(string $original_string): string {
        $result = '';
        $languages = array_keys(get_string_manager()->get_list_of_translations());
        foreach ($languages as $language) {
            $result .= sprintf("<span lang=\"%s\" class=\"multilang\">%s %s</span>", $language, strtoupper($language), $original_string);
        }
        return $result;
    }

}