<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package mod_certificate
 */

namespace mod_certificate\output;

use coding_exception;
use core\output\mustache_filesystem_loader;

class mustache_certificate_loader extends mustache_filesystem_loader {

    /**
     * @inheritDoc
     */
    protected function getFileName($name) {
        global $CFG;

        // Name is in type/filename format.
        list($type, $template_name) = explode('/', $name, 2);
        if (strpos($template_name, '/') !== false) {
            throw new coding_exception('Only certificate type templates allowed');
        }

        // Limit templates to specific folder structure
        $template = "{$CFG->dirroot}/mod/certificate/type/{$type}/templates/{$template_name}.mustache";
        if (!file_exists($template)) {
            throw new coding_exception('Certificate template not found');
        }
        return $template;
    }

}