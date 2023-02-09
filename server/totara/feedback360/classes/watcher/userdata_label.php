<?php
/*
 * This file is part of Totara Perform
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_feedback360
 */

namespace totara_feedback360\watcher;

use coding_exception;
use totara_userdata\hook\userdata_normalise_label;

class userdata_label {
    /**
     * Update GDPR totara export/purge userdata label to include legacy.
     *
     * @param userdata_normalise_label $hook
     * @throws coding_exception
     */
    public static function normalise(userdata_normalise_label $hook): void {
        $grouplabels = $hook->get_grouplabels();
        if (isset($grouplabels['totara_feedback360'])) {
            $grouplabels['totara_feedback360'] = get_string('legacy_feedback', 'totara_feedback360');
            $hook->set_grouplabels($grouplabels);
        }
    }
}