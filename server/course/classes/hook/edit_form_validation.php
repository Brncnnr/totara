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
 * This hook is called at the end of validation for the course edit form definition, prior to displaying to the user.
 */
class edit_form_validation extends \totara_core\hook\base {

    /**
     * The course edit form instance.
     * @var \course_edit_form
     */
    public $form;

    /**
     * Data submitted during the form submission for validation.
     * @var mixed[]
     */
    public $data;

    /**
     * Files submitted during form submission for validation.
     * @var array[]
     */
    public $files;

    /**
     * Errors found during form validation up to this point.
     * @var string[]
     */
    public $errors;

    /**
     * The edit_form_definition_complete constructor.
     *
     * @param \course_edit_form $form
     * @param mixed[] $data Cannot be modified by observers.
     * @param array[] $files Cannot be modified by observers.
     * @param array[] $errors Passed by reference so that errors can be injected by observers.
     */
    public function __construct(\course_edit_form $form, array $data, array $files, array &$errors) {
        $this->form = $form;
        $this->data = $data;
        $this->files = $files;
        $this->errors =& $errors;
    }
}
