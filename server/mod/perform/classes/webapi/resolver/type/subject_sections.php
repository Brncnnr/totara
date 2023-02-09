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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\type;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\type_resolver;

use mod_perform\models\response\subject_sections as subject_sections_model;

defined('MOODLE_INTERNAL') || die();

/**
 * Maps the section_participants model class into a GraphQL mod_perform_subject_sections
 * type.
 */
class subject_sections extends type_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!$source instanceof subject_sections_model) {
            throw new coding_exception(__METHOD__ . ' requires a subject sections model');
        }

        switch ($field) {
            case 'subject':
                return $source->get_subject_instance();
            case 'sections':
                return $source->get_sections();
            default:
                $err = "Unknown field '$field' requested in subject sections type resolver";
                throw new coding_exception($err);
        }
    }
}