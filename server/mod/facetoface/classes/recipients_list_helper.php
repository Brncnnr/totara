<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

/**
 * Class recipients represents Manage recipients for Seminar event message
 */
final class recipients_list_helper {

    /**
     * @var string[] $recipients
     */
    private $recipients = [];

    /**
     * @var \stdClass[]
     */
    private $existingrecipients = [];

    /**
     * @var \stdClass[]
     */
    private $potentialrecipients = [];

    /**
     * Set recipients submitted by $_POST request.
     */
    public function set_recipients(): void {
        $recipients = optional_param('recipients', [], PARAM_SEQUENCE);
        $this->recipients = explode(',', $recipients);
        foreach ($this->recipients as $key => $recipient) {
            if (!$recipient) {
                unset($this->recipients[$key]);
            }
        }
    }

    /**
     * Get recipients.
     * @return string[] recipients
     */
    public function get_recipients(): array {
        return $this->recipients;
    }

    /**
     * Add recipients
     * @param \stdClass $data submitted by $_POST request
     */
    public function add_recipients(\stdClass $data): void {
        if (!empty($data->addselect) && confirm_sesskey()) {
            foreach ($data->addselect as $adduser) {
                if (!$adduser = clean_param($adduser, PARAM_INT)) {
                    continue; // invalid userid
                }
                $this->recipients[] = $adduser;
            }
        }
    }

    /**
     * Remove recipients
     * @param object $data submitted $_POST request
     */
    public function remove_recipients($data): void {
        if (!empty($data->removeselect) and confirm_sesskey()) {
            foreach ($data->removeselect as $removeuser) {
                if (!$removeuser = clean_param($removeuser, PARAM_INT)) {
                    // Invalid userid.
                    continue;
                }
                $this->recipients = array_diff($this->recipients, array($removeuser));
            }
        }
    }

    /**
     * Set the list of current recipients
     * @return recipients_list_helper
     */
    public function set_existing_recipients(): recipients_list_helper {
        global $DB;

        $usernamefields = self::get_all_user_name_fields();
        if ($this->recipients) {
            list($insql, $params) = $DB->get_in_or_equal($this->recipients);
            $sql = "SELECT id, email, idnumber, $usernamefields FROM {user} WHERE id $insql";
            $this->existingrecipients = $DB->get_records_sql($sql, $params);
        }

        return $this;
    }

    /**
     * Get the list of current recipients
     * @return \stdClass[] existing recipients
     */
    public function get_existing_recipients(): array {
        return $this->existingrecipients;
    }

    /**
     * Set all available attendees
     * @param seminar_event $seminarevent
     * @return recipients_list_helper
     */
    public function set_potential_recipients(\mod_facetoface\seminar_event $seminarevent): recipients_list_helper {
        global $DB;

        $usernamefields = self::get_all_user_name_fields();
        $sql  = "SELECT id, email, idnumber, $usernamefields
                   FROM {user}
                  WHERE id IN
                  (
                    SELECT userid FROM {facetoface_signups} WHERE sessionid = ?
                  )
               ORDER BY lastname ASC, firstname ASC";
        // Get all available recipients.
        $availableusers = $DB->get_records_sql($sql, array($seminarevent->get_id()));
        $this->potentialrecipients = array_diff_key($availableusers, $this->existingrecipients);

        return $this;
    }

    /**
     * Get all available attendees
     * @return \stdClass[] potential recipients
     */
    public function get_potential_recipients(): array {
        return $this->potentialrecipients;
    }

    /**
     * Local location for the all name fields to keep consistency with get_selected_users() and get_available_users() methods.
     * @return string All name fields as an SQL fragment
     */
    private static function get_all_user_name_fields(): string {
        static $fields = null;
        if (!is_null($fields)) {
            return $fields;
        }
        $fields = get_all_user_name_fields(true);
        return $fields;
    }
}