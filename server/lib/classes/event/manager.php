<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * New event manager class.
 *
 * @package    core
 * @since      Moodle 2.6
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class used for event dispatching.
 *
 * Note: Do NOT use directly in your code, it is intended to be used from
 *       base event class only.
 */
class manager {
    /** @var array buffer of event for dispatching */
    protected static $buffer = array();

    /** @var array buffer for events that were not sent to external observers when DB transaction in progress */
    protected static $extbuffer = array();

    /** @var array Totara buffers tracking of nested transaction events */
    protected static $trans_buffers = [];

    /** @var string Totara transaction name */
    protected static $trans_name;

    /** @var bool evert dispatching already in progress - prevents nesting */
    protected static $dispatching = false;

    /** @var null|array cache of all observers */
    protected static $allobservers = null;

    /**
     * Trigger new event.
     *
     * @internal to be used only from \core\event\base::trigger() method.
     * @param \core\event\base $event
     *
     * @throws \coding_Exception if used directly.
     */
    public static function dispatch(\core\event\base $event) {
        if (during_initial_install()) {
            return;
        }
        if (!$event->is_triggered() or $event->is_dispatched()) {
            throw new \coding_exception('Illegal event dispatching attempted.');
        }

        self::$buffer[] = $event;

        if (self::$dispatching) {
            return;
        }

        self::$dispatching = true;
        self::process_buffers();
        self::$dispatching = false;
    }

    /**
     * Notification from DML layer.
     * @internal to be used from DML layer only.
     * @param string $name
     */
    public static function database_transaction_began(string $name) {
        self::$trans_buffers[$name] = [];
        self::$trans_name = $name;
    }

    /**
     * Notification from DML layer.
     * @internal to be used from DML layer only.
     * @param string $name
     */
    public static function database_transaction_commited(string $name) {
        if ($name !== self::$trans_name or !isset(self::$trans_buffers[$name])) {
            debugging('Invalid transaction commit order detected', DEBUG_DEVELOPER);
            return;
        }

        $lastkey = array_key_last(self::$trans_buffers);
        if ($lastkey !== $name) {
            debugging('Invalid transaction commit order detected', DEBUG_DEVELOPER);
            return;
        }

        if (count(self::$trans_buffers) > 1) {
            $lastbuffer = self::$trans_buffers[$name];
            unset(self::$trans_buffers[$name]);
            $prevname = array_key_last(self::$trans_buffers);
            self::$trans_buffers[$prevname] = array_merge(self::$trans_buffers[$prevname], $lastbuffer);
            self::$trans_name = $prevname;
            return;
        }

        // Move all transaction events to the extbuffer to get it processed.
        $events = reset(self::$trans_buffers);
        self::$extbuffer = array_merge(self::$extbuffer, $events);
        self::$trans_buffers = [];
        self::$trans_name = null;

        if (self::$dispatching) {
            // The dispatching is already running,
            // it will process the new events in self::$extbuffer before it terminates.
            return;
        }

        self::$dispatching = true;
        self::process_buffers();
        self::$dispatching = false;
    }

    /**
     * Notification from DML layer.
     * @internal to be used from DML layer only.
     * @param string|null $name null means discard the whole buffer
     */
    public static function database_transaction_rolledback(?string $name) {
        if ($name === null) {
            self::$trans_buffers = [];
            self::$trans_name = null;
            return;
        }

        if (!isset(self::$trans_buffers[$name])) {
            debugging('Invalid transaction rollback order detected', DEBUG_DEVELOPER);
            return;
        }

        while (true) {
            $lastname = array_key_last(self::$trans_buffers);
            unset(self::$trans_buffers[$lastname]);
            if ($lastname === $name) {
                break;
            }
        }

        self::$trans_name = array_key_last(self::$trans_buffers);
    }

