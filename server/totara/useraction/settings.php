<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Chester <simon.chester@totara.com>
 * @package totara_useraction
 */

if ($hassiteconfig || has_capability('totara/useraction:manage_actions', \context_system::instance())) {
    // try and place page just after "Bulk user actions"
    $before = null;
    if ($ADMIN->locate('userdefaultpreferences')) {
        $before = 'userdefaultpreferences';
    }

    $ADMIN->add(
        'users',
        new admin_externalpage(
            'totara_useraction_scheduled_actions',
            new lang_string('scheduled_user_actions', 'totara_useraction'),
            $CFG->wwwroot . '/totara/useraction/scheduled_actions.php',
            ['totara/useraction:manage_actions']
        ),
        $before,
    );
}
