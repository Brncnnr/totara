<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_flavour
 */

namespace totara_flavour;

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * Totara feature overview renderable
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_flavour
 */
class overview implements \renderable {

    /**
     * The selected flavour component.
     * @var string flavour component name, null if none active
     */
    public $currentflavour;

    /**
     * The available flavours
     * @var definition[]
     */
    public $flavours;

    /**
     * An array of settings to display as part of the overview.
     * @var overview_setting[]
     */
    public $settings = array();

    /**
     * Constructs a new overview renderable.
     */
    public function __construct() {
        global $CFG;

        $this->currentflavour = helper::get_active_flavour_component();
        $flavours = helper::get_available_flavour_definitions();
        $flavour_shortnames = array_map(function ($component) {
            list($unused, $name) = explode('_', $component, 2);
            return $name;
        }, array_keys($flavours));

        // By default don't display non-active flavours. $CFG->showflavours can be used to display others if required.
        $showflavours = isset($CFG->showflavours) ? $CFG->showflavours : '';

        // Hide the flavours that are not supposed to be visible.
        if (empty($showflavours)) {
            $showflavours = array();
        } else {
            $showflavours = explode(',', $showflavours);
            foreach ($showflavours as $k => $v) {
                $showflavours[$k] = 'flavour_' . $v;
            }
        }

        // Add all configured flavours in the specified order.
        $this->flavours = array();
        foreach ($showflavours as $component) {
            if (isset($flavours[$component])) {
                $this->flavours[$component] = $flavours[$component];
            }
        }

        // Always add current flavour.
        if (!isset($this->flavours[$this->currentflavour]) and isset($flavours[$this->currentflavour])) {
            $this->flavours[$this->currentflavour] = $flavours[$this->currentflavour];
        }

        $this->initialise_settings_array();
    }

    /**
     * Initialises the settings array to contain information for the overview.
     */
    protected function initialise_settings_array() {
        // Settings to display in the overview report.
        $components = array(
            'moodle' => array(
                'enableorganisations'             => null,  // Learn Professional.
                'feature_audiencemanagement'      => '1',   // Fake setting to mimic audience management.
                'feature_facetoface'              => '1',   // Fake setting to mimic facetoface.
                'enablebadges'                    => null,  // Totara.
                'audiencevisibility'              => null,  // Totara.
                'enableuser_reports'              => null,  // Totara.
                'enableglobalrestrictions'        => null,  // Totara.
                'enablepositions'                 => null,  // Totara.
                'enablemyteam'                    => null,  // Totara.
                'enabletotaradashboard'           => null,  // Totara.
                'enablecompetencies'              => null,  // Totara.
                'enablecourserpl'                 => null,  // Learn.
                'enablelearningplans'             => null,  // Learn.
                'enableprograms'                  => null,  // Learn.
                'enablecertifications'            => null,  // Learn.
                'enablecompletionimport'          => null,  // Learn.
                'enablecompetency_assignment'     => null,  // Perform.
                'enableperformance_activities'    => null,  // Perform.
                'enablegoals'                     => null,  // Perform.
                'enableevidence'                  => null,  // Perform.
                'enableappraisals'                => null,  // Perform.
                'enablefeedback360'               => null,  // Perform.
                'enableengage_resources' => null, // Engage.
                'enablecontainer_workspace' => null, // Engage.
                'enabletotara_msteams' => null, // Engage.
                'enableml_recommender' => null, // Engage.
            )
        );

        // Create settings for each of these.
        foreach ($components as $component => $settings) {
            foreach ($settings as $setting => $default) {
                // Use keys similar to admin settings UI, we need to cover both component and setting name.
                $this->settings[$component . '|' . $setting] = new overview_setting($setting, $component, $default);
            }
        }

        // Collect up whether each flavour prohibits this setting.
        foreach ($this->flavours as $flavour) {
            $prohibitedsettings = $flavour->get_prohibited_settings();
            foreach ($this->settings as $setting) {
                $prohibited = !empty($prohibitedsettings[$setting->component][$setting->name]);
                $setting->register_flavour_setup($flavour->get_component(), $prohibited);
            }
        }
    }

    /**
     * Do we need to enforce any flavour?
     *
     * @return string component name of the flavour to enforce, null means no enforcing necessary, empty string means remove current
     */
    public function get_flavour_to_enforce() {
        $current = get_config('totara_flavour', 'currentflavour');

        if (!$this->currentflavour) {
            if ($current) {
                // Unset the current flavour.
                return '';
            }
            // Nothing to enforce.
            return null;
        }

        if ($current !== $this->currentflavour) {
            // Switch to different flavour.
            return $this->currentflavour;
        }

        // Make sure that no prohibited feature is enabled.
        if (!isset($this->flavours[$this->currentflavour])) {
            // This should not happen.
            return null;
        }

        foreach ($this->settings as $setting) {
            if ($setting->is_prohibited($this->currentflavour)) {
                continue;
            }
            if ($setting->is_set_in_configphp()) {
                // We cannot force this setting.
                continue;
            }
            if ($setting->is_on()) {
                // This setting needs to be disabled.
                return $this->currentflavour;
            }
        }

        // All is fine.
        return null;
    }
}
