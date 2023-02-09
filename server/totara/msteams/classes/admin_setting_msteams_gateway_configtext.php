<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package totara_msteams
 */
namespace totara_msteams;

use admin_setting_configtext;
use core\ip_utils;
use core_text;
use totara_msteams\quickaccessmenu\msteams;

final class admin_setting_msteams_gateway_configtext extends admin_setting_configtext {
    /**
     * @inheritDoc
     */
    public function validate($data) {
        global $CFG;
        $result = parent::validate($data);

        if (true !== $result) {
            return $result;
        }

        // We allow empty data input to save config.
        if (core_text::strlen(trim($data)) == 0) {
            return true;
        }

        if (!ip_utils::is_domain_name($data) || !msteams_gateway_helper::is_internal_host_allowed($data)) {
            return get_string('error:domain_name', 'totara_msteams');
        }

        // These checks here are in place in order to allow us construct an error message.
        if (!isset($CFG->msteams_gateway_private_key)) {
            return get_string("error:no_private_key", "totara_msteams");
        }

        if (!extension_loaded("openssl")) {
            return get_string("error:no_openssl_extension", "totara_msteams");
        }

        if (!msteams_gateway_helper::remote_procedure_call_success($data)) {
            return get_string("error:gateway_register", "totara_msteams");
        }

        return true;
    }
}