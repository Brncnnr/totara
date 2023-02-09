<?php
/**
 * This file is part of Totara Learn
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_course
 */
namespace core_course\hook;

use totara_core\hook\base;

/**
 * A hook to redirect from page '/course/index.php?categoryid=someid' where the category's id is
 * the id of category that you are not desire to display it in the page.
 */
final class course_category_index_view extends base {
    /**
     * @var int
     */
    private $category_id;

    /**
     * course_category_management_view constructor.
     * @param int $category_id
     */
    public function __construct(int $category_id) {
        $this->category_id = $category_id;
    }
}