<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_contentmarketplace
 */
use core\orm\query\builder;
require_once(__DIR__ . "/../../../../lib/behat/behat_base.php");


class behat_mod_contentmarketplace extends behat_base {
    /**
     * @Given /^I am on content marketplace index page of course "([^"]*)"$/
     *
     * @param string $course_shortname
     * @return void
     */
    public function I_am_on_content_marketplace_index_page_of_course(string $course_shortname): void {
        $db = builder::get_db();
        $course_id = $db->get_field('course', 'id', ['shortname' => $course_shortname], MUST_EXIST);

        $url = new moodle_url("/mod/contentmarketplace/index.php", ['id' => $course_id]);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));

        $this->wait_for_pending_js();
    }
}