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
 * @package engage_survey
 */
namespace engage_survey\output;

use core\output\template;
use moodle_url;

/**
 * This is an output block for messaging of mention in specific context. Mostly this is used for email's content.
 */
final class vote_message extends template {
    /**
     * @param string $title
     * @param moodle_url $url
     * @return vote_message
     */
    public static function create(string $title, moodle_url $url): vote_message {
        if (!defined('CLI_SCRIPT') || !CLI_SCRIPT) {
            throw new \coding_exception("Cannot instantiate the template for web page usage");
        }

        return new static([
            'message' => get_string('vote_message', 'engage_survey', $title),
            'contexturl' => $url->out(),
            'view' =>  get_string('vote_message_view', 'engage_survey')
        ]);
    }
}