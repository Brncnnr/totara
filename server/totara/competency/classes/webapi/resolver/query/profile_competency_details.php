<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_orm
 */

namespace totara_competency\webapi\resolver\query;

use core\webapi\execution_context;
use totara_competency\models\profile\competency_progress;

class profile_competency_details extends profile_resolver {
    public static function resolve(array $args, execution_context $ec) {
        $user_id = static::authorize($args['user_id'] ?? null);
        $status = $args['status'] ?? null;
        $competency_id = $args['competency_id'] ?? null;

        if (!isset($competency_id)) {
            return null;
        }
        return competency_progress::build_for_competency($user_id, $competency_id, $status);
    }

}
