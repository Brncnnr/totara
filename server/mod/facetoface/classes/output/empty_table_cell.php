<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package mod_facetoface
 */

namespace mod_facetoface\output;

use html_table_cell;

defined('MOODLE_INTERNAL') || die();

/**
 * An empty table cell.
 */
final class empty_table_cell extends html_table_cell {
    /**
     * Is this cell a placeholder?
     * @var boolean
     */
    public $placeholder = false;

    /**
     * @param boolean $placeholder
     * @param string $class
     */
    private function __construct(bool $placeholder, string $class = '') {
        $this->placeholder = $placeholder;
        if ($class !== '') {
            $this->attributes['class'] = trim($class);
        }
    }

    /**
     * Create an empty table cell.
     * @param string $class
     * @return self
     */
    public static function create_empty(string $class = ''): self {
        return new self(false, $class);
    }

    /**
     * Create a placeholder table cell.
     * @param string $class
     * @return self
     */
    public static function create_placeholder(string $class = ''): self {
        return new self(true, $class);
    }

    /**
     * Is this an empty table cell or a placeholder table cell?
     * @param mixed $cell
     * @return boolean
     */
    public static function is_empty($cell): bool {
        return $cell instanceof empty_table_cell;
    }

    /**
     * Is this a placeholder table cell?
     * @param mixed $cell
     * @return boolean
     */
    public static function is_placeholder($cell): bool {
        return $cell instanceof empty_table_cell && $cell->placeholder;
    }
}
