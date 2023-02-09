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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_topic
 */
namespace totara_topic\task;

use core\message\message;
use core\task\adhoc_task;
use core_user;
use totara_topic\hook\get_deleted_topic_usages;
use totara_topic\output\delete_topic_email;
use totara_topic\usage\item;

/**
 * Topic deletion notification task, this adhoc_task will be created for each users, not for each topic usage.
 */
final class delete_notify_task extends adhoc_task {
    /**
     * @param array $components
     * @return item[]
     */
    private function get_items(array $components): array {
        // Start building the resource usages. Note: it is a 3 level nested array.
        $items = [];

        foreach ($components as $component => $instances) {
            foreach ($instances as $item_type => $instance_ids) {
                $hook = new get_deleted_topic_usages($component, $item_type, $instance_ids);
                $hook->execute();

                $results = $hook->get_items();

                if (count($results) !== count($instance_ids)) {
                    debugging("There are missing record(s) for the item's id", DEBUG_DEVELOPER);
                }

                $items = array_merge($items, $results);
            }
        }

        return $items;
    }
    /**
     * @return void
     */
    public function execute() {
        global $CFG, $OUTPUT;

        $data = $this->get_custom_data();
        $keys = ['actor', 'topicvalue', 'components'];

        foreach ($keys as $key) {
            if (!property_exists($data, $key)) {
                debugging("The custom data for the task does not have key '{$key}'");
                return;
            }
        }

        $actor = core_user::get_user($data->actor, '*', MUST_EXIST);

        // Building the list of structure data.
        $components = (array) $data->components;
        $items = $this->get_items($components);

        // Start building a tree data for the affected user.
        $recipients = [];

        foreach ($items as $item) {
            $userid = $item->get_userid();

            if (!isset($recipients[$userid])) {
                $recipients[$userid] = [];
            }

            $recipients[$userid][] = $item;
        }

        require_once("{$CFG->dirroot}/lib/messagelib.php");

        foreach ($recipients as $recipient_id => $items) {
            $recipient = core_user::get_user($recipient_id);
            if (!$recipient) {
                // Skip if user doesn't exist.
                debugging('Skipped sending notification to non-existent user with id ' . $recipient_id);
                continue;
            }
            cron_setup_user($recipient);

            $message = new message();

            $template = delete_topic_email::create($data->topicvalue, $items);
            $messagecontent = $OUTPUT->render($template);

            $message->userfrom = $actor;
            $message->userto = $recipient;
            $message->component = 'totara_topic';
            $message->name = 'deletetopic';
            $message->subject = get_string('topicdeleted', 'totara_topic');
            $message->courseid = SITEID;
            $message->fullmessage = $messagecontent;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = $messagecontent;

            message_send($message);
        }
    }
}