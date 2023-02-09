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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\type;

use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_approval\exception\helper\validation;
use mod_approval\formatter\application\application_activity_formatter;
use mod_approval\model\application\application_activity as application_activity_model;

/**
 * Resolves mod_approval_application_activity
 */
final class application_activity extends type_resolver {
    /**
     * @param string $field
     * @param application_activity_model|object $activity
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $activity, array $args, execution_context $ec) {
        validation::instance_of($activity, application_activity_model::class);
        $format = $args['format'] ?? format::FORMAT_PLAIN;
        $formatter = new application_activity_formatter($activity, $ec->get_relevant_context());
        return $formatter->format($field, $format);
    }
}
