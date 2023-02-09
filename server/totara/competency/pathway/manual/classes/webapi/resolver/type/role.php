<?php
/*
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_manual
 */

namespace pathway_manual\webapi\resolver\type;

use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use pathway_manual\models\roles\role as role_model;
use core\webapi\formatter\field\string_field_formatter;

class role extends type_resolver {

    /**
     * Resolves fields for a manual rating role
     *
     * @param string $field
     * @param role_model $role
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $role, array $args, execution_context $ec) {
        require_login(null, false, null, false, true);

        switch ($field) {
            case 'name':
                return $role->get_name();
            case 'display_name':
                $format = $args['format'] ?? format::FORMAT_PLAIN;
                $formatter = new string_field_formatter($format, \context_system::instance());
                return $formatter->format($role->get_display_name());
            case 'display_order':
                return $role->get_display_order();
            case 'has_role':
                return $role->has_role();
            default:
                throw new \coding_exception('Unknown field', $field);
        }
    }

}
