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
 * @package totara_competency
 */

namespace pathway_perform_rating\event;

use core\event\base;
use mod_perform\entity\activity\activity;
use pathway_perform_rating\models\perform_rating;
use totara_core\entity\relationship;
use totara_core\relationship\relationship as relationship_model;
use totara_hierarchy\entity\competency;

class perform_rating_created extends base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'pathway_perform_rating';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Create instance of event.
     *
     * @param perform_rating $rating
     * @return perform_rating_created|base
     */
    public static function create_from_perform_rating(perform_rating $rating) {
        $data = [
            'userid' => $rating->rater_user_id,
            'objectid' => $rating->id,
            'context' => $rating->activity->get_context(),
            'relateduserid' => $rating->user_id,
            'other' => [
                'competency_id' => $rating->competency_id,
                'scale_value_id' => $rating->scale_value_id,
                'rater_id' => $rating->rater_user_id,
                'activity_id' => $rating->activity_id,
                'participant_instance_id' => $rating->participant_instance->id,
                'relationship_id' => $rating->rater_relationship_id,
            ]
        ];

        return static::create($data);
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_rating_created', 'pathway_perform_rating');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        /** @var competency $competency */
        $competency = competency::repository()->find($this->other['competency_id']);
        $competency = $competency ? format_string($competency->display_name) : 'n/a';

        /** @var activity $activity */
        $activity = activity::repository()->find($this->other['activity_id']);
        $activity = $activity ? format_string($activity->name) : 'n/a';

        /** @var relationship $relationship */
        $relationship = relationship::repository()->find($this->other['relationship_id']);
        $relationship = $relationship ? relationship_model::load_by_entity($relationship)->name : 'n/a';

        return sprintf(
            "Received rating from '%s' in activity '%s' for competency '%s'",
            $relationship,
            $activity,
            $competency
        );
    }

}