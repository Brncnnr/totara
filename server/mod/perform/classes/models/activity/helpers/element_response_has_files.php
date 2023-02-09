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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity\helpers;

/**
 * Interface for a respondable element plugin that supports file responses.
 * This must be implemented in order to handle userdata purging.
 *
 * @package mod_perform\models\activity\helpers
 */
interface element_response_has_files {

    /**
     * Get the component name for where response files are to be stored.
     *
     * @return string
     */
    public static function get_response_files_component_name(): string;

    /**
     * Get the file area name for where response files are to be stored.
     *
     * @return string
     */
    public static function get_response_files_filearea_name(): string;

}
