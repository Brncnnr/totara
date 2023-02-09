<?php
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
 * @subpackage plan
 */

use totara_evidence\models\evidence_type;

/**
 * Determine whether a evidence type is in use or not.
 *
 * "in use" means that items are assigned any of the evidence type's values.
 *
 * @deprecated since Totara 13
 *
 * @param int $evidencetypeid The evidence type to check
 * @return boolean
 */
function dp_evidence_type_is_used($evidencetypeid) {
    debugging('dp_evidence_type_is_used() has been deprecated and is no longer used, please use totara_evidence\models\evidence_type::in_use() instead.', DEBUG_DEVELOPER);
    return evidence_type::load_by_id($evidencetypeid)->in_use();
}
