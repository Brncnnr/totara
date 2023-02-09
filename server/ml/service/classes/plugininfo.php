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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_service
 */

namespace ml_service;

use core\plugininfo\ml;

/**
 * Plugin info for ml_service
 */
final class plugininfo extends ml {
    /**
     * @return bool
     */
    public function is_uninstall_allowed(): bool {
        return false;
    }

    /**
     * @return array
     */
    public function get_usage_for_registration_data(): array {
        return [];
    }

    /**
     * @return bool
     */
    public function can_toggle(): bool {
        return false;
    }
}