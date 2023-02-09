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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\type;

use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_perform\formatter\activity\participant_instance as participant_instance_formatter;
use mod_perform\models\activity\anonymous_participant_instance;
use mod_perform\models\activity\helpers\external_participation;
use mod_perform\models\activity\participant_instance as participant_instance_model;

class participant_instance extends type_resolver {

    /**
     * @param string $field
     * @param participant_instance_model $participant_instance
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $participant_instance, array $args, execution_context $ec) {
        if (!$participant_instance instanceof participant_instance_model) {
            throw new \coding_exception('Expected participant_instance model');
        }

        $should_anonymise = $participant_instance->should_anonymise();

        // If this is an external participation then we cannot determine the user
        // by checking logged in user. In this case we need to use the token to
        // identify the user and whether this instance belongs to him
        $helper = new external_participation($ec);
        if ($helper->belongs_to($participant_instance)) {
            $should_anonymise = false;
        }

        // Last chance guarding when participants should be anonymous.
        if (!$participant_instance instanceof anonymous_participant_instance && $should_anonymise) {
            $participant_instance = new anonymous_participant_instance($participant_instance);
        }

        $format = $args['format'] ?? format::FORMAT_HTML;
        $formatter = new participant_instance_formatter($participant_instance, $ec->get_relevant_context());

        return $formatter->format($field, $format);
    }
}