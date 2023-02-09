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
 * @package virtualmeeting_msteams
 */

namespace virtualmeeting_msteams\providers;

use core\entity\user;
use DateTime;
use totara_core\http\client;
use totara_core\http\request;
use totara_core\util\language;
use totara_core\virtualmeeting\exception\meeting_exception;
use totara_core\virtualmeeting\exception\not_implemented_exception;
use totara_core\virtualmeeting\dto\meeting_dto;
use totara_core\virtualmeeting\dto\meeting_edit_dto;
use totara_core\virtualmeeting\plugin\provider\provider;
use virtualmeeting_msteams\constants;

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
        $user = new user($meeting->get_user()->get_userid());
        $lang = language::convert_to_ietf_format($user->lang);
        return ['Authorization' => 'Bearer ' . $auth, 'Accept-Language' => $lang];
    }

    /**
     * @inheritDoc
     */
    public function create_meeting(meeting_edit_dto $meeting): void {
        $request = request::post(constants::MEETING_API_ENDPOINT, [
            'startDateTime' => $meeting->get_timestart()->format(DateTime::RFC3339_EXTENDED),
            'endDateTime' => $meeting->get_timefinish()->format(DateTime::RFC3339_EXTENDED),
            'subject' => $meeting->get_name(),
        ], $this->get_headers($meeting));
        $response = $this->client->execute($request);
        $response->throw_if_error();
        $json = $response->get_body_as_json(false, true);
        $meeting->get_storage()
            ->set('meeting_id', $json->id)
            ->set('join_url', $json->joinWebUrl);
        if (isset($json->joinInformation->content)) {
            $preview = urldecode(explode(',', $json->joinInformation->content, 2)[1]);
            $meeting->get_storage()->set('preview', $preview);
        } else {
            $meeting->get_storage()->set('preview', '');
        }
    }

    /**
     * @inheritDoc
     */
    public function update_meeting(meeting_edit_dto $meeting): void {
        // create a new meeting, then delete the old one.
        $id = $meeting->get_storage()->get('meeting_id');
        $this->create_meeting($meeting);

        $request = request::delete(constants::MEETING_API_ENDPOINT . '/' . rawurlencode($id), $this->get_headers($meeting));
        $response = $this->client->execute($request);
        // ignore the result
    }

    /**
     * @inheritDoc
     */
    public function delete_meeting(meeting_dto $meeting): void {
        $id = $meeting->get_storage()->get('meeting_id');
        if ($id) {
            $request = request::delete(constants::MEETING_API_ENDPOINT . '/' . rawurlencode($id), $this->get_headers($meeting));
            $response = $this->client->execute($request);
            // ignore the result
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
        if ($what === provider::INFO_PREVIEW) {
            $preview = $meeting->get_storage()->get('preview');
            if ($preview !== null) {
                // Remove some cosmetic junk.
                $preview = preg_replace('#<div[^>]*?>\s*<span[^>]*?>_+</span>\s*</div>#', '', $preview);
                return $preview;
            }
            throw new meeting_exception('preview not set');
        }
        throw new not_implemented_exception();
    }
}
