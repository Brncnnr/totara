<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_form
 */

namespace totara_form\form\testform;

use totara_form\form\element\editor;
use totara_form\form\group\section;

/**
 * Class element_editor_user_preferred
 *
 * Add user preferred editor form.
 *
 * @package totara_form\form\testform
 */
class element_editor_user_preferred extends form {

    /**
     * Returns the name for this test form.
     *
     * @return string
     */
    public static function get_form_test_name() {
        return 'User preferred editor element';
    }

    /**
     * Returns the current data for this form.
     *
     * @return array
     */
    public static function get_current_data_for_test() {
        return [
            'userpreferrededitorformat' => null,
        ];
    }

    /**
     * Defines the test form.
     */
    public function definition() {
        $section = $this->model->add(new section('userpreferrededitor_section', 'User preferred editor section'));
        $section->add(new editor('userpreferrededitor', 'User preferred editor'));
        $this->add_required_elements();
    }

}
