<?php
/*
 * This file is part of Totara LMS
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
 * @package core_user
 */
namespace core_user\hook;

use totara_core\hook\base;

final class edit_form_definition_complete extends base {
    /**
     * @var \user_edit_form
     */
    private $form;

    /**
     * @var int
     */
    private $userid;

    /**
     * edit_form_definition_complete constructor.
     * @param \user_edit_form $form
     * @param int $userid
     */
    public function __construct(\user_edit_form $form, int $userid) {
        $this->form = $form;
        $this->userid = $userid;
    }

    /**
     * @return \user_edit_form
     */
    public function get_form(): \user_edit_form {
        return $this->form;
    }

    /**
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }
}