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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\type;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_perform\models\activity\settings\visibility_conditions\visibility_option;

/**
 * Class visibility_condition_option
 *
 * @package mod_perform\webapi\resolver\type
 */
class visibility_condition_option extends type_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!$source instanceof visibility_option) {
            throw new coding_exception(__METHOD__ . ' requires a visibility option model');
        }
        switch ($field) {
            case 'name':
                return $source->get_name();
            case 'value':
                return $source->get_value();
            case 'participant_description':
                return $source->get_participant_description();
            case 'view_only_participant_description':
                return $source->get_view_only_participant_description();
            default:
                $err = "Unknown field '$field' requested in visibility option type resolver";
                throw new coding_exception($err);
        }
    }
}
