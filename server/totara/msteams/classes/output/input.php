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
 * An input field.
 */
final class input extends template {
    /**
     * Create a required field.
     *
     * @param string $id
     * @param string $name
     * @param string $label
     * @param string $errortext
     * @param string $placeholder
     * @param boolean $autofocus
     * @return self
     */
    public static function create_required(string $id, string $name, string $label, string $errortext = '', string $placeholder = '', bool $autofocus = false): self {
        return new self([
            'id' => $id,
            'name' => $name,
            'label' => $label,
            'errortext' => $errortext,
            'placeholder' => $placeholder,
            'required' => true,
            'autofocus' => $autofocus
        ]);
    }

    /**
     * Create a search field.
     *
     * @param string $id
     * @param string $name
     * @param string $label
     * @param string $placeholder
     * @param boolean $autofocus
     * @return self
     */
    public static function create_search(string $id, string $name, string $label, string $placeholder = '', bool $autofocus = false): self {
        return new self([
            'id' => $id,
            'name' => $name,
            'label' => $label,
            'placeholder' => $placeholder,
            'required' => false,
            'autofocus' => $autofocus
        ]);
    }
}
