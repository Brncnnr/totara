<?php
/**
 * This file is part of Totara LMS
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package core
 */

namespace core\hook;

defined('MOODLE_INTERNAL') || die();

/**
 * initialize page body classes hook
 *
 * This hook is called while the page is setting up initial classes,
 * allowing plugins to inject base classes as required.
 *
 * @package core\hook
 */
class page_initialize_body_classes extends \totara_core\hook\base {
    /**
     * @var \moodle_page
     */
    private $page;

    /**
     * The page_initialize_body_classes constructor.
     *
     * @param \moodle_page $page
     */
    public function __construct(\moodle_page &$page) {
        $this->page = &$page;
    }

    /**
     * @return \moodle_page
     */
    public function get_page(): \moodle_page {
        return $this->page;
    }
}
