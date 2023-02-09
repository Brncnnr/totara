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

namespace totara_msteams\botfw\validator;

use totara_msteams\botfw\activity;
use totara_msteams\botfw\context;

/**
 * An interface to validate an incoming request.
 */
interface validator {
    /**
     * Validate an activity.
     *
     * @param context $context
     * @param activity $activity
     * @return bool
     */
    public function validate_activity(context $context, activity $activity): bool;

    /**
     * Validate an HTTP header.
     *
     * @param context $context
     * @param string[] $headers of [field_name => field_value]
     * @return bool
     */
    public function validate_header(context $context, array $headers): bool;
}
