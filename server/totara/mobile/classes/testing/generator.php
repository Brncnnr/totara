<?php
/*
 * This file is part of Totara LMS
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\testing;

/**
 * Totara Mobile test data generator class
 *
 * @package totara_mobile
 */
final class generator extends \core\testing\component_generator {

    /**
     * Just a stub to use for aging device records
     */

    /**
     * Age a user's mobile devices the specified number of seconds
     *
     * @param string $username Username of user whose mobile devices we want to age
     * @param int $seconds Number of seconds to age
     */
    public function age_mobile_devices($username, $seconds) {
        global $DB;
        $user = $DB->get_record('user', array('username' => $username), '*', MUST_EXIST);
        $sql = "UPDATE {totara_mobile_devices} 
                   SET timeregistered = timeregistered - :seconds
                 WHERE userid = :userid";
        $DB->execute($sql, ['seconds' => $seconds, 'userid' => $user->id]);
    }

}
