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

use context_system;
use core\date_format;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use pathway_manual\entity\rating as rating_entity;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;

defined('MOODLE_INTERNAL') || die();

class rating extends type_resolver {

    /**
     * Resolves fields for an individual manual rating
     *
     * @param string $field
     * @param rating_entity $rating
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $rating, array $args, execution_context $ec) {
        require_login(null, false, null, false, true);

        switch ($field) {
            case 'rater':
                $rater = $rating->assigned_by_user;
                if ($rater) {
                    return (object) $rater->to_array();
                }
                return null;
            case 'scale_value':
                return $rating->scale_value;
            case 'timestamp':
                $format = $args['format'] ?? date_format::FORMAT_TIMESTAMP;
                $formatter = new date_field_formatter($format, context_system::instance());
                return $formatter->format($rating->date_assigned);
            case 'comment':
                $format = $args['format'] ?? format::FORMAT_PLAIN;
                $formatter = new string_field_formatter($format, context_system::instance());
                return $formatter->format($rating->comment);
            default:
                throw new \coding_exception('Unknown field', $field);
        }
    }

}
