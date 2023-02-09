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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\webapi\resolver\type;

use coding_exception;
use contentmarketplace_linkedin\dto\tree_filter_select_option;
use context_system;
use core\format;
use core\webapi\execution_context;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\type_resolver;

class catalog_import_filter_select_option extends type_resolver {

    /**
     * @param string $field
     * @param tree_filter_select_option $select_option
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $select_option, array $args, execution_context $ec) {
        if (!$select_option instanceof tree_filter_select_option) {
            throw new coding_exception('Expected an instance of ' . tree_filter_select_option::class);
        }

        switch ($field) {
            case 'id':
                return $select_option->get_id();
            case 'label':
                // Force the format to be plain - tree labels can only be simple text.
                // We use the system context, as the filter option strings are sourced from LinkedIn Learning and aren't internal.
                $formatter = new string_field_formatter(format::FORMAT_PLAIN, context_system::instance());
                return $formatter->format($select_option->get_label());
            default:
                throw new coding_exception("Unsupported field: $field");
        }
    }

}
