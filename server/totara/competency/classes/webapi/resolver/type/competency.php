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
use totara_competency\formatter;
use totara_core\advanced_feature;
use totara_hierarchy\entity\competency as competency_entity;

/**
 * Organisation hierarchy type.
 *
 * Note: It is the responsibility of the query to ensure the user is permitted to see an organisation.
 */
class competency extends type_resolver {

    /**
     * Resolves fields for an organisation
     *
     * @param string $field
     * @param competency_entity $competency
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $competency, array $args, execution_context $ec) {
        if (!$competency instanceof competency_entity) {
            throw new \coding_exception('Accepting only entities.');
        }

        $format = $args['format'] ?? null;

        if ($field == 'aggregation_method') {
            $field = 'aggregationmethod';
        }

        if (!self::authorize($field, $format)) {
            return null;
        }
        $formatter = new formatter\competency($competency, context_system::instance());
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
        $context = \context_system::instance();

        // Some fields require a capability check
        if (in_array($field, ['framework', 'frameworkid'])) {
            return has_capability('totara/hierarchy:viewcompetencyframeworks', $context);
        }

        // Some fields need an extra capability check when format is RAW
        if (in_array($field, ['shortname', 'fullname']) && $format == format::FORMAT_RAW) {
            return has_capability('totara/hierarchy:updatecompetency', $context)
                || has_capability('totara/hierarchy:createcompetency', $context);
        }

        if ($field === 'description' && $format == format::FORMAT_RAW) {
            return has_capability('totara/hierarchy:updatecompetency', $context)
                || has_capability('totara/hierarchy:createcompetency', $context);
        }

        if ($field == 'assign_availability') {
            // This field should show up on perform only
            return advanced_feature::is_enabled('competency_assignment');
        }

        if ($field == 'aggregationmethod') {
            // This field should show up on NON-perform only
            return !advanced_feature::is_enabled('competency_assignment');
        }

        return true;
    }

}
