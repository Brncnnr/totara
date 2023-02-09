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

use context_system;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_competency\models\scale as scale_model;
use totara_competency\formatter;

/**
 * Competency scale type.
 *
 * Note: It is the responsibility of the query to ensure the user is permitted to see an organisation.
 */
class scale extends type_resolver {

    /**
     * Resolves fields for an organisation
     *
     * @param string $field
     * @param scale_model $scale
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $scale, array $args, execution_context $ec) {
        if (!$scale instanceof scale_model) {
            throw new \coding_exception('Please pass a scale value model');
        }

        $format = $args['format'] ?? null;

        if (!self::authorize($field, $format)) {
            return null;
        }

        $formatter = new formatter\scale($scale, context_system::instance());
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
        if (in_array($field, ['name', 'description']) && $format == format::FORMAT_RAW) {
            return has_capability('totara/hierarchy:viewcompetencyscale', context_system::instance());
        }

        return true;
    }

}
