<?php
/**
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
 * @author  Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_core\virtualmeeting
 */

namespace totara_core\virtualmeeting\plugin\provider;

use totara_core\virtualmeeting\dto\meeting_dto;
use totara_core\virtualmeeting\dto\meeting_edit_dto;
use totara_core\virtualmeeting\exception\not_implemented_exception;

/**
 * Virtualmeeting plugin service provider interface, communicates directly with the
 * third-party virtual meeting provider's API
 */
interface provider {
    /** get_info() returns the host URL */
    const INFO_HOST_URL = 'host_url';
    /** get_info() returns the meeting invitation HTML */
    const INFO_INVITATION = 'invitation';
    /** get_info() returns the preview HTML */
    const INFO_PREVIEW = 'preview';

    /**
     * Creates a virtual meeting via API
     *
     * @param meeting_edit_dto $meeting
     */
    public function create_meeting(meeting_edit_dto $meeting): void;

    /**
     * Updates a virtual meeting (or deletes and creates new) via API
     *
     * @param meeting_edit_dto $meeting
     */
    public function update_meeting(meeting_edit_dto $meeting): void;

    /**
     * Deletes a virtual meeting via API
     *
     * @param meeting_dto $meeting
     */
    public function delete_meeting(meeting_dto $meeting): void;

    /**
     * Returns the join URL (if there is one) for a virtual meeting
     * The function MUST return a valid URL starting with https://, if the virtual meeting is available
     *
     * @param meeting_dto $meeting
     * @return string
     */
    public function get_join_url(meeting_dto $meeting): string;

    /**
     * Returns the additional information provided by a virtual meeting provider
     * The function MUST throw not_implemented_exception for any information the virtual meeting provider doesn't have
     *
     * @param meeting_dto $meeting
     * @param string $what one of
     * - host_url: the host URL (if the plugin uses host URLs and there is one) for a virtual meeting
     * - invitation: the invitation in HTML format
     * - preview: the meeting preview in HTML format
     * @return string
     * @throws not_implemented_exception
     */
    public function get_info(meeting_dto $meeting, string $what): string;
}
