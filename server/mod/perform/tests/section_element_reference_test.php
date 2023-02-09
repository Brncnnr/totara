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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\entity\activity\section_element_reference as section_element_reference_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\section_element_reference;
use mod_perform\testing\generator as perform_generator;
use performelement_aggregation\aggregation;
use performelement_aggregation\calculations\average;
use performelement_numeric_rating_scale\numeric_rating_scale;
use performelement_redisplay\redisplay;

/**
 * @group perform
 * @group perform_element
 */
abstract class section_element_reference_testcase extends advanced_testcase {

    /**
     * @var $referencing_aggregation_activity activity
     */
    protected $source_activity, $referencing_redisplay_activity;

    /**
     * @var section
     */
    protected $source_section;

    /**
     * @var section
     */
    protected $referencing_aggregation_section, $referencing_redisplay_section;

    /**
     * @var $source_element element
     */
    protected $source_element;

    /**
     * @var element
     */
    protected $referencing_aggregation_element, $referencing_redisplay_element;

    /**
     * @var section_element
     */
    protected $source_section_element, $referencing_aggregation_section_element, $referencing_redisplay_section_element;

    /**
     * @var section_element_reference
     */
    protected $redisplay_section_element_reference, // Redisplay question that references the source element.
        $aggregation_section_element_reference;  // Aggregation question that references the source element.

    /**
     * @var activity
     */
    protected $self_reference_activity;

    /**
     * @var section
     */
    protected $self_reference_section;

    /**
     * @var element
     */
    protected $self_reference_referencing_element, $self_reference_source_element;

    /**
     * @var section_element
     */
    protected $self_reference_source_section_element, $self_reference_referencing_section_element;

    /**
     * @var section_element_reference
     */
    protected $self_reference_section_element_reference;

    protected function create_test_data(): void {
        self::setAdminUser();

        $perform_generator = perform_generator::instance();

        /*
         * source_activity                     [SOURCE ACTIVITY]
         * ** source_section                   [SOURCE SECTION]
         *    ** source_element (numeric_rating_scale)   [SOURCE SECTION ELEMENT]
         * ** referencing_aggregation_section
         *    ** referencing_aggregation_element (aggregation) --> source_element
         *
         * referencing_redisplay_activity
         * ** referencing_redisplay_section
         *    ** referencing_redisplay_element (redisplay) --> source_element
         */

        $this->source_activity = $perform_generator->create_activity_in_container(['activity_name' => 'source_activity']);
        $this->referencing_redisplay_activity = $perform_generator->create_activity_in_container(['activity_name' => 'referencing_redisplay_activity']);
        $this->source_section = $perform_generator->create_section($this->source_activity, ['title' => 'source_section']);

        $this->referencing_aggregation_section = $perform_generator->create_section(
            $this->source_activity, ['title' => 'referencing_aggregation_section']
        );

        $this->referencing_redisplay_section = $perform_generator->create_section(
            $this->referencing_redisplay_activity, ['title' => 'referencing_redisplay_section']
        );

        $this->source_element = $perform_generator->create_element(
            [
                'plugin_name' => numeric_rating_scale::get_plugin_name(),
            ]
        );

        $this->source_section_element = $perform_generator->create_section_element($this->source_section, $this->source_element);

        $this->referencing_aggregation_element = $perform_generator->create_element([
            'plugin_name' => 'aggregation',
            'data' => json_encode([
                aggregation::SOURCE_SECTION_ELEMENT_IDS => [$this->source_section_element->id],
                aggregation::EXCLUDED_VALUES => [],
                aggregation::CALCULATIONS => [average::get_name()],
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->referencing_redisplay_element = $perform_generator->create_element([
            'plugin_name' => 'redisplay',
            'data' => json_encode([redisplay::SOURCE_SECTION_ELEMENT_ID => $this->source_section_element->id], JSON_THROW_ON_ERROR),
        ]);

        $this->referencing_aggregation_section_element = $perform_generator->create_section_element(
            $this->referencing_aggregation_section, $this->referencing_aggregation_element
        );

        $this->referencing_redisplay_section_element = $perform_generator->create_section_element(
            $this->referencing_redisplay_section, $this->referencing_redisplay_element
        );

        $this->aggregation_section_element_reference = section_element_reference::load_by_entity(
            section_element_reference_entity::repository()
                ->where('source_section_element_id', $this->source_section_element->id)
                ->where('referencing_element_id', $this->referencing_aggregation_element->id)
                ->one(true)
        );

        $this->redisplay_section_element_reference = section_element_reference::load_by_entity(
            section_element_reference_entity::repository()
                ->where('source_section_element_id', $this->source_section_element->id)
                ->where('referencing_element_id', $this->referencing_redisplay_element->id)
                ->one(true)
        );
    }

    /**
     * Create a redisplay element that references an element in the same activity.
     */
    protected function create_test_data_referencing_same_section(): void {
        self::setAdminUser();

        $perform_generator = perform_generator::instance();

        /*
         * self_reference_activity                                        [SOURCE ACTIVITY]
         * ** self_reference_section                                      [SOURCE SECTION]
         *    ** self_reference_source_element (short-text)               [SOURCE SECTION ELEMENT]
         *    ** self_reference_referencing_element (redisplay) --> self_reference_source_element
         */
        $this->self_reference_activity = $perform_generator->create_activity_in_container(['activity_name' => 'activity1']);

        $this->self_reference_section = $perform_generator->create_section($this->self_reference_activity, ['title' => 'section1']);

        $this->self_reference_source_element = $perform_generator->create_element();

        $this->self_reference_source_section_element = $perform_generator->create_section_element(
            $this->self_reference_section,
            $this->self_reference_source_element
        );

        $this->self_reference_referencing_element = $perform_generator->create_element([
            'plugin_name' => 'redisplay',
            'data' => json_encode([
                redisplay::SOURCE_SECTION_ELEMENT_ID => $this->self_reference_source_section_element->id
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->self_reference_referencing_section_element = $perform_generator->create_section_element(
            $this->self_reference_section,
            $this->self_reference_referencing_element
        );

        $this->self_reference_section_element_reference = section_element_reference::load_by_entity(
            section_element_reference_entity::repository()
                ->where('source_section_element_id', $this->self_reference_source_section_element->id)
                ->where('referencing_element_id', $this->self_reference_referencing_element->id)
                ->one(true)
        );
    }

    protected function tearDown(): void {
        $this->source_activity = null;
        $this->referencing_redisplay_activity = null;
        $this->source_section = null;
        $this->referencing_aggregation_section = null;
        $this->referencing_redisplay_section = null;
        $this->source_element = null;
        $this->referencing_aggregation_element = null;
        $this->referencing_redisplay_element = null;
        $this->source_section_element = null;
        $this->referencing_aggregation_section_element = null;
        $this->referencing_redisplay_section_element = null;
        $this->redisplay_section_element_reference = null;
        $this->aggregation_section_element_reference = null;
        $this->self_reference_activity = null;
        $this->self_reference_section = null;
        $this->self_reference_referencing_element = null;
        $this->self_reference_source_element = null;
        $this->self_reference_source_section_element = null;
        $this->self_reference_referencing_section_element = null;
        $this->self_reference_section_element_reference = null;
    }
}