<?php
/**
 * This file is part of Totara Core
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/adminlib.php');

/**
 * Totara core feature enable/disable checkbox setting.
 */
class totara_core_admin_setting_feature_checkbox extends admin_setting_configcheckbox {
    /** @var  array list of udpate callbacks */
    protected $updatecallbacks;
    /**
     * Constructor.
     *
     * @param string $name unique ascii name, usually 'enablexxxx'
     * @param string $visiblename localised
     * @param string $description long localised info
     * @param int $defaultsetting
     * @param array $updatecallbacks list of update callbacks, null defaults to array('totara_menu_reset_all_caches')
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, array $updatecallbacks = null) {
        if (is_array($defaultsetting)) {
            debugging('Invalid default setting ' . $name, DEBUG_DEVELOPER);
        }
        parent::__construct($name, $visiblename, $description, $defaultsetting, advanced_feature::ENABLED, advanced_feature::DISABLED);
        if ($updatecallbacks === null) {
            // In majority of cases the Totara menu  and Reportbuilder ignored sources needs to be reset.
            $updatecallbacks = array('totara_menu_reset_all_caches', 'totara_rb_purge_ignored_reports');
        }
        $this->updatecallbacks = $updatecallbacks;

        $this->set_updatedcallback(array($this, 'execute_update_callbacks'));

        if (debugging('', DEBUG_DEVELOPER)) {
            // Make sure developers did not forget to modify the list of core features.
            if (strpos($name, 'enable') !== 0) {
                debugging('Feature setting names must start with "enable"', DEBUG_DEVELOPER);
            } else {
                $shortname = preg_replace('/^enable/', '', $name);
                if (!in_array($shortname, advanced_feature::get_available())) {
                    debugging('Feature setting name must be included in \totara_core\advanced_feature::get_available() ' . $name, DEBUG_DEVELOPER);
                }
            }
        }
    }

    /**
     * Called when this setting changes.
     * @param string $fullname
     */
    public function execute_update_callbacks($fullname) {
        foreach ($this->updatecallbacks as $callback) {
            if (is_callable($callback)) {
                call_user_func($callback);
            }
        }
    }
}
