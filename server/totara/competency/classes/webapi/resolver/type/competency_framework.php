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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_hierarchy
 */

namespace totara_competency\webapi\resolver\type;

use coding_exception;
use context_system;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_hierarchy\entity\competency_framework as competency_framework_entity;
use totara_competency\formatter;

/**
 * Organisation hierarchy type.
 *
 * Note: It is the responsibility of the query to ensure the user is permitted to see an organisation.
 */
class competency_framework extends type_resolver {

    /**
     * Resolves fields for an organisation
     *
     * @param string $field
     * @param competency_framework_entity $competency_framework
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $competency_framework, array $args, execution_context $ec) {
        if (!$competency_framework instanceof competency_framework_entity) {
            throw new coding_exception('Accept competency framework entity only.');
        }

        $format = $args['format'] ?? null;

        if (!self::authorize($field, $format)) {
            return null;
        }

        $formatter = new formatter\competency_framework($competency_framework, context_system::instance());
        return $formatter->format($field, $format);
    }


    /**
     * Check if access to certain fields is allowed
     *
     * @param string $field
     * @param string $format
     * @return bool
     */
    public static function authorize(string $field, ?string $format) {
        $context = context_system::instance();

        // Some fields need an extra capability check when format is RAW
        if (in_array($field, ['shortname', 'fullname']) && $format == format::FORMAT_RAW) {
            return has_capability('totara/hierarchy:updatecompetencyframeworks', $context);
        }

        if ($field === 'description' && $format == format::FORMAT_RAW) {
            return has_capability('totara/hierarchy:updatecompetencyframeworks', $context);
        }

        return true;
    }

}