    protected static function process_buffers() {
        global $CFG;
        self::init_all_observers();

        while (true) { // Terminated by 'return;' if after no more events to process.

            $fromextbuffer = false;
            $addedtoextbuffer = false;

            if (self::$extbuffer) {
                $event = array_shift(self::$extbuffer);
                $fromextbuffer = true;
            } else if (self::$buffer) {
                $event = array_shift(self::$buffer);
            } else {
                return;
            }

            $observingclasses = self::get_observing_classes($event);
            foreach ($observingclasses as $observingclass) {
                if (!isset(self::$allobservers[$observingclass])) {
                    continue;
                }
                foreach (self::$allobservers[$observingclass] as $observer) {
                    if ($observer->internal) {
                        if ($fromextbuffer) {
                            // Do not send buffered external events to internal handlers,
                            // they processed them already.
                            continue;
                        }
                    } else {
                        if (!$fromextbuffer and self::$trans_name !== null) {
                            // Do not notify external observers while in DB transaction.
                            if (!$addedtoextbuffer) {
                                self::$trans_buffers[self::$trans_name][] = $event;
                                $addedtoextbuffer = true;
                            }
                            continue;
                        }
                    }

                    if (isset($observer->includefile) and file_exists($observer->includefile)) {
                        include_once($observer->includefile);
                    }
                    if (is_callable($observer->callable)) {
                        try {
                            call_user_func($observer->callable, $event);
                        } catch (\Exception $e) {
                            // Observers are notified before installation and upgrade, this may throw errors.
                            if (empty($CFG->upgraderunning)) {
                                // Ignore errors during upgrade, otherwise warn developers.
                                // Totara: get the human callable name
                                $callable = get_callable_name($observer->callable);
                                debugging("Exception encountered in event observer '{$callable}': ".$e->getMessage(), DEBUG_DEVELOPER, $e->getTrace());
                            }
                        }
                    } else {
                        // Totara: get the human callable name.
                        $callable = get_callable_name($observer->callable);
                        debugging("Can not execute event observer '{$callable}'");
                    }
                }
            }

            // TODO: Invent some infinite loop protection in case events cross-trigger one another.
        }
    }

    /**
     * Returns list of classes related to this event.
     * @param \core\event\base $event
     * @return array
     */
    protected static function get_observing_classes(\core\event\base $event) {
        $classname = get_class($event);
        $observers = array('\\'.$classname);
        while ($classname = get_parent_class($classname)) {
            $observers[] = '\\'.$classname;
        }
        $observers = array_reverse($observers, false);

        return $observers;
    }

    /**
     * Initialise the list of observers.
     */
    protected static function init_all_observers() {
        global $CFG;

        if (is_array(self::$allobservers)) {
            return;
        }

        if (!during_initial_install()) {
            $cache = \cache::make('core', 'observers');
            $cached = $cache->get('all');
            $dirroot = $cache->get('dirroot');
            if ($dirroot === $CFG->dirroot and is_array($cached)) {
                self::$allobservers = $cached;
                return;
            }
        }

        self::$allobservers = array();

        $plugintypes = \core_component::get_plugin_types();
        $plugintypes = array_merge(array('core' => 'not used'), $plugintypes);
        $systemdone = false;
        foreach ($plugintypes as $plugintype => $ignored) {
            if ($plugintype === 'core') {
                $plugins['core'] = "$CFG->dirroot/lib";
            } else {
                $plugins = \core_component::get_plugin_list($plugintype);
            }

            foreach ($plugins as $plugin => $fulldir) {
                if (!file_exists("$fulldir/db/events.php")) {
                    continue;
                }
                $observers = null;
                include("$fulldir/db/events.php");
                if (!is_array($observers)) {
                    continue;
                }
                self::add_observers($observers, "$fulldir/db/events.php", $plugintype, $plugin);
            }
        }

        self::order_all_observers();

        if (!during_initial_install()) {
            $cache->set('all', self::$allobservers);
            $cache->set('dirroot', $CFG->dirroot);
        }
    }

