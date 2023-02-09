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

namespace totara_msteams\botfw;

/**
 * The base interface for converting X to/from Y.
 */
interface convertible /*<X, Y>*/ {
    /**
     * Convert X to Y.
     *
     * @param object $in type of X
     * @param activity $owner
     * @return object type of Y
     */
    public static function convert_from(/*X*/ $in, activity $owner) /*: Y*/;

    /**
     * Convert Y to X.
     * @param object $in type of Y
     * @return object type of X
     */
    public static function convert_to(/*Y*/ $in) /*: X*/;
}
