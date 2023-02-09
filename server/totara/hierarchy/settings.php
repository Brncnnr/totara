<?php // $Id$
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @package totara
 * @subpackage totara_hierarchy
 */

// This file defines settingpages and externalpages under the "hierarchies" category

/** @var $ADMIN admin_root */

    // Positions.
use totara_core\advanced_feature;

$ADMIN->add(
    'positions',
    new admin_externalpage(
        'positionmanage',
        get_string('positionmanage', 'totara_hierarchy'),
        "{$CFG->wwwroot}/totara/hierarchy/framework/index.php?prefix=position",
        ['totara/hierarchy:viewpositionframeworks'],
        advanced_feature::is_disabled('positions')
    )
);

$ADMIN->add(
    'positions',
    new admin_externalpage(
        'positiontypemanage',
        get_string('managepositiontypes', 'totara_hierarchy'),
        "{$CFG->wwwroot}/totara/hierarchy/type/index.php?prefix=position",
        [
            'totara/hierarchy:createpositiontype',
            'totara/hierarchy:updatepositiontype',
            'totara/hierarchy:deletepositiontype'
        ],
        advanced_feature::is_disabled('positions')
    )
);

// Organisations.
$ADMIN->add(
    'organisations',
    new admin_externalpage(
        'organisationmanage',
        get_string('organisationmanage', 'totara_hierarchy'),
        "{$CFG->wwwroot}/totara/hierarchy/framework/index.php?prefix=organisation",
        ['totara/hierarchy:vieworganisationframeworks'],
        advanced_feature::is_disabled('organisations')
    )
);

$ADMIN->add(
    'organisations',
    new admin_externalpage(
        'organisationtypemanage',
        get_string('manageorganisationtypes', 'totara_hierarchy'),
        "{$CFG->wwwroot}/totara/hierarchy/type/index.php?prefix=organisation",
        [
            'totara/hierarchy:createorganisationtype',
            'totara/hierarchy:updateorganisationtype',
            'totara/hierarchy:deleteorganisationtype'
        ],
        advanced_feature::is_disabled('organisations')
    )
);

// To make sure the competency pages come before the assignment pages in the menu
// check if the assignment page is already there as the settings could have been parsed
// in a different order.
// Unfortunately there does not seem to be a better way to sort it than this.
if ($ADMIN->locate('competency_assignment')) {
    $before = 'competency_assignment';
} else if ($ADMIN->locate('hierarchy_competency_settings')) {
    $before = 'hierarchy_competency_settings';
} else {
    $before = null;
}

// Competencies.
$ADMIN->add(
    'competencies',
    new admin_externalpage(
        'competencymanage',
        get_string('competencymanage', 'totara_hierarchy'),
        "{$CFG->wwwroot}/totara/hierarchy/framework/index.php?prefix=competency",
        [
            'totara/hierarchy:viewcompetencyscale',
            'totara/hierarchy:viewcompetencyframeworks'
        ],
        advanced_feature::is_disabled('competencies')
    ),
    $before
);

$ADMIN->add(
    'competencies',
    new admin_externalpage(
        'competencytypemanage',
        get_string('managecompetencytypes', 'totara_hierarchy'),
        "{$CFG->wwwroot}/totara/hierarchy/type/index.php?prefix=competency",
        [
            'totara/hierarchy:createcompetencytype',
            'totara/hierarchy:updatecompetencytype',
            'totara/hierarchy:deletecompetencytype'
        ],
        advanced_feature::is_disabled('competencies')
    ),
    $before
);

\hierarchy_competency\admin_settings::load_or_create_settings_page($ADMIN);

// Goals.
$ADMIN->add(
    'goals',
    new admin_externalpage(
        'goalmanage',
        get_string('goalmanage', 'totara_hierarchy'),
        "{$CFG->wwwroot}/totara/hierarchy/framework/index.php?prefix=goal",
        [
            'totara/hierarchy:creategoalframeworks',
            'totara/hierarchy:updategoalframeworks',
            'totara/hierarchy:deletegoalframeworks',
            'totara/hierarchy:creategoal',
            'totara/hierarchy:updategoal',
            'totara/hierarchy:deletegoal',
            'totara/hierarchy:creategoalscale',
            'totara/hierarchy:updategoalscale',
            'totara/hierarchy:deletegoalscale'
        ],
        advanced_feature::is_disabled('goals')
    )
);

$ADMIN->add(
    'goals',
    new admin_externalpage(
        'companygoaltypemanage',
        get_string('managecompanygoaltypes', 'totara_hierarchy'),
        "{$CFG->wwwroot}/totara/hierarchy/type/index.php?prefix=goal&class=company",
        [
            'totara/hierarchy:creategoaltype',
            'totara/hierarchy:updategoaltype',
            'totara/hierarchy:deletegoaltype'
        ],
        advanced_feature::is_disabled('goals')
    )
);

$ADMIN->add(
    'goals',
    new admin_externalpage(
        'personalgoaltypemanage',
        get_string('managepersonalgoaltypes', 'totara_hierarchy'),
        "{$CFG->wwwroot}/totara/hierarchy/type/index.php?prefix=goal&class=personal",
        [
            'totara/hierarchy:creategoaltype',
            'totara/hierarchy:updategoaltype',
            'totara/hierarchy:deletegoaltype'
        ],
        advanced_feature::is_disabled('goals')
    )
);

$ADMIN->add(
    'goals',
    new admin_externalpage(
        'goalreport',
        get_string('goalreports', 'totara_hierarchy'),
        "{$CFG->wwwroot}/totara/hierarchy/prefix/goal/reports.php",
        ['totara/hierarchy:viewgoalreport'],
        advanced_feature::is_disabled('goals')
    )
);
