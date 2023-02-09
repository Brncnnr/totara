<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/** @var admin_root $ADMIN */
$ADMIN->add(
    'root',
    new admin_category(
        'totara_evidence',
        get_string('evidence', 'totara_evidence'),
        advanced_feature::is_disabled('evidence')
    ),
    'totara_plan'
);

// Manage types
$ADMIN->add(
    'totara_evidence',
    new admin_externalpage(
        'manage_evidence_types',
        get_string('manage_types', 'totara_evidence'),
        new moodle_url('/totara/evidence/type/index.php'),
        'totara/evidence:managetype',
        advanced_feature::is_disabled('evidence')
    )
);
