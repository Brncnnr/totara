<?php
/*
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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @package editor_weka
 */
namespace editor_weka\extension;

// If you are thinking about renaming this class to list, please bump this
// number up by 1.
//
// Number of people that have discovered the hard way that list is a keyword: 3

final class list_extension extends extension {
    /**
     * @return string
     */
    public function get_js_path(): string {
        return 'editor_weka/extensions/list';
    }
}
