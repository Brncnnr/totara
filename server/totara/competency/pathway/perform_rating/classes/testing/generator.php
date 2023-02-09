<?php
/*
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

namespace pathway_perform_rating\testing;

use coding_exception;
use core_component;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\section_element;
use pathway_manual\manual;
use pathway_perform_rating\models\perform_rating as perform_rating_model;
use pathway_perform_rating\perform_rating;
use performelement_linked_review\linked_review;
use stdClass;
use totara_competency\testing\generator as competency_generator;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;

/**
 * Pathway generator.
 *
 * Usage:
 *    $generator = $this->getDataGenerator()->get_plugin_generator('pathway_perform_rating');
 */
final class generator extends \core\testing\component_generator {

    /**
     * Create a perform rating pathway
     *
     * @param competency|stdClass|int $competency Competency entity, record or ID.
     * @param int|null $sort_order Defaults to being sorted last.
     * @return perform_rating
     */
    public function create_perform_rating_pathway($competency, int $sort_order = null): perform_rating {
        $competency_generator = competency_generator::instance();
        /** @var perform_rating $instance */
        $instance = $competency_generator->create_pathway(perform_rating::class, $competency, $sort_order);

        return $instance->save();
    }

    /**
     * Create a perform rating
     *
     * Note: you need a participant instance and a linked review element for competencies to be able create the rating
     *
     * @param int|stdClass|competency|manual $competency Competency ID, record or entity, or alternatively a manual pathway.
     * @param participant_instance $rating_participant_instance
     * @param section_element $section_element the element this rating is given in, has to be a competency review type
     * @param scale_value|int|null $scale_value If not specified, defaults to the first scale value set for the competency
     *
     * @return perform_rating_model
     */
    public function create_perform_rating(
        $competency,
        participant_instance $rating_participant_instance,
        section_element $section_element,
        $scale_value = null
    ): perform_rating_model {
        if (!core_component::get_plugin_directory('performelement', 'linked_review')) {
            throw new coding_exception('Required linked review plugin is not present');
        }

        if ($competency instanceof manual) {
            $competency = $competency->get_competency();
        }

        if (!$competency instanceof competency) {
            $competency = new competency($competency);
        }

        $element = $section_element->get_element();
        if (!$element->get_element_plugin() instanceof linked_review) {
            throw new coding_exception('Expected a linked_review element');
        }

        $data = json_decode($element->get_data(), true);
        if (empty($data['content_type']) || $data['content_type'] !== 'totara_competency') {
            throw new coding_exception('Expected a linked_review element for competencies');
        }

        $rating = perform_rating_model::create(
            $competency->id,
            $scale_value ? $scale_value->id : null,
            $rating_participant_instance->id,
            $section_element->id
        );

        return $rating;
    }

}
