<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara_customfield
 */

namespace totara_customfield\prefix;
defined('MOODLE_INTERNAL') || die();

abstract class hierarchy_type extends type_base {

    use unique_type;

    /**
     * Create a new hierarchy type.
     *
     * @param string $prefix
     * @param string $context
     * @param array $extrainfo
     */
    public function __construct($prefix, $context, $extrainfo = array()) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/lib.php');

        $shortprefix = \hierarchy::get_short_prefix($prefix);
        if ($extrainfo["class"] == 'personal') {
            $tableprefix = $shortprefix.'_user';
        } else {
            $tableprefix = $shortprefix.'_type';
        }
        parent::__construct($prefix, $tableprefix, $shortprefix, $context, $extrainfo);
    }
}
