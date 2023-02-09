<?php
/*
 * This file is part of Totara Learn
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort\rules\ui;

defined('MOODLE_INTERNAL') || die();

use \totara_cohort\rules\ui\menu as menu;

class tenant_member extends menu {
    /**
     * tenant_member constructor.
     * @param string $description
     */
    public function __construct($description) {
        parent::__construct($description, null);
        $this->init();
    }

    /**
     * @return void
     */
    private function init(): void {
        global $DB;
        if (!isset($this->options)) {
            $tenants = $DB->get_records_menu('tenant', [], 'name ASC', 'id,name');
            $this->options = array_map('format_string', $tenants);
        }
    }

    /**
     * A method for validating the form submitted data
     * @return bool
     */
    public function validateResponse() {
        /** @var \core_renderer $OUTPUT */
        global $OUTPUT;
        $form = $this->constructForm();
        if  ($data = $form->get_submitted_data()) {
            $success = !empty($data->listofvalues);
            // Checking whether the listofvalues being passed is empty or not. If it is empty, error should be returned
            if (!$success) {
                $form->_form->addElement('html',
                    $OUTPUT->notification(get_string('msg:missing_tenant', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                );
            }
            return $success;
        }

        // If the form is not submitted at all, then there is no point to validate and false should be returned here
        return false;
    }
}
