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

namespace totara_msteams\botfw;

use lang_string;

/**
 * An interface that provides help texts.
 */
interface describable {
    /**
     * Get the name of the command.
     *
     * @return lang_string|null
     */
    public function get_name(): ?lang_string;

    /**
     * Get the short description of the command.
     *
     * @return string
     */
    public function get_description(): lang_string;

    /**
     * Get the long description of the command.
     *
     * @return lang_string|null
     */
    public function get_long_description(): ?lang_string;
}
