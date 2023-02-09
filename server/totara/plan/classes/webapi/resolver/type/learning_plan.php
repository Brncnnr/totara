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
 * @package totara_plan
 */

namespace totara_plan\webapi\resolver\type;

use context_system;
use core\format;
use core\webapi\execution_context;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use core\webapi\type_resolver;
use development_plan;

global $CFG;
require_once($CFG->dirroot . '/totara/plan/development_plan.class.php');

defined('MOODLE_INTERNAL') || die();

class learning_plan extends type_resolver {

    /**
     * Resolves fields for an individual learning plan
     *
     * @param string $field
     * @param development_plan $plan
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $plan, array $args, execution_context $ec) {
        require_login(null, false, null, false, true);

        switch ($field) {
            case 'id':
                return $plan->id;
            case 'can_view':
                return $plan->can_view();
            case 'name':
                if (!$plan->can_view()) {
                    return null;
                }
                $format = $args['format'] ?? format::FORMAT_PLAIN;
                $formatter = new string_field_formatter($format, context_system::instance());
                return $formatter->format($plan->name);
            case 'description':
                if (!$plan->can_view()) {
                    return null;
                }
                $context = context_system::instance();
                $format = $args['format'] ?? format::FORMAT_HTML;
                $formatter = new text_field_formatter($format, $context);
                $formatter->set_pluginfile_url_options($context, 'totara_plan', 'dp_plan', $plan->id);
                return $formatter->format($plan->description);
            default:
                throw new \coding_exception('Unknown field', $field);
        }
    }

}
