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

namespace core_course\local\archive_progress_helper\output\navigation;

use core_course\local\archive_progress_helper\current_user as current_user_helper;
use navigation_node;
use pix_icon;
use stdClass;

/**
 * Course administration node for the current user to archive course progress.
 */
class current_user implements course_administration {

    /**
     * Current user helper class.
     *
     * @var current_user_helper
     */
    private $helper;

    /**
     * Constructor.
     *
     * @param stdClass $course
     */
    public function __construct(stdClass $course) {
        $this->helper = new current_user_helper($course);
    }

    /**
     * @inheritDoc
     */
    public function add_node(navigation_node $navigation_node): void {
        $reason = $this->helper->get_unable_to_archive_reason();
        $can_archive_my_progress = is_null($reason);
        if ($can_archive_my_progress) {
            $navigation_node->add(
                get_string('archive_current_user_navigation', 'completion'),
                $this->helper->get_page_output()->get_archive_completion_url(),
                navigation_node::TYPE_SETTING,
                null,
                null,
                new pix_icon('i/settings', '')
            );
        }
    }
}