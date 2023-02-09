<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\settings;

use admin_setting_configtext;

class admin_setting_apiconfigtext extends admin_setting_configtext {
    /**
     * @var int
     */
    public const MAX_INT = 2147483647;

    /**
     * @inheritDoc
     */
    public function validate($data) {
        $result = parent::validate($data);

        if (is_bool($result) && $result === true) {
            if ((int)$data > self::MAX_INT) {
                return get_string('error_validate_max_input_number', 'totara_api', self::MAX_INT);
            }
        }

        return $result;
    }
}