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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package performelement_redisplay
 */

namespace performelement_redisplay;

use coding_exception;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\entity\activity\section_element_reference as section_element_reference_entity;
use mod_perform\models\activity\element as element_model;
use mod_perform\models\activity\element_plugin;
use mod_perform\models\activity\helpers\element_usage as base_element_usage;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\section_element_reference;
use performelement_redisplay\data_provider\redisplay_data;

class redisplay extends element_plugin {

    /**
     * @string The serialized key for a reference element's source section element id.
     */
    public const SOURCE_SECTION_ELEMENT_ID = 'sourceSectionElementId';

    /**
     * @inheritDoc
     */
    public function get_sortorder(): int {
        return 110;
    }

    /**
     * @inheritDoc
     */
    public function get_group(): int {
        return self::GROUP_OTHER;
    }

    /**
     * @inheritDoc
     */
    public function has_title(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function get_title_text(): string {
        return get_string('instruction_text', 'performelement_redisplay');
    }

    /**
     * @inheritDoc
     */
    public function is_title_required(): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function get_participant_print_component(): string {
        return $this->get_participant_form_component();
    }

    /**
     * @inheritDoc
     */
    public function validate_element(element_entity $element): void {
        $data = json_decode($element->data, true, 512, JSON_THROW_ON_ERROR);

        $this->ensure_source_section_element_is_set($data);
    }

    private function ensure_source_section_element_is_set(array $data): void {
        $source_section_element_id = $data[self::SOURCE_SECTION_ELEMENT_ID] ?? null;
        $section_element = section_element::load_by_id($source_section_element_id);

        // Todo: fix in TL-30351, Hard-coded linked_review element.
        if ($section_element->element->plugin_name === 'linked_review') {
            throw new coding_exception('Linked review elements can not be redisplayed');
        }

        if ($source_section_element_id === null) {
            throw new coding_exception(self::SOURCE_SECTION_ELEMENT_ID .' must be specified in the element data field');
        }
    }

    /**
     * @inheritDoc
     */
    public function process_data(element_entity $element): ?string {
        $modified_data = (new redisplay_data())->include_extra_info($element->id);

        return json_encode($modified_data, JSON_THROW_ON_ERROR);
    }

    /**
     * @inheritDoc
     */
    public function post_create(element_model $element): void {
        $data = json_decode($element->get_raw_data(), true, 512, JSON_THROW_ON_ERROR);
        $source_section_element_id = $data[self::SOURCE_SECTION_ELEMENT_ID];

        section_element_reference::create($source_section_element_id, $element->id);

        // Strip this from the data, otherwise it can become incorrect if an element/activity is cloned.
        // We can safely do this because all data for this type of element is saved in the section_element_reference table.
        $element->clear_data();
    }

    /**
     * @inheritDoc
     */
    public function post_update(element_model $element): void {
        $data = json_decode($element->get_raw_data(), true, 512, JSON_THROW_ON_ERROR);
        $source_section_element_id = $data[self::SOURCE_SECTION_ELEMENT_ID];

        // It is possible that the section_element_reference record is gone due to deleted activity. In that case we have
        // to create a new record.
        if (!section_element_reference_entity::repository()
            ->where('referencing_element_id', $element->id)
            ->exists()
        ) {
            section_element_reference::create($source_section_element_id, $element->id);
        } else {
            section_element_reference::update($source_section_element_id, $element->id);
        }

        // Strip this from the data, otherwise it can become incorrect if an element/activity is cloned.
        // We can safely do this because all data for this type of element is saved in the section_element_reference table.
        $element->clear_data();
    }

    /**
     * @inheritDoc
     */
    public function get_element_usage(): base_element_usage {
        return new element_usage();
    }

}