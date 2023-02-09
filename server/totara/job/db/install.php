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
 * @package totara_job
 */


function xmldb_totara_job_install() {
    global $CFG;
    require_once($CFG->dirroot . '/totara/core/db/upgradelib.php');

    totara_core_upgrade_create_relationship('totara_job\relationship\resolvers\manager', 'manager', 2);
    totara_core_upgrade_create_relationship('totara_job\relationship\resolvers\managers_manager', 'managers_manager', 3);
    totara_core_upgrade_create_relationship('totara_job\relationship\resolvers\appraiser', 'appraiser', 4);
    totara_core_upgrade_create_relationship('totara_job\relationship\resolvers\direct_report', 'direct_report', 8);

    return true;
}
