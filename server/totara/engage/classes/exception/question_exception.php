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
 * @package totara_engage
 */
namespace totara_engage\exception;

/**
 * Exception for question basic elementals.
 */
final class question_exception extends \moodle_exception {
    /**
     * question_exception constructor.
     *
     * @param string        $errorcode
     * @param string        $link
     * @param null|mixed    $debuginfo
     */
    protected function __construct(string $errorcode, string $link = '', $debuginfo = null) {
        parent::__construct($errorcode, 'totara_engage', $link, null, $debuginfo);
    }

    /**
     * @param string        $link
     * @param null|mixed    $debuginfo
     *
     * @return question_exception
     */
    public static function on_create(string $link = '', $debuginfo = null): question_exception {
        return new static('error:createquestion', $link, $debuginfo);
    }

    /**
     * @param string        $link
     * @param null|mixed    $debuginfo
     *
     * @return question_exception
     */
    public static function on_delete(string $link = '', $debuginfo = null): question_exception {
        return new static('error:deletequestion', $link, $debuginfo);
    }

    /**
     * @param string        $link
     * @param null|mixed    $debuginfo
     *
     * @return question_exception
     */
    public static function on_update(string $link = '', $debuginfo = null): question_exception {
        return new static('error:updatequestion', $link, $debuginfo);
    }
}