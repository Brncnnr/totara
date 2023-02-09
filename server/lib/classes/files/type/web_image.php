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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core
 */

namespace core\files\type;

use moodle_url;

class web_image implements file_type {

    /**
     * @inheritDoc
     */
    public function get_category(): string {
        return 'image';
    }

    /**
     * @inheritDoc
     */
    public function get_group(): string {
        return 'web_image';
    }

    /**
     * @inheritDoc
     */
    public function get_valid_extensions(): array {
        return file_get_typegroup('extension', [$this->get_group()]);
    }

    /**
     * @inheritDoc
     */
    public function create_url(string $theme, string $component, string $filename, int $item_id, bool $use_override): moodle_url {
        global $OUTPUT;
        return $OUTPUT->image_url($filename, $component, $use_override);
    }

}