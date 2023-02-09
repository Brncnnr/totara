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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package virtualmeeting_zoom
 */

namespace virtualmeeting_zoom\providers;

use totara_core\http\client;
use totara_core\http\request;
use totara_core\virtualmeeting\exception\meeting_exception;
use totara_core\virtualmeeting\exception\not_implemented_exception;
use totara_core\virtualmeeting\dto\meeting_dto;
use totara_core\virtualmeeting\dto\meeting_edit_dto;
use totara_core\virtualmeeting\plugin\provider\provider;
use virtualmeeting_zoom\constants;

/**
 * Meeting implementation
 */
class meeting implements provider {
    /** @var client */
    private $client;

    /**
     * Constructor.
     *
     * @param client $client
     * @codeCoverageIgnore
     */
    public function __construct(client $client) {
        $this->client = $client;
    }

    /**
     * Get an array of header fields.
     *
     * @param meeting_dto $meeting
     * @return array
     */
    private function get_headers(meeting_dto $meeting): array {
        $auth = $meeting->get_user()->get_fresh_token(auth::create_authoriser($this->client));
        return ['Authorization' => 'Bearer ' . $auth];
    }

    /**
     * Get an array of Zoom meeting properties fields.
     *
     * @param meeting_edit_dto $meeting
     * @return array
     */
    private function get_properties(meeting_edit_dto $meeting): array {
        // Duration is minutes
        $duration = ceil(($meeting->get_timefinish()->format('U') - $meeting->get_timestart()->format('U'))/MINSECS);
        $date = new \DateTime('@' . $meeting->get_timestart()->getTimestamp());
        return [
            'topic' => $meeting->get_name(),
            'type' => constants::MEETING_API_TYPE,
            'start_time' => $date->format(constants::MEETING_DATETIME_FORMAT),
            'duration' => $duration,
            'settings' => [
                'join_before_host' => true,
                'waiting_room' => false,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function create_meeting(meeting_edit_dto $meeting): void {
        // Create the meeting.
        $properties = $this->get_properties($meeting);
        $request = request::post(constants::CREATE_MEETING_API_ENDPOINT, $properties, $this->get_headers($meeting));
        $response = $this->client->execute($request);
        $response->throw_if_error();
        $json = $response->get_body_as_json(false, true);
        $meeting->get_storage()
            ->set('meeting_id', $json->id)
            ->set('join_url', $json->join_url)
            ->set('host_url', $json->start_url);
        if (isset($json->password)) {
            $meeting->get_storage()->set('password', $json->password);
        }
    }

    /**
     * @inheritDoc
     */
    public function update_meeting(meeting_edit_dto $meeting): void {
        $id = $meeting->get_storage()->get('meeting_id');
        $request = request::get(constants::MEETING_API_ENDPOINT . '/' . rawurlencode($id), $this->get_headers($meeting));
        $response = $this->client->execute($request);
        if ($response->get_http_code() === 404) {
            $this->create_meeting($meeting);
            return;
        } else {
            $response->throw_if_error();
        }
        $json = $response->get_body_as_json(false, true);

        $json_start = strtotime($json->start_time);
        $json_finish = strtotime($json->start_time) + ((int)($json->duration)) * 60;

        if ($json->topic == $meeting->get_name() && $json_start == $meeting->get_timestart()->getTimestamp() && $json_finish == $meeting->get_timefinish()->getTimestamp()) {
            // same meeting time, do nothing.
            return;
        }
        $request = request::patch(constants::MEETING_API_ENDPOINT . '/' . rawurlencode($id), $this->get_properties($meeting), $this->get_headers($meeting));
        $response = $this->client->execute($request);
        $response->throw_if_error();
    }

    /**
     * @inheritDoc
     */
    public function delete_meeting(meeting_dto $meeting): void {
        $id = $meeting->get_storage()->get('meeting_id');
        if ($id) {
            $request = request::delete(constants::MEETING_API_ENDPOINT . '/' . rawurlencode($id), $this->get_headers($meeting));
            $this->client->execute($request);
        }
        $meeting->get_storage()->delete_all();
    }

    /**
     * @inheritDoc
     */
    public function get_join_url(meeting_dto $meeting): string {
        $url = $meeting->get_storage()->get('join_url');
        if ($url !== null) {
            return $url;
        }
        throw new meeting_exception('join url not set');
    }

    /**
     * @inheritDoc
     */
    public function get_info(meeting_dto $meeting, string $what): string {
        global $USER;
        if ($what === provider::INFO_HOST_URL) {
            // Check the stored URL to see that there was one when the meeting was created.
            $stored_url = $meeting->get_storage()->get('host_url');
            if ($stored_url) {
                // Host URL should only be revealed to the meeting owner.
                if ($meeting->get_userid() != $USER->id) {
                    throw new meeting_exception('host url not available');
                }
                $url = new \moodle_url('/integrations/virtualmeeting/zoom/host.php', ['meetingid' => $meeting->get_id()]);
                return $url;
            }
            throw new meeting_exception('host url not set');
        }
        throw new not_implemented_exception();
    }

    /**
     * Call out to Zoom for the actual host URL
     *
     * @param meeting_dto $meeting
     * @return string
     */
    public function get_real_host_url(meeting_dto $meeting): string {
        global $USER;
        if ($meeting->get_userid() != $USER->id) {
            throw new meeting_exception('host url not available');
        }
        // Get a fresh, new host URL from Zoom
        $id = $meeting->get_storage()->get('meeting_id');
        $request = request::get(constants::MEETING_API_ENDPOINT . '/' . rawurlencode($id), $this->get_headers($meeting));
        $response = $this->client->execute($request);
        $response->throw_if_error();
        $json = $response->get_body_as_json(false, true);
        if (isset($json->start_url)) {
            return $json->start_url;
        }
        throw new meeting_exception('host url not set');
    }
}
