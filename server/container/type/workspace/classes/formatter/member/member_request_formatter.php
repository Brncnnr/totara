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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\formatter\member;

use container_workspace\interactor\workspace\interactor;
use container_workspace\member\member_request;
use context;
use core\webapi\formatter\formatter;
use totara_engage\formatter\field\date_field_formatter;
use stdClass;

class member_request_formatter extends formatter {
    /**
     * @param member_request $member_request
     * @param context $context
     */
    public function __construct(member_request $member_request, context $context) {
        $record = new stdClass();

        $record->id = $member_request->get_id();
        $record->is_accepted = $member_request->is_accepted();
        $record->is_declined = $member_request->is_declined();
        $record->workspace_id = $member_request->get_workspace_id();
        $record->request_content = $member_request->get_request_content();
        $record->user = $member_request->get_user();
        $record->time_description = $member_request->get_time_created();
        $record->workspace_interactor = interactor::from_workspace_id(
            $member_request->get_workspace_id(),
            $member_request->get_user_id()
        );

        parent::__construct($record, $context);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        return [
            'id' => null,
            'user' => null,
            'is_accepted' => null,
            'is_declined' => null,
            'workspace_id' => null,
            'workspace_interactor' => null,
            'time_description' => date_field_formatter::class,
            'request_content' => function (string $content): string {
                return format_text($content, FORMAT_PLAIN);
            }
        ];
    }
}