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
use mod_approval\formatter\application\application_submission_formatter;
use mod_approval\model\application\application_submission as application_submission_model;

/**
 * Resolves mod_approval_application_submission
 */
final class application_submission extends type_resolver {
    /**
     * @param string $field
     * @param application_submission_model|object $submission
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $submission, array $args, execution_context $ec) {
        validation::instance_of($submission, application_submission_model::class);
        if ($field === 'is_first_submission') {
            return $submission->is_first_submission();
        }
        $format = $args['format'] ?? format::FORMAT_PLAIN;
        $formatter = new application_submission_formatter($submission, $ec->get_relevant_context());
        return $formatter->format($field, $format);
    }
}
