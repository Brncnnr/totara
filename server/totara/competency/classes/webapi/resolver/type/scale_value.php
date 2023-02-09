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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\type;

use context_system;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_competency\formatter;
use totara_hierarchy\entity\scale_value as scale_value_entity;

/**
 * Competency scale type.
 *
 * Note: It is the responsibility of the query to ensure the user is permitted to see an organisation.
 */
class scale_value extends type_resolver {

    /**
     * Resolves fields for an organisation
     *
     * @param string $field
     * @param scale_value_entity $value
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $value, array $args, execution_context $ec) {
        if (!$value instanceof scale_value_entity) {
            throw new \coding_exception('Please pass a scale value entity');
        }

        $format = $args['format'] ?? null;

        if (!self::authorize($field, $format)) {
            return null;
        }

        $formatter = new formatter\scale_value($value, context_system::instance());
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
        // Some fields need an extra capability check when format is RAW
        if (in_array($field, ['name', 'description']) && $format == format::FORMAT_RAW || in_array($field, ['timemodfied', 'usermodified'])) {
            return has_capability('totara/hierarchy:viewcompetencyscale', context_system::instance());
        }

        return true;
    }

}
