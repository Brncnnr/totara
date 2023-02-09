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
 * @package totara_certification
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();
/** @var admin_root $ADMIN */
/** @var context_system $systemcontext */

$certificationsenabled = advanced_feature::is_disabled('certifications');

if (has_any_capability(['totara/certification:createcertification', 'totara/certification:configurecertification'], $systemcontext)) {
    $ADMIN->add('certifications', new admin_externalpage('managecertifications',
        new lang_string('managecertifications', 'totara_core'),
        $CFG->wwwroot . '/totara/program/manage.php?viewtype=certification',
        ['totara/certification:createcertification', 'totara/certification:configurecertification'],
        $certificationsenabled
    ));
} else if (!empty($USER->tenantid)) {
    $tenant = core\record\tenant::fetch($USER->tenantid);
    $categorycontext = context_coursecat::instance($tenant->categoryid);
    if (has_any_capability(['totara/certification:createcertification', 'totara/certification:configurecertification'], $categorycontext)) {
        $ADMIN->add('certifications', new admin_externalpage('managecertifications',
            new lang_string('managecertifications', 'totara_core'),
            $CFG->wwwroot . '/totara/program/manage.php?viewtype=certification&categoryid=' . $tenant->categoryid,
            ['totara/certification:createcertification', 'totara/certification:configurecertification'],
            $certificationsenabled,
            $categorycontext
        ));
    }
}

// TODO create link to custom fields.
