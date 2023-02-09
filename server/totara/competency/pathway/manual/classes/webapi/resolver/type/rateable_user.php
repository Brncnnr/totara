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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_manual
 */

namespace pathway_manual\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use pathway_manual\models\rateable_user as rateable_user_model;

class rateable_user extends type_resolver {

    /**
     * Resolves fields for a rateable user.
     *
     * @param string $field
     * @param rateable_user_model $rateable_user
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $rateable_user, array $args, execution_context $ec) {
        require_login(null, false, null, false, true);

        switch ($field) {
            case 'user':
                return (object) $rateable_user->get_user()->to_array();
            case 'competency_count':
                return $rateable_user->get_competency_count();
            case 'latest_rating':
                return $rateable_user->get_latest_rating();
            default:
                throw new \coding_exception('Unknown field', $field);
        }
    }

}
