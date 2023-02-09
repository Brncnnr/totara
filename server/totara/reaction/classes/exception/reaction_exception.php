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
 * @package totara_reaction
 */
namespace totara_reaction\exception;

/**
 * Class reaction_exception
 * @package totara_reaction\exception
 */
final class reaction_exception extends \moodle_exception {
    /**
     * reaction_exception constructor.
     * @param $errorcode
     * @param string $link
     * @param null $a
     * @param null $debuginfo
     */
    protected function __construct($errorcode, $link = '', $a = null, $debuginfo = null) {
        parent::__construct($errorcode, 'totara_reaction', $link, $a, $debuginfo);
    }

    /**
     * @return reaction_exception
     */
    public static function on_delete(): reaction_exception {
        return new static('error:delete');
    }

    /**
     * @return reaction_exception
     */
    public static function on_view(): reaction_exception {
        return new static('error:view');
    }
}