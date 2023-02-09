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
 * @package totara_msteams
 */

namespace totara_msteams\output;

defined('MOODLE_INTERNAL') || die;

use core\output\template;

/**
 * A spinning circle.
 */
final class spinner extends template {
    /**
     * Creating a loading spinner.
     *
     * @param boolean $fullscreen
     * @param string $id
     * @return self
     */
    public static function create_loading(bool $fullscreen = true, string $id = ''): self {
        return new self([
            'id' => $id,
            'fullscreen' => $fullscreen,
            'label' => get_string('spinner_loading', 'totara_msteams'),
        ]);
    }

    /**
     * Creating a loading spinner.
     *
     * @param boolean $fullscreen
     * @param string $id
     * @return self
     */
    public static function create_signingin(bool $fullscreen = true, string $id = ''): self {
        return new self([
            'id' => $id,
            'fullscreen' => $fullscreen,
            'label' => get_string('spinner_signingin', 'totara_msteams'),
        ]);
    }
}
