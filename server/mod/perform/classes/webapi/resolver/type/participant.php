<?php
/**
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\type;

use coding_exception;
use core\format;
use core\webapi\execution_context;
use core\webapi\resolver\type\user as user_type;
use core\webapi\type_resolver;
use mod_perform\formatter\activity\participant as participant_formatter;
use mod_perform\models\activity\helpers\external_participation;
use mod_perform\models\activity\participant as participant_model;

class participant extends type_resolver {

    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!$source instanceof participant_model) {
            throw new coding_exception(sprintf("Invalid class %s passed to participant type resolver.", get_class($source)));
        }

        $helper = new external_participation($ec);

        // If the user requested is an external user or
        // the user who requests has a valid token we use the participant
        // model to bypass the core_user checks which only work for internal users
        // and only if you are logged in.
        if ($source->is_external()
            || $helper->is_external_participation()
            || in_array($field, participant_model::$model_only_fields, true)
        ) {
            $format = $args['format'] ?? format::FORMAT_PLAIN;
            $formatter = new participant_formatter($source, $ec->get_relevant_context());

            return $formatter->format($field, $format);
        }

        return user_type::resolve($field, $source->get_user()->to_record(), $args, $ec);
    }

}