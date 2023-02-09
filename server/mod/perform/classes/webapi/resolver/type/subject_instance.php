<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\type;

use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_perform\formatter\activity\subject_instance as subject_instance_formatter;
use mod_perform\models\activity\subject_instance as subject_instance_model;

/**
 * Note: It is the responsibility of the query to ensure the user is permitted to see an activity.
 */
class subject_instance extends type_resolver {

    /**
     * @param string $field
     * @param subject_instance_model $subject_instance
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $subject_instance, array $args, execution_context $ec) {
        if (!$subject_instance instanceof subject_instance_model) {
            throw new \coding_exception('Expected subject_instance model');
        }

        $format = $args['format'] ?? format::FORMAT_HTML;

        $formatter = new subject_instance_formatter($subject_instance, $ec->get_relevant_context());

        return $formatter->format($field, $format);
    }
}