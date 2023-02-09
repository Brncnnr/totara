<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/adminlib.php');

/**
 * Totara default home page type admin setting
 *
 * @since 13.0
 */
class totara_core_admin_setting_defaulthomepage extends admin_setting_configselect {

    /**
     * Returns XHTML select field and wrapping div(s)
     *
     * Parent method returns $defaultinfo as null if totara dashboard is disabled, rest is the same.
     *
     * @param string $data the option to show as selected
     * @param string $query
     * @return string XHTML field and wrapping div
     */
    public function output_html($data, $query = '') {
        global $OUTPUT;

        $default = $this->get_defaultsetting();
        $current = $this->get_setting();

        if (!$this->load_choices() || empty($this->choices)) {
            return '';
        }

        $context = (object) [
            'id' => $this->get_id(),
            'name' => $this->get_full_name(),
        ];

        // Make sure $defaultinfo is totara dashboard.
        $defaultinfo = get_string('totaradashboard', 'admin');

        // Warnings.
        $warning = '';
        if ($current === null) {
            // First run.
        } else if (empty($current) && (array_key_exists('', $this->choices) || array_key_exists(0, $this->choices))) {
            // No warning.
        } else if (!array_key_exists($current, $this->choices)) {
            $warning = get_string('warningcurrentsetting', 'admin', $current);
            if (!is_null($default) && $data == $current) {
                $data = $default; // Use default instead of first value when showing the form.
            }
        }

        $options = [];
        $template = 'core_admin/setting_configselect';

        foreach ($this->choices as $value => $name) {
            $options[] = [
                'value' => $value,
                'name' => $name,
                'selected' => (string) $value == $data
            ];
        }
        $context->options = $options;

        $element = $OUTPUT->render_from_template($template, $context);

        return format_admin_setting($this, $this->visiblename, $element, $this->description, true, $warning, $defaultinfo, $query);
    }

    /**
     * Returns the default home page config
     *
     * Parent method return $CFG->defaulthomepage as 0 once totara dashboard is disabled.
     *
     * @param string $name
     * @return string
     */
    public function config_read($name) {
        global $CFG;

        if (!empty($this->plugin)) {
            $value = get_config('core', $name);
            return $value === false ? 0 : $value;
        } else {
            if (isset($CFG->$name)) {
                return $CFG->$name;
            } else {
                return NULL;
            }
        }
    }
}