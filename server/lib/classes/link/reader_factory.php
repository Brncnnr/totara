<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package core
 */

namespace core\link;

final class reader_factory {
    /**
     * reader_factory constructor.
     */
    private function __construct() {
        // Prevent the construction directly.
    }

    /**
     * @param string $url
     * @return string
     */
    public static function get_reader_classname(string $url): string {
        // Get simple vimeo reader class.
        if (preg_match('/^https?:\/\/(?:www\.)?vimeo.com\/([0-9]+)/', $url)) {
            return vimeo_reader::class;
        }

        return metadata_reader::class;
    }
}