<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Gihan Hewaralalage <gihan.hewaralalage@totara.com>
 * @package core
 */

namespace core\form;
defined('MOODLE_INTERNAL') || die();

use moodleform;

require_once($CFG->libdir.'/formslib.php');

/**
 * Moodle 404 Error page feedback form
 *
 * @package    core
 */
class error_feedback extends moodleform {

    /**
     * Error form definition
     */
    public function definition() {
        $http_referer = get_local_referer(false);
        $request_uri  = empty($_SERVER['REQUEST_URI'])  ? '' : $_SERVER['REQUEST_URI'];

        $mform = $this->_form;
        $mform->addElement('hidden', 'referer', $http_referer);
        $mform->setType('referer', PARAM_URL);

        $mform->addElement('hidden', 'requested', $request_uri);
        $mform->setType('requested', PARAM_URL);

        $mform->addElement('textarea', 'text', get_string('pleasereport', 'error'), 'wrap="virtual" rows="10" cols="50"');
        $mform->addElement('submit', 'submitbutton', get_string('sendmessage', 'error'));
    }
}