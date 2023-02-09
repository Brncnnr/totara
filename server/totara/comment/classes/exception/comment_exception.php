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
 * @package totara_comment
 */
namespace totara_comment\exception;

final class comment_exception extends \moodle_exception {
    /**
     * comment_exception constructor.
     *
     * @param string        $errorcode
     * @param null|mixed    $a
     * @param null|mixed    $debuginfo
     */
    public function __construct($errorcode, $a = null, $debuginfo = null) {
        if (false === stripos($errorcode, "error:")) {
            $errorcode = "error:{$errorcode}";
        }

        parent::__construct($errorcode, 'totara_comment', '', $a, $debuginfo);
    }

    /**
     * @param string|null $debug_info
     * @return comment_exception
     */
    public static function on_update(?string $debug_info = null): comment_exception {
        return new static('update', null, $debug_info);
    }

    /**
     * @param string|null $debug_info
     * @return comment_exception
     */
    public static function on_create(?string $debug_info = null): comment_exception {
        return new static('create', null, $debug_info);
    }

    /**
     * @return comment_exception
     */
    public static function on_soft_delete(): comment_exception {
        return new static('softdelete');
    }

    /**
     * @return comment_exception
     */
    public static function on_access_denied(): comment_exception {
        return new static('accessdenied');
    }
}