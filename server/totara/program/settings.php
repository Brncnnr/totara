<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_program
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();
/** @var admin_root $ADMIN */
/** @var context_system $systemcontext */

$programsenabled = advanced_feature::is_disabled('programs');

if (has_any_capability(['totara/program:createprogram', 'totara/program:configuredetails'], $systemcontext)) {
    $ADMIN->add('programs', new admin_externalpage('programmgmt',
        new lang_string('manageprograms', 'admin'),
        $CFG->wwwroot . '/totara/program/manage.php',
        ['totara/program:createprogram', 'totara/program:configuredetails'],
        $programsenabled
    ));
} else if (!empty($USER->tenantid)) {
    $tenant = core\record\tenant::fetch($USER->tenantid);
    $categorycontext = context_coursecat::instance($tenant->categoryid);
    if (has_any_capability(['totara/program:createprogram', 'totara/program:configuredetails'], $categorycontext)) {
        $ADMIN->add('programs', new admin_externalpage('programmgmt',
            new lang_string('manageprograms', 'admin'),
            $CFG->wwwroot . '/totara/program/manage.php?viewtype=program&categoryid=' . $tenant->categoryid,
            ['totara/program:createprogram', 'totara/program:configuredetails'],
            $programsenabled,
            $categorycontext
        ));
    }
}

$ADMIN->add('programs', new admin_externalpage('programcustomfields',
    new lang_string('customfields', 'totara_customfield'),
    $CFG->wwwroot . '/totara/customfield/index.php?prefix=program',
    ['totara/core:programmanagecustomfield'],
    $programsenabled
));

unset($programsenabled);
