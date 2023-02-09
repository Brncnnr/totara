<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\webapi\resolver\type;

use coding_exception;
use contentmarketplace_linkedin\dto\locale as data_locale;
use core\webapi\execution_context;
use core\webapi\type_resolver;

class locale extends type_resolver {
    /**
     * @param string $field
     * @param data_locale source
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof data_locale)) {
            throw new coding_exception(
                "Expecting the \$source to be an instance of " . data_locale::class
            );
        }

        switch ($field) {
            case "language":
                return $source->get_lang();

            case "country":
                return $source->get_country();

            case "language_label":
                $language = $source->get_lang();
                $manager = get_string_manager();

                $available_languages = $manager->get_list_of_languages();
                if (!isset($available_languages[$language])) {
                    // The available language should be supported by us.
                    // However throw exception to flag this up as we are not yet supporting it.
                    throw new coding_exception("The language '{$language}' is not supported yet");
                }

                return $available_languages[$language];

            default:
                throw new coding_exception("The field '{$field}' is not yet supported");
        }
    }
}