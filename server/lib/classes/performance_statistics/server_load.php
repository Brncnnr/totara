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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core\performance_statistics;

class server_load extends provider {

    /**
     * @inheritDoc
     */
    public function get_data() {
        $data = null;
        // Grab the load average for the last minute.
        // /proc will only work under some linux configurations
        // while uptime is there under MacOSX/Darwin and other unices.
        if (is_readable('/proc/loadavg') && $loadavg = @file('/proc/loadavg')) {
            list($data) = explode(' ', $loadavg[0]);
            unset($loadavg);
        } else if (function_exists('is_executable') && is_executable('/usr/bin/uptime') && $loadavg = `/usr/bin/uptime`) {
            if (preg_match('/load averages?: (\d+[\.,:]\d+)/', $loadavg, $matches)) {
                $data = $matches[1];
            } else {
                trigger_error('Could not parse uptime output!');
            }
        }

        return $data;
    }

}