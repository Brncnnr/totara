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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\type;

use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_competency\entity\pathway as pathway_entity;
use totara_competency\pathway as pathway_instance;
use totara_competency\pathway_factory;
use core\webapi\formatter\field\string_field_formatter;

/**
 * General totara competency pathway
 *
 * Please be aware that it is the responsibility of the query to ensure that the user is allowed to
 * see this.
 */
class pathway extends type_resolver {

    /**
     * Resolves a competency pathway field.
     *
     * @param string $field
     * @param pathway|pathway_entity $pathway
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $pathway, array $args, execution_context $ec) {
        // We want to be flexible and be able to work with entities as well
        if ($pathway instanceof pathway_entity) {
            $pathway = pathway_factory::from_entity($pathway);
        }

        if (!$pathway instanceof pathway_instance) {
            throw new \coding_exception('Only \totara_competency\pathway objects are accepted: ' . gettype($pathway));
        }

        switch ($field) {
            case 'id':
                return $pathway->get_id();
            case 'pathway_type':
                return $pathway->get_path_type();
            case 'instance_id':
                return $pathway->get_path_instance_id();
            case 'title':
                return $pathway->get_title();
            case 'sortorder':
                return $pathway->get_sortorder();
            case 'status':
                return $pathway->get_status_name();
            case 'classification':
                return $pathway->get_classification_name();
            case 'scale_value':
                $format = $args['format'] ?? format::FORMAT_PLAIN;
                $formatter = new string_field_formatter($format, \context_system::instance());
                $scale_value = $pathway->get_scale_value();
                return $formatter->format($scale_value ? $scale_value->name : null);
            case 'error':
                if ($pathway->is_valid()) {
                    return null;
                }
                $format = $args['format'] ?? format::FORMAT_PLAIN;
                $formatter = new string_field_formatter($format, \context_system::instance());
                return $formatter->format(get_string('error_invalid_configuration', 'totara_competency'));
            case 'criteria_summary':
                return $pathway->get_summarized_criteria_set();
        }

        throw new \coding_exception('Unknown field', $field);
    }

}
