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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */


namespace mod_facetoface\attendance;
defined('MOODLE_INTERNAL') || die();
use mod_facetoface\seminar_event;
use coding_exception;
use moodle_exception;
use mod_facetoface\export_helper;

/**
 * Class download_attendance_tracking
 * @package mod_facetoface\attendance
 */
final class download_attendance_tracking implements attendance_tracking {
    /**
     * @var int
     */
    private $sessiondateid;

    /**
     * @var seminar_event
     */
    private $seminarevent;

    /**
     * @var string
     */
    private $download;

    /**
     * @var string
     */
    private $action;

    /**
     * download_attendance_tracking constructor.
     * @param seminar_event $seminarevent
     * @param string $download
     */
    public function __construct(seminar_event $seminarevent, string $download) {
        $this->seminarevent = $seminarevent;
        $this->download = $download;
        $this->sessiondateid = 0;
        $this->action = 'takeattendance';
    }

    /**
     * @param int $sessiondateid
     * @return download_attendance_tracking
     */
    public function set_sessiondate_id(int $sessiondateid): download_attendance_tracking {
        $this->sessiondateid = $sessiondateid;
        return $this;
    }

    /**
     * Generate a content for downloading, however, when the downloadable content is being done
     * by a library function, then the whole script will get shutdown. But to be compatible with
     * this method return statement, empty string need to be returned.
     *
     * @return string
     */
    public function generate_content(): string {
        $generator = null;
        if ($this->sessiondateid > 0) {
            $generator = new session_content($this->seminarevent, $this->action);
        } else {
            $generator = new event_content($this->seminarevent, $this->action);
        }

        $helper = new attendance_helper();
        $rows = $helper->get_attendees($this->seminarevent->get_id(), $this->sessiondateid);

        $contents = $generator->generate_downloadable_content($rows, $this->download);
        if (!isset($contents['headers']) || !isset($contents['rows'])) {
            throw new coding_exception(
                "Expecting function load_downloadable_content to return an array" .
                " with keys 'headers' and 'rows'"
            );
        }

        // Default event taking attendance file name.
        $exportfilename = $this->action;
        if ($this->sessiondateid > 0) {
            $exportfilename .= " - {$this->sessiondateid}";
        }

        $headers = $contents['headers'];
        $rows = $contents['rows'];

        switch ($this->download) {
            case 'ods':
                export_helper::download_ods($headers, $rows, $exportfilename);
                break;
            case 'xls':
                export_helper::download_xls($headers, $rows, $exportfilename);
                break;
            case 'csv':
            case 'csvforupload':
                export_helper::download_csv($headers, $rows, $exportfilename);
                break;
            default:
                throw new moodle_exception('nodownloadfiletype', 'mod_facetoface');
        }

        return '';
    }
}