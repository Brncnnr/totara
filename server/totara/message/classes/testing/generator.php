<?php
/**
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_message
 */

namespace totara_message\testing;

use stdClass;

final class generator extends \core\testing\component_generator {


    /**
     * @param string $fromuser Sender
     * @param string $touser Receiver
     * @param string $description Text of the message
     */
    public function create_alert(string $fromuser, string $touser, string $description, array $parameter = []) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/message/messagelib.php');

        $eventdata = new stdClass();
        $eventdata->userfrom = $DB->get_record('user', ['username' => $fromuser]);
        $eventdata->userto = $DB->get_record('user', ['username' => $touser]);
        $eventdata->contexturl = $CFG->wwwroot . '/';
        $eventdata->icon = 'default';
        $eventdata->subject = $description;
        $eventdata->fullmessage = $description;
        $eventdata->fullmessagehtml = '<div style="color:#ff0000">' . $description . '</div>';
        if (!empty($parameter['subject'])) {
            $eventdata->subject = $parameter['subject'];
        }

        tm_alert_send($eventdata);
    }

    /**
     * This is a callback from behat data generators.
     *
     * @param array $parameters
     */
    public function create_alert_from_params(array $parameters) {
        if (empty($parameters['fromuser'])) {
            throw new \coding_exception("Alerts require fromuser column");
        }
        if (empty($parameters['touser'])) {
            throw new \coding_exception("Alerts require touser column");
        }
        if (empty($parameters['description'])) {
            throw new \coding_exception("Alerts require description column");
        }

        $this->create_alert($parameters['fromuser'], $parameters['touser'], $parameters['description'], $parameters);
    }
}