    /**
     * Add observers.
     * @param array $observers
     * @param string $file
     * @param string $plugintype Plugin type of the observer.
     * @param string $plugin Plugin of the observer.
     */
    protected static function add_observers(array $observers, $file, $plugintype = null, $plugin = null) {
        global $CFG;

        foreach ($observers as $observer) {
            if (empty($observer['eventname']) or !is_string($observer['eventname'])) {
                debugging("Invalid 'eventname' detected in $file observer definition", DEBUG_DEVELOPER);
                continue;
            }
            if ($observer['eventname'] === '*') {
                $observer['eventname'] = '\core\event\base';
            }
            if (strpos($observer['eventname'], '\\') !== 0) {
                $observer['eventname'] = '\\'.$observer['eventname'];
            }
            if (empty($observer['callback'])) {
                debugging("Invalid 'callback' detected in $file observer definition", DEBUG_DEVELOPER);
                continue;
            }
            $o = new \stdClass();
            $o->callable = $observer['callback'];
            if (!isset($observer['priority'])) {
                $o->priority = 0;
            } else {
                $o->priority = (int)$observer['priority'];
            }
            if (!isset($observer['internal'])) {
                $o->internal = true;
            } else {
                $o->internal = (bool)$observer['internal'];
            }
            if (empty($observer['includefile'])) {
                $o->includefile = null;
            } else {
                if ($CFG->admin !== 'admin' and strpos($observer['includefile'], '/admin/') === 0) {
                    $observer['includefile'] = preg_replace('|^/admin/|', '/'.$CFG->admin.'/', $observer['includefile']);
                }
                $observer['includefile'] = $CFG->dirroot . '/' . ltrim($observer['includefile'], '/');
                if (!file_exists($observer['includefile'])) {
                    debugging("Invalid 'includefile' detected in $file observer definition", DEBUG_DEVELOPER);
                    continue;
                }
                $o->includefile = $observer['includefile'];
            }
            $o->plugintype = $plugintype;
            $o->plugin = $plugin;
            self::$allobservers[$observer['eventname']][] = $o;
        }
    }

    /**
     * Reorder observers to allow quick lookup of observer for each event.
     */
    protected static function order_all_observers() {
        foreach (self::$allobservers as $classname => $observers) {
            \core_collator::asort_objects_by_property($observers, 'priority', \core_collator::SORT_NUMERIC);
            self::$allobservers[$classname] = array_reverse($observers);
        }
    }

    /**
     * Returns all observers in the system. This is only for use for reporting on the list of observers in the system.
     *
     * @access private
     * @return array An array of stdClass with all core observer details.
     */
    public static function get_all_observers() {
        self::init_all_observers();
        return self::$allobservers;
    }

    /**
     * Replace all standard observers.
     * @param array $observers
     * @return array
     *
     * @throws \coding_Exception if used outside of unit tests.
     */
    public static function phpunit_replace_observers(array $observers) {
        global $DB;

        if (!PHPUNIT_TEST) {
            throw new \coding_exception('Cannot override event observers outside of phpunit tests!');
        }
        if (self::$dispatching) {
            throw new \coding_exception('Cannot override event observers when events are being dispatched');
        }
        if ($DB->is_transaction_started()) {
            throw new \coding_exception('Cannot override event observers when transaction is started');
        }

        self::$buffer = array();
        self::$extbuffer = array();
        self::$allobservers = array();

        self::add_observers($observers, 'phpunit');
        self::order_all_observers();

        return self::$allobservers;
    }

    /**
     * Reset everything if necessary.
     * @private
     *
     * @throws \coding_Exception if used outside of unit tests.
     */
    public static function phpunit_reset() {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('Cannot reset event manager outside of phpunit tests!');
        }
        self::$buffer = array();
        self::$extbuffer = array();
        self::$dispatching = false;
        self::$trans_buffers = [];
        self::$trans_name = null;
        self::$allobservers = null;
    }
}
