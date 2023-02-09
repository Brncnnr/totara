<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package mod_scorm
 */

namespace mod_scorm\form;

defined('MOODLE_INTERNAL') || die();

/**
 * Confirmation deleting of package trust
 */
final class trusted_delete_confirm extends \totara_form\form {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        $currentdata = (object)$this->model->get_current_data(null);

        $a = new \stdClass();
        $a->contenthash = $currentdata->contenthash;

        $confirmhtml = get_string('packagedeletetrustconfirm', 'mod_scorm', $a);
        $confirmhtml = markdown_to_html($confirmhtml);
        $confirmstatic = new \totara_form\form\element\static_html('confirmstatic', '', $confirmhtml);
        $this->model->add($confirmstatic);

        $this->model->add_action_buttons(true, get_string('packagedeletetrust', 'mod_scorm'));

        $this->model->add(new \totara_form\form\element\hidden('contenthash', PARAM_ALPHANUM));
        $this->model->add(new \totara_form\form\element\hidden('reportid', PARAM_INT));
    }
}
