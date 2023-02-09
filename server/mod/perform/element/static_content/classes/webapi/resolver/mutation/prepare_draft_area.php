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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package performelement_static_content
 */

namespace performelement_static_content\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use mod_perform\entity\activity\element;
use mod_perform\entity\activity\section_element;
use mod_perform\webapi\middleware\require_activity;
use mod_perform\webapi\middleware\require_manage_capability;

class prepare_draft_area extends mutation_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        // Originally this method matched the element_id and section_id in $args
        // to a section element record and used that to retrieve required static
        // element record. The logic behind this was to first verify the static
        // element really belonged to the correct activity before using it.
        //
        // However, this does not work when the static element is a child of a
        // linked review element; in that case, what the section element record
        // points to is the parent linked review element and the element_id in
        // $args is the _static element id_. Even if the parent linked review
        // element id is passed in $args, the code would not be able to find the
        // static element record from the section element record since a parent
        // can have many elements as children.
        //
        // This is why the code now has to do the opposite: the static element
        // record is retrieved first, then it is verified against the correct
        // activity by querying the section element repository with the correct
        // element id. NB: using the $static_element->section relationship to
        // verify the section element does not work because there is no section
        // associated with a child element!
        $static_element_id = $args['element_id'];
        $element = element::repository()
            ->where('id', $static_element_id)
            ->one(true);

        if (empty($element)) {
            throw new \coding_exception('Invalid static element');
        }

        $section_element_id = $element->parent
            ? $element->parent
            : $static_element_id;

        $section_element = section_element::repository()
            ->where('element_id', $section_element_id)
            ->where('section_id', $args['section_id'])
            ->exists();

        if (!$section_element) {
            throw new \coding_exception('Invalid element for section');
        }

        $data = $element->data;
        $data = json_decode($data, true);
        $draft_id = null;
        $data['wekaDoc'] = file_prepare_draft_area(
            $draft_id,
            $element->context_id,
            'performelement_static_content',
            'content',
            $static_element_id,
            null,
            $data['wekaDoc']
        );

        return $draft_id;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            new require_login(),
            require_activity::by_section_id('section_id', true),
            new require_manage_capability(),
        ];
    }
}