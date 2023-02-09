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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\exception;

/**
 * Class workspace_exception
 * @package container_workspace\exception
 */
final class workspace_exception extends \moodle_exception {
    /**
     * workspace_exception constructor.
     * @param string     $error_code
     * @param mixed|null $a
     * @param mixed|null $debug_info
     */
    protected function __construct(string $error_code, $a = NULL, $debug_info = null) {
        parent::__construct($error_code, 'container_workspace', '', $a, $debug_info);
    }

    /**
     * @return workspace_exception
     */
    public static function on_update(): workspace_exception {
        return new static('error:update');
    }

    /**
     * @return workspace_exception
     */
    public static function on_create(): workspace_exception {
        return new static('error:create');
    }

    /**
     * @return workspace_exception
     */
    public static function on_view(): workspace_exception {
        return new static('error:view_workspace');
    }

    /**
     * @return workspace_exception
     */
    public static function on_delete(): workspace_exception {
        return new static('error:delete_workspace');
    }
}