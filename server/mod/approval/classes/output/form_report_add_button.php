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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\output;

use moodle_url;
use core\output\template;
use container_approval\approval as container;
use mod_approval\interactor\category_interactor;
use mod_approval\controllers\form\manage;

/**
 * Used for rendering a container box which only contains a button for create action.
 */
final class form_report_add_button extends template {

    /**
     * @param int|null $userid
     * @return form_report_add_button
     */
    public static function create(?int $userid = null): form_report_add_button {
        global $USER;

        if (null == $userid) {
            $userid = $USER->id;
        }

        // Using the approval container.
        $has_capability = (new category_interactor(
            container::get_default_category_context(),
            $userid
        ))->can_create_workflow();

        return new static(
            [
                'button' => $has_capability,
                'url' => new moodle_url(manage::URL)
            ]
        );
    }
}