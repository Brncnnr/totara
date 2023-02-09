<?php
/*
 * This file is part of Totara Learn
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

namespace totara_core;

use totara_core\local\visibility\cache;
use totara_core\local\visibility\map;
use totara_core\local\visibility\resolver;

/**
 * Visibility controller class
 *
 * This class provides an optimised way to get course, programs and certifications that
 * are visible to the user, as well as counts of those things per category, or for the site.
 *
 * Please note that this is an adviser and should not be used to replace access checks on individual
 * items that should be made when actually accessing those items.
 *
 * @package totara_core
 */
final class visibility_controller {

    /**
     * Returns an array of the types this visibility controller can advise on.
     *
     * @return string[]
     */
    public static function types(): array {
        return [
            'certification',
            'course',
            'program',
        ];
    }

    /**
     * Returns an array of maps, one for each type this visibility controller supports.
     *
     * @return map[]
     */
    public static function get_all_maps(): array {
        $maps = [];
        foreach (self::types() as $type) {
            $maps[$type] = self::get($type)->map();
        }
        return $maps;
    }

    /**
     * Gets a visibility resolver that can be used to get visible items and counts.
     *
     * @param string $type The type of the controller you want.
     * @param \cache_loader|null $cache If you want the results to be cached pass in the cache instance that is
     *     responsible for storing the data.
     * @return resolver
     * @throws \coding_exception
     */
    public static function get(string $type, ?\cache_loader $cache = null): resolver {
        global $CFG;
        if (!in_array($type, self::types())) {
            throw new \coding_exception('Unknown visibility controller type', $type);
        }
        $class =  '\totara_core\local\visibility\\' . $type . '\\';
        if (empty($CFG->audiencevisibility)) {
            $class .= 'traditional';
        } else {
            $class .= 'audiencebased';
        }
        $resolver = new $class();
        if (is_null($cache)) {
            return $resolver;
        }
        return new cache($resolver, $cache);
    }

    /**
     * Returns the course visibility controller
     *
     * This is just a convenience method.
     *
     * @return \totara_core\local\visibility\course\traditional|\totara_core\local\visibility\course\audiencebased
     */
    public static function course(?\cache_loader $cache = null) : resolver {
        return self::get('course', $cache);
    }

    /**
     * Returns the program visibility controller
     *
     * This is just a convenience method.
     *
     * @return \totara_core\local\visibility\program\traditional|\totara_core\local\visibility\program\audiencebased
     */
    public static function program(?\cache_loader $cache = null) : resolver {
        return self::get('program', $cache);
    }

    /**
     * Returns the certification visibility controller
     *
     * This is just a convenience method.
     *
     * @return \totara_core\local\visibility\certification\traditional|\totara_core\local\visibility\certification\audiencebased
     */
    public static function certification(?\cache_loader $cache = null) : resolver {
        return self::get('certification', $cache);
    }

}