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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\watcher;

use coding_exception;
use totara_userdata\hook\userdata_normalise_label;

class userdata_label {
    /**
     * Update GDPR totara export/purge userdata label to use plural form.
     *
     * @param userdata_normalise_label $hook
     * @throws coding_exception
     */
    public static function normalise(userdata_normalise_label $hook): void {
        $grouplabels = $hook->get_grouplabels();
        if (isset($grouplabels['mod_approval'])) {
            $grouplabels['mod_approval'] = get_string('modulenameplural', 'mod_approval');
            $hook->set_grouplabels($grouplabels);
        }
    }
}