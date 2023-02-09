<?php
/**
 * This file is part of Totara Learn
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package pathway_perform_rating
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2022110800;       // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2022110800;       // Requires this Totara version.
$plugin->component = 'pathway_perform_rating'; // To check on upgrade, that module sits in correct place
$plugin->dependencies = array(
    'mod_perform' => 2021031500,
    'performelement_linked_review' => 2021031502,
    'totara_hierarchy' => 2020101200,
);
