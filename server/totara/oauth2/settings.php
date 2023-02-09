<?php
/**
 * This file is part of Totara Core
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_oauth2
 */

defined('MOODLE_INTERNAL') || die;

if (has_capability('totara/oauth2:manageproviders', $systemcontext)) {
    $ADMIN->add(
        'oauth2services',
        new admin_externalpage(
            'oauth2providerdetails',
            new lang_string('oauth2providerdetails', 'totara_oauth2'),
            "$CFG->wwwroot/totara/oauth2/oauth2_provider.php",
            ['totara/oauth2:manageproviders']
        )
    );
}