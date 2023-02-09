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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace;

use null_progress_trace;
use progress_trace;
use totara_contentmarketplace\sync\external_sync;
use totara_contentmarketplace\sync\sync_action;
use totara_core\http\client;
use core_component;
use core_plugin_manager;
use totara_contentmarketplace\plugininfo\contentmarketplace;
use coding_exception;

class sync {
    /**
     * @var array
     */
    private $sync_action_classes;

    /**
     * @var client
     */
    private $client;

    /**
     * @var progress_trace
     */
    private $trace;

    /**
     * @var bool
     */
    private $performance_debug;

    /**
     * sync constructor.
     *
     * @param client              $client
     * @param progress_trace|null $trace
     */
    public function __construct(client $client, ?progress_trace $trace = null) {
        global $CFG;
        if (null === $trace) {
            $trace = new null_progress_trace();
        }

        $this->sync_action_classes = [];
        $this->client = $client;
        $this->trace = $trace;

        // This is what output renderer was using.
        $this->performance_debug = (
            (defined('MDL_PERF') && MDL_PERF === true) || (!empty($CFG->perfdebug) && $CFG->perfdebug > 7)
        );
    }

    /**
     * @param bool $value
     * @return void
     */
    public function set_performance_debug(bool $value): void {
        $this->performance_debug = $value;
    }

    /**
     * @param bool $initial_run
     * @return void
     */
    public function execute(bool $initial_run): void {
        $actions = $this->get_sync_actions($initial_run);
        foreach ($actions as $action) {
            if ($action->is_skipped()) {
                $this->trace->output(sprintf('Skipping sync action for %s', get_class($action)));
                continue;
            }

            $action->set_performance_debug($this->performance_debug);
            $action->set_trace($this->trace);
            $action->invoke();
        }

        $this->trace->finished();
    }

    /**
     * Allows developer to set the sync action classes which should be
     * a child of {@see sync_action}. This function will allow developer
     * to run a custom set of sync action.
     *
     * Note that this function will override whatever the logics to load the sync action
     * classes within this class.
     *
     * @param array $sync_classes
     * @return void
     */
    public function set_sync_action_classes(array $sync_classes): void {
        // Reset the current sync classes.
        $this->sync_action_classes = [];

        foreach ($sync_classes as $sync_class) {
            if (!is_subclass_of($sync_class, sync_action::class)) {
                throw new coding_exception("Invalid sync class {$sync_class}");
            }

            $this->sync_action_classes[] = $sync_class;
        }
    }

    /**
     * @param bool $initial_run
     * @return sync_action[]
     */
    private function get_sync_actions(bool $initial_run): array {
        $this->load_sync_action_classes();
        $actions = [];

        foreach ($this->sync_action_classes as $action_class) {
            /** @var sync_action $action */
            $action = new $action_class();

            // Note: we set the flag initial run thru the setter, because the constructor
            // function can be extended and modified differently from the child class.
            // Which it can yield fatal error on the construction due to different type.
            $action->set_is_initial_run($initial_run);

            if ($action instanceof external_sync) {
                $action->set_api_client($this->client);
            }

            $actions[] = $action;
        }

        return $actions;
    }


    /**
     * @return void
     */
    private function load_sync_action_classes(): void {
        if (!empty($this->sync_action_classes)) {
            return;
        }

        /** @var contentmarketplace[] $plugins */
        $plugins = core_plugin_manager::instance()->get_plugins_of_type('contentmarketplace');
        foreach ($plugins as $plugin) {
            if ($plugin->is_enabled()) {
                $this->sync_action_classes = core_component::get_namespace_classes(
                    'sync_action',
                    sync_action::class,
                    $plugin->component
                );
            }
        }
    }

}