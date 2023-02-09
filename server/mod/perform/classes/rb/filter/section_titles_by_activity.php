<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 * @subpackage totara_reportbuilder
 */

namespace mod_perform\rb\filter;

use mod_perform\models\activity\activity;

/**
 * Filter based on selecting multiple element types via a dialog
 */
class section_titles_by_activity extends perform_filter_type {

    /**
     * @inheritDoc
     */
    protected static function get_modal_title(): array {
        return ['choose_section_id_plural', 'mod_perform'];
    }

    /**
     * @inheritDoc
     */
    protected static function get_modal_css(): string {
        return 'rb-filter-choose-section-id';
    }

    /**
     * @inheritDoc
     */
    public function setupForm(&$mform) {
        parent::setupForm($mform);
        $activity_id = optional_param('activity_id', null, PARAM_INT);
        if ($activity_id) {
            $mform->addElement('hidden', 'activity_id', $activity_id);
            $mform->setType('activity_id', PARAM_INT);
        }
    }

    /**
     * Get an array of element type options to use for filtering.
     *
     * @return string[] of [plugin_name => Display Name]
     */
    public static function get_item_options(): array {
        $options = [];

        $activity_id = optional_param('activity_id', null, PARAM_INT);
        if (!$activity_id) {
            return $options;
        }

        $activity = activity::load_by_id($activity_id);
        $sections = $activity->get_sections();
        foreach ($sections as $section) {
            $options[$section->id] = $section->get_display_title();
        }
        asort($options);

        return $options;
    }
}