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
use hierarchy_position\entity\position as position_entity;

/**
 * Position type
 *
 * Note: It is the responsibility of the query to ensure the user is permitted to see a position
 */
class position extends \core\webapi\type_resolver {

    /**
     * Resolve position fields
     *
     * @param string $field
     * @param \stdClass $position
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     * @throws \coding_exception If the position is not a DB record, or if the requested field does not exist.
     */
    public static function resolve(string $field, $position, array $args, execution_context $ec) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');

        if ($position instanceof position_entity) {
            $position = (object) $position->to_array();
        }

        if (!$position instanceof \stdClass) {
            throw new \coding_exception('Only position records from the database are accepted ' . gettype($position));
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
            'path' => false,
            'visible' => false,
            'parentid' => true,
            'typeid' => true,
        ];
        if (isset($basicdata[$field])) {
            if (!isset($position->{$field})) {
                if ($basicdata[$field]) {
                    // Field not set and nullable
                    return null;
                }
                throw new \coding_exception('Expected value, but was not found and was not nullable.', $field);
            }
            return $position->{$field};
        }

        switch ($field) {
            case 'shortname':
            case 'fullname':
                $format = $args['format'] ?? format::FORMAT_PLAIN;
                return self::format_string($position, $field, $format, $ec);
            case 'description':
                if (is_null($position->description)) {
                    return null;
                }
                $context = \context_system::instance();
                $format = $args['format'] ?? format::FORMAT_HTML;
                $formatter = new text_field_formatter($format, $context);
                $formatter->set_pluginfile_url_options(
                    $context,
                    'totara_hierarchy',
                    \hierarchy::get_short_prefix('position'),
                    $position->id
                );
                if ($format === format::FORMAT_RAW && !has_capability('totara/hierarchy:updateposition', $context)) {
                    // They do not have permission to edit, therefore they cannot see the raw.
                    return null;
                }
                return $formatter->format($position->description);
            case 'frameworkid':
                if (!self::can_view_framework()) {
                    return null;
                }
                return $position->frameworkid;
            case 'framework':
                if (!self::can_view_framework()) {
                    return null;
                }
                /** @var \organisation $hierarchy */
                $hierarchy = \hierarchy::load_hierarchy('position');
                return $hierarchy->get_framework($position->frameworkid);
            case 'parent':
                if (empty($position->parentid)) {
                    return null;
                }
                /** @var \position $hierarchy */
                $hierarchy = \hierarchy::load_hierarchy('position');
                return $DB->get_record($hierarchy->shortprefix, ['id' => $position->parentid], '*', MUST_EXIST);
            case 'children':
                /** @var organisation $hierarchy */
                $hierarchy = \hierarchy::load_hierarchy('position');
                $children = $hierarchy->get_item_descendants($position->id, '*');
                unset($children[$position->id]); // Remove the current position!
                return $children;
            case 'type':
                if (empty($position->typeid)) {
                    return null;
                }
                /** @var \position $hierarchy */
                $hierarchy = \hierarchy::load_hierarchy('position');
                return $hierarchy->get_type_by_id($position->typeid);
        }

        throw new \coding_exception('Unknown field', $field);
    }

    /**
     * Returns true if the current user can view the position frameworks.
     * @return bool
     */
    private static function can_view_framework() {
        return has_capability('totara/hierarchy:viewpositionframeworks', \context_system::instance());
    }

    /**
     * Formats the given string for the position.
     *
     * @param \stdClass $position
     * @param string $field
     * @param string $format
     * @param execution_context $ec
     * @return string|null
     */
    private static function format_string(\stdClass $position, string $field, string $format, execution_context $ec) {
        if ($position->{$field} === null) {
            return null;
        }
        $context = \context_system::instance();
        $formatter = new string_field_formatter($format, $context);
        if ($format === format::FORMAT_RAW && !has_capability('totara/hierarchy:updateposition', $context)) {
            return null;
        }
        return $formatter->format($position->{$field});
    }
}
