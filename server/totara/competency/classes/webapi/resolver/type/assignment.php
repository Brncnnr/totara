<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\type;

use coding_exception;
use context_system;
use core\date_format;
use core\webapi\execution_context;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\type_resolver;
use totara_competency\formatter;
use totara_competency\models\assignment as assignment_model;

/**
 * Note: It is the responsibility of the query to ensure the user is permitted to see an organisation.
 */
class assignment extends type_resolver {

    /**
     * @param string $field
     * @param assignment_model $assignment
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $assignment, array $args, execution_context $ec) {
        if (!$assignment instanceof assignment_model) {
            throw new coding_exception('Accepting only assignment models.');
        }

        if ($field === 'is_assigned') {
            if (empty($args['user_id'])) {
                throw new coding_exception('Missing user_id argument for assignment field \'is_assigned\'');
            }

            return $assignment->is_assigned($args['user_id']);
        }

        if ($field === 'unassigned_at') {
            if (empty($args['user_id'])) {
                throw new coding_exception('Missing user_id argument for assignment field \'unassigned_at\'');
            }

            $format = $args['format'] ?? date_format::FORMAT_TIMESTAMP;
            $context = $ec->has_relevant_context() ? $ec->get_relevant_context() : context_system::instance();
            $formatter = new date_field_formatter($format, $context);

            return $formatter->format($assignment->get_unassigned_at($args['user_id']));
        }

        $formatter = new formatter\assignment($assignment, context_system::instance());
        return $formatter->format($field, $args['format'] ?? null);
    }

}