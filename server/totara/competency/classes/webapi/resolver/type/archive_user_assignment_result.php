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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_competency\models\assignment;
use \coding_exception;

/**
 * Class archive_user_assignment_result
 *
 * @package totara_competency\webapi\resolver\type
 */
class archive_user_assignment_result extends type_resolver {

    /**
     * Resolve archive user assignment result type.
     *
     * @param string $field
     * @param mixed $assignment
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     * @throws \coding_exception
     */
    public static function resolve(string $field, $assignment, array $args, execution_context $ec) {
        if (!$assignment instanceof assignment) {
            throw new coding_exception('Accepting only assignment models.');
        }

        switch ($field) {
            case 'archived_assignment':
                return $assignment;
            default:
                throw new coding_exception('Field not implemented');
        }
    }
}
