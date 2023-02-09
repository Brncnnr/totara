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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_manual
 */

namespace pathway_manual\observers;

use hierarchy_competency\event\competency_deleted;
use pathway_manual\entity\rating;

class competency {

    /**
     * React on a competency being deleted
     *
     * @param competency_deleted $event
     */
    public static function deleted(competency_deleted $event) {
        $competency_id = $event->get_data()['objectid'];

        rating::repository()
            ->where('competency_id', $competency_id)
            ->delete();
    }

}
