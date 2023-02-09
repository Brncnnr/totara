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

use core_phpunit\testcase;
use totara_msteams\admin_setting_msteams_gateway_configtext;

class totara_msteams_admin_setting_msteams_gateway_configtext_testcase extends testcase {

    /**
     * @return void
     */
    public function test_admin_setting_msteams_gateway_configtext_with_empty_string(): void {
        $setting = new admin_setting_msteams_gateway_configtext(
            'totara_msteams/domain_name',
            'Domain name',
            'Domain name help',
            '',
            PARAM_TEXT
        );

        self::assertTrue($setting->validate(''));
        self::assertNull($setting->get_setting());
    }
}