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
 * @package totara_engage
 */
namespace totara_engage\output;

use core\output\template;
use moodle_url;

/**
 * This is an output block for messaging of mention in specific context. Mostly this is used for email's content.
 */
final class like_message extends template {
    /**
     * @param \stdClass $message_body
     * @return like_message
     */
    public static function create(\stdClass $message_body): like_message {
        if (!defined('CLI_SCRIPT') || !CLI_SCRIPT) {
            throw new \coding_exception("Cannot instantiate the template for web page usage");
        }

        return new static([
            'message' => get_string('like_message', 'totara_engage', $message_body),
            'contexturl' => $message_body->url->out(),
            'view' =>  get_string('like_message_view', 'totara_engage', $message_body->type)
        ]);
    }
}