<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 Totara Learning Solutions Ltd
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package message_msteams
 */
namespace message_msteams\task;

use totara_core\advanced_feature;
use totara_msteams\botfw\builder;

defined('MOODLE_INTERNAL') || die();

class send_user_message_adhoc_task extends \core\task\adhoc_task {
    /**
     * Send out messages formatted for given user
     */
    public function execute() {
        // Prevent any messages from being sent/tasks from running if teams is disabled
        if (advanced_feature::is_disabled('totara_msteams')) {
            return;
        }

        $data = unserialize($this->get_custom_data());
        $userid = $data->useridto;

        if (defined('PHPUNIT_TEST')) {
            $trace = new \null_progress_trace();
        } else {
            $trace = new \text_progress_trace();
        }

        $trace->output('Send notification to user id: ' . $userid);

        if (!empty($data->fullmessagehtml)) {
            // Keep only HTML tags MS Teams definitely supports, except the <img> tag.
            // https://docs.microsoft.com/en-us/microsoftteams/platform/task-modules-and-cards/cards/cards-format#formatting-cards-with-html
            $text = strip_tags($data->fullmessagehtml, '<u><s><b><i><strong><em><strike><h1><h2><h3><ul><ol><li><pre><blockquote><br><a>');
        } else {
            $fullorsmall = empty($data->fullmessage) ? $data->smallmessage : $data->fullmessage;
            $text = str_replace("\n", '<br>', $fullorsmall);
        }
        // Build a message.
        $message = builder::message()
            ->add_attachment(builder::hero_card()
                ->title($data->subject)
                ->text($text)
                ->build())
            ->summary($data->subject)
            ->build();
        // Send the message.
        $bot = builder::bot()->build();
        if (!$bot->send_notification($userid, $message)) {
            $trace->output('Cannot find any subscription for user id: ' . $userid);
        }

        $trace->finished();
    }
}
