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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package enrol_totara_learningplan
 */

namespace enrol_totara_learningplan;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class enrol_form extends \moodleform {
    protected $instance;

    public function definition($mform = null) {
        global $OUTPUT, $USER;

        if ($mform === null) {
            $mform = $this->_form;
        }
        $instance = $this->_customdata;
        $this->instance = $instance;
        $plugin = enrol_get_plugin('totara_learningplan');

        $heading = $plugin->get_instance_name($instance);
        $mform->addElement('header', 'totara_learningplanheader', $heading);

        // This isn't an approved course in their learning plan or learning plan isn't approved
        $link = $OUTPUT->action_link(new \moodle_url('/totara/plan/index.php', array('userid' => $USER->id)), get_string('learningplan', 'enrol_totara_learningplan'));
        $noplantext = get_string('notpermitted', 'enrol_totara_learningplan', $link);

        $mform->addElement('static', 'totara_learningplantext', '', $noplantext);
    }
}
