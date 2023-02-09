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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity\helpers;

use coding_exception;
use totara_tui\json_editor\formatter\formatter as json_editor_formatter;
use mod_perform\entity\activity\element as element_entity;

/**
 * Helper methods for element plugins that use weka editors/content.
 *
 * @package mod_perform\models\activity
 */
class element_weka_helper {

    /**
     * A helper function for converting a weka doc into html and adding it on to element data (for display).
     *
     * @see respondable_element_plugin::process_data
     * @param element_entity $element The element to add weka html to
     * @param string $in_doc_field The field the weka doc is in
     * @param string $out_html_field The field to put the output html in
     * @return string|null The encoded element data
     */
    public static function add_weka_html_to_data(element_entity $element, string $in_doc_field, string $out_html_field): ?string {
        if ($element->data === null) {
            return null;
        }

        $element_data = json_decode($element->data, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($element_data)) {
            return json_encode($element_data, JSON_THROW_ON_ERROR);
        }

        $element_data = static::add_weka_html($element_data, $in_doc_field, $out_html_field);

        return json_encode($element_data, JSON_THROW_ON_ERROR);
    }

    /**
     * Similar to add_weka_html_to_data but applied to a set of iterable options.
     *
     * @param element_entity $element
     * @param string $in_iterable_key The option to covert and add the weka html to each of its items
     * @param string $in_doc_field The field the weka doc is in
     * @param string $out_html_field The field to put the output html in
     * @return string|null The encoded element data
     *@see respondable_element_plugin::process_data
     */
    public static function add_weka_html_to_data_iterable(
        element_entity $element,
        string $in_iterable_key,
        string $in_doc_field,
        string $out_html_field
    ): ?string {
        if ($element->data === null) {
            return null;
        }

        $element_data = json_decode($element->data, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($element_data) || !is_array(($element_data[$in_iterable_key] ?? null))) {
            return json_encode($element_data, JSON_THROW_ON_ERROR);
        }

        foreach ($element_data[$in_iterable_key] as $index => $item) {
            $in_data = $element_data[$in_iterable_key][$index] ?? null;

            if ($in_data !== null) {
                $element_data[$in_iterable_key][$index] = static::add_weka_html($item, $in_doc_field, $out_html_field);
            }
        }

        return json_encode($element_data, JSON_THROW_ON_ERROR);
    }

    private static function add_weka_html(array $element_data, string $in_doc_field, string $out_html_field): array  {
        $weka_doc = $element_data[$in_doc_field] ?? null;

        if ($weka_doc) {
            $formatter = new json_editor_formatter();
            $element_data[$out_html_field] = $formatter->to_html($weka_doc);
        } else {
            $element_data[$out_html_field] = null;
        }

        return $element_data;
    }

}
