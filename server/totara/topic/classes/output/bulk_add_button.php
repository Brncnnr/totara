<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_topic
 */
namespace totara_topic\output;

use core\output\template;

/**
 * Used for rendering a container box which only contains a button for bulk action.
 */
final class bulk_add_button extends template {
    /**
     * @param int|null $userid
     * @return bulk_add_button
     */
    public static function create(?int $userid = null): bulk_add_button {
        global $USER;

        if (null == $userid) {
            $userid = $USER->id;
        }

        // Using the context system because there was no tenant specific api for this.
        $context = \context_system::instance();
        return new static(
            [
                'button' => has_capability('totara/topic:add', $context, $userid),
                'url' => new \moodle_url("/totara/topic/bulk_add.php")
            ]
        );
    }
}