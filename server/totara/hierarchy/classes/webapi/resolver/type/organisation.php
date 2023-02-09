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
use hierarchy_organisation\entity\organisation as organisation_entity;
use totara_core\advanced_feature;

/**
 * Organisation hierarchy type.
 *
 * Note: It is the responsibility of the query to ensure the user is permitted to see an organisation.
 */
class organisation extends \core\webapi\type_resolver {

    /**
     * Resolves fields for an organisation
     *
     * @param string $field
     * @param \stdClass $organisation
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     * @throws \coding_exception If the organisation is not a DB record, or if the requested field does not exist.
     */
    public static function resolve(string $field, $organisation, array $args, execution_context $ec) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');

        if ($organisation instanceof organisation_entity) {
            $organisation = (object) $organisation->to_array();
        }

        if (!$organisation instanceof \stdClass) {
            throw new \coding_exception('Only organisation records from the database are accepted ' . gettype($organisation));
        }

        if (advanced_feature::is_disabled('organisations')) {
            // You should have checked before resolving to this type.
            throw new \coding_exception('Organisations have been disabled.');
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
            if (!isset($organisation->{$field})) {
                if ($basicdata[$field]) {
                    // Field not set and nullable
                    return null;
                }
                throw new \coding_exception('Expected value, but was not found and was not nullable.', $field);
            }
            return $organisation->{$field};
        }

        switch ($field) {
            case 'shortname':
            case 'fullname':
                $format = $args['format'] ?? format::FORMAT_PLAIN;
                return self::format_string($organisation, $field, $format, $ec);
            case 'description':
                if (is_null($organisation->description)) {
                    return null;
                }
                $context = \context_system::instance();
                $format = $args['format'] ?? format::FORMAT_HTML;
                $formatter = new text_field_formatter($format, $context);
                $formatter->set_pluginfile_url_options(
                    $context,
                    'totara_hierarchy',
                    \hierarchy::get_short_prefix('organisation'),
                    $organisation->id
                );
                if ($format === format::FORMAT_RAW && !has_capability('totara/hierarchy:updateorganisation', $context)) {
                    // They do not have permission to edit, therefore they cannot see the raw.
                    return null;
                }
                return $formatter->format($organisation->description);
            case 'frameworkid':
                if (!self::can_view_framework()) {
                    return null;
                }
                return $organisation->frameworkid;
            case 'framework':
                if (!self::can_view_framework()) {
                    return null;
                }
                /** @var \organisation $hierarchy */
                $hierarchy = \hierarchy::load_hierarchy('organisation');
                return $hierarchy->get_framework($organisation->frameworkid);
            case 'parent':
                if (empty($organisation->parentid)) {
                    return null;
                }
                /** @var \organisation $hierarchy */
                $hierarchy = \hierarchy::load_hierarchy('organisation');
                return $DB->get_record($hierarchy->shortprefix, ['id' => $organisation->parentid], '*', MUST_EXIST);
            case 'children':
                /** @var organisation $hierarchy */
                $hierarchy = \hierarchy::load_hierarchy('organisation');
                $children = $hierarchy->get_item_descendants($organisation->id, '*');
                unset($children[$organisation->id]); // Remove the current organisation!
                return $children;
            case 'type':
                if (empty($organisation->typeid)) {
                    return null;
                }
                /** @var \organisation $hierarchy */
                $hierarchy = \hierarchy::load_hierarchy('organisation');
                return $hierarchy->get_type_by_id($organisation->typeid);
        }

        throw new \coding_exception('Unknown field', $field);
    }

    /**
     * Returns true if the current user can view the organisation frameworks.
     * @return bool
     */
    private static function can_view_framework() {
        return has_capability('totara/hierarchy:vieworganisationframeworks', \context_system::instance());
    }

    /**
     * Formats the given string for the organisation.
     *
     * @param \stdClass $organisation
     * @param string $field
     * @param string $format
     * @param execution_context $ec
     * @return string|null
     */
    private static function format_string(\stdClass $organisation, string $field, string $format, execution_context $ec) {
        if ($organisation->{$field} === null) {
            return null;
        }
        $context = \context_system::instance();
        $formatter = new string_field_formatter($format, $context);
        if ($format === format::FORMAT_RAW && !has_capability('totara/hierarchy:updateorganisation', $context)) {
            return null;
        }
        return $formatter->format($organisation->{$field});
    }
}
