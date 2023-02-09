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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace performelement_long_text\watcher;

use mod_perform\hook\post_element_response_submission;
use performelement_long_text\long_text;

/**
 * Handles post response submission of long text element responses.
 *
 * @package performelement_long_text\watcher
 */
class post_response_submission {

    /**
     * Processes weka response.
     *
     * @param post_element_response_submission $hook
     */
    public static function process_response(post_element_response_submission $hook): void {
        if (!$hook->matches_element_plugin(long_text::class)) {
            return;
        }

        $processed_response = long_text::process_weka_response($hook->get_response_id(), $hook->get_element(), $hook->get_response_data());
        $hook->set_response_data($processed_response);
    }
}