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
 * Class discussion_exception
 * @package container_workspace\exception
 */
final class discussion_exception extends \moodle_exception {
    /**
     * discussion_exception constructor.
     * @param string                $errorcode
     * @param null|array|\stdClass  $a
     * @param null|array            $debug_info
     */
    private function __construct(string $errorcode, $a = null, $debug_info = null) {
        parent::__construct($errorcode, 'container_workspace', '', $a, $debug_info);
    }

    /**
     * @return discussion_exception
     */
    public static function on_delete(): discussion_exception {
        return new static('error:delete_discussion');
    }

    /**
     * @param string|null $debug_info
     * @return discussion_exception
     */
    public static function on_create(?string $debug_info = null): discussion_exception {
        return new static('error:create_discussion', null, $debug_info);
    }

    /**
     * @param string|null $debug_info
     * @return discussion_exception
     */
    public static function on_update(?string $debug_info = null): discussion_exception {
        return new static('error:update_discussion', null, $debug_info);
    }
}