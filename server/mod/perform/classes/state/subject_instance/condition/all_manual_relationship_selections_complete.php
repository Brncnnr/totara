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
 * @package mod_perform
 */

namespace mod_perform\state\subject_instance\condition;

use mod_perform\entity\activity\manual_relationship_selection_progress;
use mod_perform\models\activity\subject_instance;
use mod_perform\state\condition;

/**
 * Requires participant user selections have been made for all relationships for a subject instance before it can be activated.
 *
 * @package mod_perform
 */
class all_manual_relationship_selections_complete extends condition {

    /**
     * @inheritDoc
     */
    public function pass(): bool {
        /** @var subject_instance $subject_instance */
        $subject_instance = $this->object;

        $has_open_participant_selections = manual_relationship_selection_progress::repository()
            ->where('subject_instance_id', $subject_instance->id)
            ->where('status', '<>', manual_relationship_selection_progress::STATUS_COMPLETE)
            ->exists();

        return !$has_open_participant_selections;
    }

}
