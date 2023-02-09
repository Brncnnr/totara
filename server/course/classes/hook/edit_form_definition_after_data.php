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
 * @author Peter Spicer <peter.spicer@catalyst-eu.net>
 * @package core_course
 */

namespace core_course\hook;

/**
 * Course edit form definition complete hook.
 *
 * This hook is called at the end of the definition_after_data step of
 * the course editing form so that other plugins can connect to that
 * specific step.
 */
class edit_form_definition_after_data extends \totara_core\hook\base {

    /**
     * The course edit form instance.
     * @var \course_edit_form
     */
    public $form;

    /**
     * The edit_form_definition_after_data constructor.
     *
     * @param \course_edit_form $form
     */
    public function __construct(\course_edit_form $form) {
        $this->form = $form;
    }
}
