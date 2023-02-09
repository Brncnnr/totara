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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\task;

use container_workspace\member\member;
use container_workspace\member\status;
use container_workspace\notification\workspace_notification;
use container_workspace\workspace;
use core\message\message;
use core\orm\query\builder;
use core\orm\query\raw_field;
use core\task\adhoc_task;
use core_user;

final class add_content_task extends adhoc_task {

    /**
     * @return void
     */
    public function execute() {
        $data = $this->get_custom_data();
        $keys = ['component', 'workspace_id', 'sharer_id', 'item_name'];

        foreach ($keys as $key) {
            if (!property_exists($data, $key)) {
                debugging("The custom data for the task does not have key '{$key}'");
                return;
            }
        }

        $sharer = core_user::get_user($data->sharer_id, '*', MUST_EXIST);
        $members = $this->get_members_for_notification($data->workspace_id, $data->sharer_id);

        $workspace = workspace::from_id($data->workspace_id);
        $html_url = \html_writer::tag(
            'a',
            $workspace->get_workspace_url('library')->out(false),
            ['href' => $workspace->get_workspace_url('library')->out(false)]
        );

        foreach ($members as $member) {
            $member_userid = $member->get_member_user_id();
            $recipient = core_user::get_user($member_userid);
            if (!$recipient) {
                // Skip if user doesn't exist.
                debugging('Skipped sending notification to non-existent user with id ' . $member_userid);
                continue;
            }

            if (workspace_notification::is_off($workspace->get_id(), $member_userid)) {
                // User had turned off the workspace notification. Skip the process of sending message out.
                continue;
            }

            cron_setup_user($recipient);

            $message = new message();
            $message_data = new \stdClass();
            $message_data->fullname = fullname($sharer);
            $message_data->item_name = $data->item_name;
            $message_data->workspace_name = format_string($workspace->get_name());
            $message_data->url = $html_url;
            $message_content = get_string('message_content_added', 'container_workspace', $message_data);

            $message->courseid = $workspace->get_id();
            $message->component = 'container_workspace';
            $message->name = 'notification';
            $message->userfrom = $sharer;
            $message->userto = $recipient;
            $message->subject = get_string('message_content_added_subject', 'container_workspace', $data->component);
            $message->fullmessage = html_to_text($message_content);
            $message->fullmessageformat = FORMAT_HTML;
            $message->fullmessagehtml = $message_content;
            $message->notification = 1;

            message_send($message);
        }
    }

    /**
     * All workspace members have to get notification, except for sharer and members that are inactive
     * @param int $workspace_id
     * @param int $sharer_id
     * @return array|member[]
     */
    public function get_members_for_notification(int $workspace_id, int $sharer_id): array {
        $builder = builder::table('user_enrolments', 'ue');
        $builder->join(['enrol', 'e'], 'ue.enrolid', 'e.id');
        $builder->join(['user', 'u'], 'ue.userid', 'u.id');

        $builder->where('e.courseid', $workspace_id);
        $builder->where('ue.status', status::get_active());
        $builder->where('u.id', '<>', $sharer_id);
        $builder->map_to([member::class, 'from_record']);

        $builder->select([
            "ue.*",
            new raw_field("e.courseid as workspace_id")
        ]);

        /** @var member[] $members */
        $members = $builder->fetch();

        return $members;
    }
}