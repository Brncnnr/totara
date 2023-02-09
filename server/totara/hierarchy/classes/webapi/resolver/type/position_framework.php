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

namespace totara_hierarchy\webapi\resolver\type;

use core\format;
use core\webapi\execution_context;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use totara_core\advanced_feature;

/**
 * Position framework type
 *
 * Note: It is the responsibility of the query to ensure the user is permitted to see a position
 */
class position_framework extends \core\webapi\type_resolver {

    /**
     * Resolve position framework fields
     *
     * @param string $field
     * @param \stdClass $positionframework
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     * @throws \coding_exception If the positionframework is not a DB record, or if the requested field does not exist.
     */
    public static function resolve(string $field, $positionframework, array $args, execution_context $ec) {
        global $CFG;

        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');

        if (!$positionframework instanceof \stdClass) {
            throw new \coding_exception('Only position framework records from the database are accepted ' . gettype($positionframework));
        }

        if (advanced_feature::is_disabled('positions')) {
            // You should have checked before resolving to this type.
            throw new \coding_exception('Positions have been disabled.');
        }

        // Basic field handling, these fields require no formatting, but may or may not be nullable.
        // The key is the field, and the value is whether the field is nullable or not.
        $basicdata = [
            'id' => false,
            'idnumber' => false,
        ];
        if (isset($basicdata[$field])) {
            if (!isset($positionframework->{$field})) {
                if ($basicdata[$field]) {
                    // Field not set and nullable
                    return null;
                }
                throw new \coding_exception('Expected value, but was not found and was not nullable.', $field);
            }
            return $positionframework->{$field};
        }

        switch ($field) {
            case 'shortname':
            case 'fullname':
                $format = $args['format'] ?? format::FORMAT_PLAIN;
                return self::format_string($positionframework, $field, $format, $ec);
            case 'description':
                if (is_null($positionframework->description)) {
                    return null;
                }
                $context = \context_system::instance();
                $format = $args['format'] ?? format::FORMAT_HTML;
                $formatter = new text_field_formatter($format, $context);
                $formatter->set_pluginfile_url_options(
                    $context,
                    'totara_hierarchy',
                    \hierarchy::get_short_prefix('position') . '_framework',
                    $positionframework->id
                );
                if ($format === format::FORMAT_RAW && !has_capability('totara/hierarchy:updatepositionframeworks', $context)) {
                    // They do not have permission to edit, therefore they cannot see the raw.
                    return null;
                }
                return $formatter->format($positionframework->description);
            case 'positions':
                $position = \hierarchy::load_hierarchy('position');
                return $position->get_all_root_items();
        }

        throw new \coding_exception('Unknown field', $field);
    }

    /**
     * Formats the given string for the position.
     *
     * @param \stdClass $position
     * @param string $field
     * @param string $format
     * @param execution_context $ec
     * @return string
     */
    private static function format_string(\stdClass $position, string $field, string $format, execution_context $ec) {
        if ($position->{$field} === null) {
            return null;
        }
        $context = \context_system::instance();
        $formatter = new string_field_formatter($format, $context);
        if ($format === format::FORMAT_RAW && !has_capability('totara/hierarchy:updatepositionframeworks', $context)) {
            return null;
        }
        return $formatter->format($position->{$field});
    }
}
