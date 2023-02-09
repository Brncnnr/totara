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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package engage_article
 */

namespace totara_engage\link;

use coding_exception;
use moodle_url;

/**
 * Special case when a destination could not be calculated.
 * Used to prevent the fluent chain throwing fatal errors.
 *
 * @package engage_article\totara_engage\link
 */
final class empty_destination extends destination_generator {
    /**
     * @return array|null
     */
    public function back_button_attributes(): ?array {
        return null;
    }

    /**
     * @return moodle_url
     */
    protected function base_url(): moodle_url {
        throw new coding_exception('Cannot generate a URL for an empty destination');
    }
}