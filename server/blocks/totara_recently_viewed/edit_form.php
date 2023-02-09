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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package block_totara_recently_viewed
 */

use block_totara_recently_viewed\settings_helper as settings;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

/**
 * Class block_totara_recently_viewed_edit_form
 */
class block_totara_recently_viewed_edit_form extends block_edit_form {
    /**
     * Add the custom config options
     *
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
        if (advanced_feature::is_disabled('ml_recommender')) {
            $no_feature = get_string('no_feature', 'block_totara_recently_viewed');
            $mform->addElement('html', "<p>{$no_feature}</p>");
            $mform->freeze();
            return;
        }

        $mform->addElement('header', 'config_header', get_string('customblocksettings', 'block'));

        // Display type
        $options = [
            0 => get_string('tile', 'block_totara_recently_viewed'),
            1 => get_string('list', 'block_totara_recently_viewed'),
        ];
        $mform->addElement('select', 'config_display', get_string('config:display', 'block_totara_recently_viewed'), $options);
        $mform->setDefault('config_display', settings::DEFAULT_DISPLAY_TYPE);

        $mform->addElement('text', 'config_noi', get_string('config:number_of_items', 'block_totara_recently_viewed'), ['size' => '2']);
        $mform->setType('config_noi', PARAM_INT);
        $mform->setDefault('config_noi', settings::DEFAULT_NUMBER_OF_ITEMS);

        $no_yes_options = [
            0 => get_string('no'),
            1 => get_string('yes'),
        ];;
        $mform->addElement('select', 'config_ratings', get_string('config:show_ratings', 'block_totara_recently_viewed'), $no_yes_options);
        $mform->setDefault('config_ratings', settings::DEFAULT_SHOW_RATINGS);
    }

    /**
     * @return bool
     */
    protected function has_common_settings() {
        return advanced_feature::is_enabled('ml_recommender');
    }
}
