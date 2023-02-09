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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package container_perform
 */

namespace container_perform;

use backup_enrolments_execution_step;
use core\entity\user;
use core_container\container;
use course_enrolment_manager;
use enrol_plugin;
use mod_perform\entity\activity\participant_instance;
use progress_trace;
use restore_enrolments_structure_step;
use stdClass;
use totara_core\hook\enrol_plugins;

/**
 * This is a simple workaround to the fact that we don't officially enrol users into performance activities when
 * creating participant instances.
 *
 * This enrollment plugin instead checks if the user has a participant instance for the container,
 * and if they do then they are designated as enrolled without creating any additional records.
 *
 * If this gets refactored, please consider:
 * - This plugin is deliberately not located in the enrol/ folder to hide it from the UI.
 * - To hide it from the 'Manage enrol plugins' page, it also had to be hard coded in the class
 *   admin_setting_manageenrols in lib/adminlib.php.
 *
 * @package container_perform
 */
class perform_enrollment extends enrol_plugin {

    /**
     * @inheritDoc
     */
    public function get_name() {
        return perform::get_type();
    }

    /**
     * @inheritDoc
     */
    public function get_instance_name($instance) {
        return format_string(self::get_container($instance)->fullname);
    }

    /**
     * Checks if the current user is participating in the performance activity associated with the specified container instance.
     * Note that this does not change the participation status of the current user, instead we just check the existing state.
     *
     * @param stdClass $instance Course enrollment plugin instance
     * @param bool $preventredirect Unused
     * @return bool|int Returns 0 if they are a participant (which is then converted into a timestamp), or false if they are not.
     */
    public function try_autoenrol(stdClass $instance, bool $preventredirect = true) {
        if (self::is_enrolled($instance->courseid, user::logged_in()->id)) {
            return 0;
        }

        return false;
    }

    /**
     * Checks if the specified user is participating in the performance activity associated with the specified container instance.
     *
     * @param int $container_id
     * @param int $user_id
     * @return bool
     */
    private static function is_enrolled(int $container_id, int $user_id): bool {
        return participant_instance::repository()
            ->filter_by_participant_user($user_id)
            ->filter_by_course($container_id)
            ->exists();
    }

    /**
     * @param perform|container $container Perform container instance
     */
    public static function create_container_instance(perform $container): void {
        global $DB;
        $DB->transaction(function () use ($container) {
            // We don't want any instances other than this one.
            static::delete_container_instance($container);

            (new static())->add_instance($container->to_record());
        });
    }

    /**
     * @param perform|container $container Perform container instance
     */
    public static function delete_container_instance(perform $container): void {
        global $DB;
        $DB->delete_records('enrol', ['courseid' => $container->id]);
    }

    /**
     * @param $instance
     * @return perform|container
     */
    private static function get_container($instance): perform {
        return perform::from_id($instance->courseid);
    }

    /**
     * Hook watcher that appends this enrollment plugin to the list of plugins when the course is a performance activity container.
     *
     * @param enrol_plugins $hook
     */
    public static function append_perform_enrollment_plugin(enrol_plugins $hook): void {
        if (!$hook->get_container()->containertype === perform::get_type()) {
            return;
        }

        $hook->add_enrol_plugin(new static());
    }


    /******************************************************************************************************************************
     *                         Ignore All Methods Beyond Here. They are just stubs that don't do anything.                        *
     ******************************************************************************************************************************/

    /**
     * @inheritDoc
     */
    protected function load_config() {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function get_config($name, $default = null) {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function set_config($name, $value) {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function enrol_user(stdClass $instance, $userid, $roleid = null, $timestart = 0, $timeend = 0, $status = null, $recovergrades = null) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function enrol_user_bulk(stdClass $instance, $userids, $roleid = null, $timestart = 0, $timeend = 0, $status = null) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function update_user_enrol(stdClass $instance, $userid, $status = null, $timestart = null, $timeend = null) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function unenrol_user(stdClass $instance, $userid) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function unenrol_user_bulk(stdClass $instance, $userids) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function can_add_instance($courseid) {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function can_edit_instance($instance) {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function can_hide_show_instance($instance) {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function get_unenrolself_link($instance) {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function course_updated($inserted, $course, $data) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function update_instance($instance, $data) {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function update_status($instance, $newstatus) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function get_action_icons(stdClass $instance) {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function is_cron_required() {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function user_delete($user) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function get_manual_enrol_button(course_enrolment_manager $manager) {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function has_bulk_operations(course_enrolment_manager $manager) {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function get_bulk_operations(course_enrolment_manager $manager) {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function process_expirations(progress_trace $trace, $courseid = null) {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function send_expiry_notifications($trace) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    protected function notify_expiry_enrolled($user, $ue, progress_trace $trace) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    protected function notify_expiry_enroller($eid, $users, progress_trace $trace) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function backup_annotate_custom_fields(backup_enrolments_execution_step $step, stdClass $enrol) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function restore_sync_course($course) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function restore_role_assignment($instance, $roleid, $userid, $contextid) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function restore_group_member($instance, $groupid, $userid) {
        // Do nothing.
    }

    /**
     * @inheritDoc
     */
    public function get_instance_defaults() {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function validate_param_types($data, $rules) {
        return [];
    }

}
