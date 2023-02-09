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
 * @package pathway_learning_plan
 */

namespace pathway_learning_plan\webapi\resolver\type;

use context_system;
use core\date_format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use pathway_learning_plan\models\competency_plan as competency_plan_model;
use core\webapi\formatter\field\date_field_formatter;

defined('MOODLE_INTERNAL') || die();

class competency_plan extends type_resolver {

    /**
     * Resolves fields for plans linked to a competency
     *
     * @param string $field
     * @param competency_plan_model $competency_plans
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $competency_plans, array $args, execution_context $ec) {
        require_login(null, false, null, false, true);

        switch ($field) {
            case 'learning_plans':
                return $competency_plans->get_plans();
            case 'scale_value':
                return $competency_plans->get_scale_value();
            case 'date_assigned':
                $format = $args['format'] ?? date_format::FORMAT_TIMESTAMP;
                $formatter = new date_field_formatter($format, context_system::instance());
                return $formatter->format($competency_plans->get_date_assigned());
            default:
                throw new \coding_exception('Unknown field', $field);
        }
    }

}
