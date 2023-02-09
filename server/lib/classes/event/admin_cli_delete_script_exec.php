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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package core_event
 */

namespace core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Admin CLI record-deleting script execution event class.
 */
class admin_cli_delete_script_exec extends base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->context = \context_system::instance();
    }

    /**
     * Create a new event in a parameterised way.
     *
     * @param string $scriptname
     * @param array $options
     * @param string $intent
     * @return admin_cli_script_exec
     */
    public static function create_from_cli(string $scriptname, array $options, string $intent) {
        $options_string = self::options_string($options);
        $event = self::create(array(
            'other' => array(
                'scriptname' => $scriptname,
                'options' => $options_string,
                'outcome' => $intent,
            )
        ));
        return $event;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventadminclideletescriptexec', 'core_admin');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "CLI script {$this->other['scriptname']} was run with {$this->other['options']} to {$this->other['outcome']}";
    }

    /**
     * Convert CLI options array to string
     *
     * @param array $options
     * @return string
     */
    public static function options_string($options) {
        $option_string = "";
        foreach ($options as $key => $val) {
            if ($key == 'help') {
                continue;
            }
            if (empty($val)) {
                continue;
            }
            $option_string .= "--{$key}='{$val}' ";
        }
        return trim($option_string);
    }
}
