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
 * @package criteria_coursecompletion
 */

namespace criteria_coursecompletion\watcher;

use core\hook\admin_setting_changed;
use totara_criteria\course_item_helper;

class totara_core {

    public static function admin_settings_changed(admin_setting_changed $hook) {
        $cfgsetting = "enablecompletion";

        if ($hook->name !== $cfgsetting) {
            return;
        }

        if ($hook->newvalue == $hook->oldvalue) {
            return;
        }

        course_item_helper::global_setting_changed('coursecompletion');
    }

}
