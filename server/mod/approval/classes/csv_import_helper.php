<?php
/**
* This file is part of Totara Learn
*
* Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_approval
*/

namespace mod_approval;

/** @var core_config $CFG */
require_once($CFG->dirroot . '/lib/csvlib.class.php');

use csv_import_reader;
use moodle_url;

/**
 * Additional import functionality.
 */
class csv_import_helper extends csv_import_reader {

    /**
     * Process code id
     * @var string
     */
    protected $process_id;

    /**
     * $SESSION object name
     * @var string
     */
    private $mod_object = 'mod_approval_';

    /**
     * Prepare list or get list data
     *
     * @param int $process_id identifier of csv processing
     * @param moodle_url|null $returnurl only for first step of list needed for navigation
     * @param string $srctype specifies type of action that this list is being used for, e.g. 'add', 'addfile' etc
     * @param int $action_id
     */
    public function __construct(int $process_id, ?moodle_url $returnurl, string $srctype, int $action_id) {
        global $SESSION;
        $this->process_id = $process_id;
        $this->mod_object .= $srctype;
        if (!isset($SESSION->{$this->mod_object}[$this->process_id])) {
            if ($returnurl) {
                $returnurl = clone($returnurl);
            }
            $SESSION->{$this->mod_object}[$this->process_id] = array(
                'action_id' => $action_id,
                'userdata' => [],
                'returnurl' => $returnurl,
                'srctype' => $srctype
            );
        } else {
            // Check that listid corresponds to its type (if set)
            if (!empty($srctype)) {
                // This shouldn't normally happen (but it can happen if user intentionally put wrong data in browser form.
                if ($SESSION->{$this->mod_object}[$this->process_id]['srctype'] != $srctype) {
                    throw new \coding_exception("Stored user list is incompatible with current list manager.");
                }
            }
        }
        parent::__construct($process_id, $srctype);
    }

    /**
     * Get current process id
     * @return string
     */
    public function get_process_id(): string {
        return $this->process_id;
    }

    /**
     * Get return url.
     * @return \moodle_url $returnurl
     */
    public function get_return_url(): moodle_url {
        global $SESSION;
        return $SESSION->{$this->mod_object}[$this->process_id]['returnurl'];
    }

    /**
     * Get action type.
     * @return string $srctype
     */
    public function get_srctype(): string {
        global $SESSION;
        return $SESSION->{$this->mod_object}[$this->process_id]['srctype'];
    }

    /**
     * Store all users with additional data
     * @param array $userdata
     */
    public function set_all_user_data(array $userdata): void {
        global $SESSION;
        $SESSION->{$this->mod_object}[$this->process_id]['userdata'] = $userdata;
    }

    /**
     * Store all users with additional data
     * @param array $userdata
     */
    public function get_all_user_data(): array {
        global $SESSION;
        if (isset($SESSION->{$this->mod_object}[$this->process_id]['userdata'])) {
            return $SESSION->{$this->mod_object}[$this->process_id]['userdata'];
        }
        return [];
    }

    /**
     * Store user list form data. Used to repopulate the form when user decides to change selected users
     * @param \stdClass $formdata
     */
    public function set_form_data(\stdClass $formdata): void {
        global $SESSION;
        $SESSION->{$this->mod_object}[$this->process_id]['formdata'] = $formdata;
    }

    /**
     * Get previously stored user list form data.
     *
     * Used to repopulate the form when user decides to change selected users.
     *
     * @return array
     */
    public function get_form_data(): array {
        global $SESSION;
        if (isset($SESSION->{$this->mod_object}[$this->process_id]['formdata'])) {
            return (array) $SESSION->{$this->mod_object}[$this->process_id]['formdata'];
        }
        return [];
    }

    /**
     * Remove all data about this list
     */
    public function clean(): void {
        global $SESSION;
        unset($SESSION->{$this->mod_object}[$this->process_id]);
        $this->cleanup();
    }

    /**
     * Save validation results to session.
     *
     * @param array $results
     */
    public function set_validaton_results(array $results): void {
        global $SESSION;
        $SESSION->{$this->mod_object}[$this->process_id]['validation'] = $results;
    }

    /**
     * Load validation results from session.
     *
     * @return array
     */
    public function get_validation_results(): array {
        global $SESSION;
        if (isset($SESSION->{$this->mod_object}[$this->process_id]['validation'])) {
            return $SESSION->{$this->mod_object}[$this->process_id]['validation'];
        }
        return [];
    }

    /**
     * Get current action id
     * @return int
     */
    public function get_action_id(): int {
        global $SESSION;
        return $SESSION->{$this->mod_object}[$this->process_id]['action_id'];
    }

    /**
     * If user's choice is 'automatic' delimiter lets try to find out
     *
     * @param \stdClass $formdata Fields and file submitted by html form
     *      - content file content
     *      - delimiter
     *      data via file
     * @return string
     */
    public static function detect_delimiter(\stdClass $formdata): string {
        // User's choice is auto detect delimiter, lets try it, if failed, return false.
        $detectdelimiter = function($delimiters, $content) {
            foreach($delimiters as $name => $delimiter) {
                $arraydata = str_getcsv($content, $delimiter);
                if (count($arraydata) > 1) {
                    return $name;
                }
            }
            // We can find it, return error
            return false;
        };
        $thedelimiter = $formdata->delimiter == 'auto' ?
            $detectdelimiter(csv_import_reader::get_delimiter_list(), $formdata->content) :
            $formdata->delimiter;
        return $thedelimiter ?: 'comma';
    }

    /**
     * Return a list of csv delimiters.
     *
     * @return array
     */
    public static function get_delimiter_string_list(): array {
        $delimiteroptions['auto'] = get_string('csv_file_delimiter:auto', 'mod_approval');
        $delimiterlist = csv_import_reader::get_delimiter_list();
        // Build delimiter list for UI.
        foreach ($delimiterlist as $name => $delimiter) {
            $delimiteroptions[$name] = get_string('csv_file_delimiter:'.$name, 'mod_approval');
        }

        return $delimiteroptions;
    }
}