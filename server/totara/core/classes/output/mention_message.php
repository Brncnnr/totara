<?php
/**
 * This file is part of Totara LMS
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_core
 */
namespace totara_core\output;

use core\output\template;

/**
 * This is an output block for messaging of mention in specific context. Mostly this is used for email's content.
 */
final class mention_message extends template {
    /**
     * @param string $message
     * @param string $description
     * @param string $view
     * @param string $contexturl
     *
     * @return mention_message
     */
    public static function create(string $message, string $description, string $view, string $contexturl): mention_message {
        return new static([
            'message' => $message,
            'hascontexturl' => !empty($contexturl),
            'contexturl' => $contexturl,
            'description' => $description,
            'view' => $view,
        ]);
    }
}
