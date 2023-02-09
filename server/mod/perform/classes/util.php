<?php
/*
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform;

use container_perform\perform as perform_container;
use context;
use context_coursecat;
use context_user;
use core\collection;
use core\orm\query\builder;
use core_text;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\activity_type;
use mod_perform\entity\activity\element;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\participant_section;
use mod_perform\entity\activity\section;
use mod_perform\entity\activity\section_element;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\participant_source;
use totara_core\access;
use totara_core\advanced_feature;
use totara_core\totara\menu\build;
use totara_tenant\util as tenant_util;

class util {

    /**
     * Ensure the required performance activity roles exist in the system:
     * - performanceactivitycreator - intended for users that can create performance activities, assigned to category context.
     * - performanceactivitymanager - intended for users to manage a specific performance activity, assigned to course context typically.
     */
    public static function create_performance_roles(): void {
        global $DB;

        // Ensure mod_perform enabled.
        if (!$DB->record_exists('modules', ['name' => 'perform', 'visible' => 1])) {
            return;
        }

        $systemcontext = \context_system::instance();

        $shortnames = ['performanceactivitycreator', 'performanceactivitymanager'];
        foreach ($shortnames as $shortname) {
            if ($DB->record_exists('role', ['shortname' => $shortname])) {
                continue;
            }
            $newroleid = create_role('', $shortname, '', $shortname);

            $role = $DB->get_record('role', ['id' => $newroleid], '*', MUST_EXIST);
            foreach (array('assign', 'override', 'switch') as $type) {
                $function = 'allow_' . $type;
                $allows = get_default_role_archetype_allows($type, $role->archetype);
                foreach ($allows as $allowid) {
                    $function($role->id, $allowid);
                }
                set_role_contextlevels($role->id, get_default_contextlevels($role->archetype));
            }
            $defaultcaps = get_default_capabilities($role->archetype);
            foreach ($defaultcaps as $cap => $permission) {
                assign_capability($cap, $permission, $role->id, $systemcontext->id);
            }

            // Add allow_* defaults related to the new role.
            foreach ($DB->get_records('role') as $role) {
                if ($role->id == $newroleid) {
                    continue;
                }
                foreach (array('assign', 'override', 'switch') as $type) {
                    $function = 'allow_'.$type;
                    $allows = get_default_role_archetype_allows($type, $role->archetype);
                    foreach ($allows as $allowid) {
                        if ($allowid == $newroleid) {
                            $function($role->id, $allowid);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get the default category for performance activities.
     * If multi tenancy is turned on and the current user is part of a tenant
     * it will get the category of the tenant.
     *
     * If the category does not exist yet it will automatically create it.
     *
     * @return int
     */
    public static function get_default_category_id(): int {
        return perform_container::get_default_category_id();
    }

    /**
     * Creates a set of activity types.
     */
    public static function create_activity_types(): void {
        $predefined_types = [
            'appraisal',
            'check-in',
            'feedback'
        ];

        foreach ($predefined_types as $type) {
            $entity = new activity_type();
            $entity->name = $type;
            $entity->is_system = true;

            $entity->save();
        }
    }

    /**
     * @return context
     */
    public static function get_default_context(): context {
        $category_id = self::get_default_category_id();
        return context_coursecat::instance($category_id);
    }

    /**
     * Convenience function for adding a prefix/suffix to string. If necessary,
     * the original string is truncated to ensure the new string fits within a
     * length limit.
     *
     * @param string $text text to which to add a prefix and suffix.
     * @param int $max_chars maximum no of _characters_ (not bytes) allowed for
     *        the resultant string.
     * @param string $prefix text that leads the resultant string.
     * @param string $suffix text at the end of the resultant string.
     *
     * @return string the new string.
     */
    public static function augment_text(
        string $text,
        int $max_chars,
        string $prefix='',
        string $suffix=''
    ): string {
        // Note the use of _character_ (instead of byte) aware methods to get a
        // resultant string.
        $prefix_size = core_text::strlen($prefix);
        $suffix_size = core_text::strlen($suffix);
        $new_text_size = $prefix_size + core_text::strlen($text) + $suffix_size;

        if ($new_text_size <= $max_chars) {
            return "$prefix$text$suffix";
        }

        $ellipsis = "...";
        $ellipsis_size = core_text::strlen($ellipsis);
        $truncated_size = $max_chars - $prefix_size - $ellipsis_size - $suffix_size;

        $truncated = core_text::substr($text, 0, $truncated_size);
        return "$prefix$truncated$ellipsis$suffix";
    }

    public static function can_potentially_manage_participants(int $user_id): bool {
        if (static::has_manage_all_participants_capability($user_id)) {
            return true;
        }

        return has_role_with_capability(
            'mod/perform:manage_subject_user_participation',
            [CONTEXT_USER, CONTEXT_SYSTEM, CONTEXT_TENANT],
            $user_id
        );
    }

    public static function get_participant_manageable_activities(int $user_id) {
        if (static::has_manage_all_participants_capability($user_id)) {
            return activity_entity::repository()
                ->filter_by_visible()
                ->order_by('id')
                ->get()
                ->map_to(activity::class);
        }

        // Early exit if they can not even potentially manage any participants
        if (!has_role_with_capability(
            'mod/perform:manage_subject_user_participation',
            [CONTEXT_USER, CONTEXT_SYSTEM, CONTEXT_TENANT],
            $user_id
        )) {
            return new collection();
        }

        [$cap_sql, $params] = access::get_has_capability_sql('mod/perform:manage_subject_user_participation', 'c.id', $user_id);

        $sql = "
            p.id IN (
                SELECT a.id 
                FROM {perform} a
                JOIN {perform_track} t ON a.id = t.activity_id
                JOIN {perform_track_user_assignment} tua ON t.id = tua.track_id
                JOIN {perform_subject_instance} si ON tua.id = si.track_user_assignment_id
                JOIN {context} c ON si.subject_user_id = c.instanceid AND c.contextlevel = ".CONTEXT_USER."
                WHERE {$cap_sql}
            )
        ";

        return activity_entity::repository()
            ->as('p')
            ->filter_by_visible()
            ->where_raw($sql, $params)
            ->order_by('id')
            ->get()
            ->map_to(activity::class);
    }

    public static function has_manage_all_participants_capability(int $user_id): bool {
        $user_context = context_user::instance($user_id);

        return has_capability('mod/perform:manage_all_participation', $user_context, $user_id);
    }

    /**
     * Returns an array of up to 1000 userids of users who the $for_user id holds
     * the $capability in the user's context. Useful for checking which users a
     * user is permitted to do some action on.
     *
     * @deprecated since Totara 16
     * @param int $for_user ID of user to check for.
     * @param string $capability Capability string to test.
     * @param int $offset Offset to apply before returning records, null for no offset.
     * @param int $limit Maximum number of userids to return, null for no limit.
     * @return int[] Array of userids
     */
    public static function get_permitted_users(int $for_user, string $capability, int $offset = 0, int $limit = 1000): array {
        [$has_cap_sql, $has_cap_params] = access::get_has_capability_sql($capability, 'c.id', $for_user);

        $sql = sprintf("SELECT u.id AS user_key, u.id FROM {user} u
            JOIN {context} c ON c.contextlevel = %s AND c.instanceid = u.id
            WHERE u.deleted = 0 AND (%s)
            ORDER BY u.id
        ", CONTEXT_USER, $has_cap_sql);

        return builder::get_db()->get_records_sql_menu($sql, $has_cap_params, $offset, $limit);
    }

    /**
     * Require a manage participation check to pass.
     *
     * @param int $manager_id
     * @param int $subject_user_id
     * @return bool
     */
    public static function require_can_manage_participation(int $manager_id, int $subject_user_id): bool {
        if (self::can_manage_participation($manager_id, $subject_user_id)) {
            return true;
        }

        throw new \coding_exception('You do not have permission to manage participation');
    }

    /**
     * Determine if the given user can manage participation of the given subject
     *
     * @param int $manager_id A user id
     * @param int $subject_user_id A user id
     * @return bool
     */
    public static function can_manage_participation(int $manager_id, int $subject_user_id): bool {
        if (static::has_manage_all_participants_capability($manager_id)) {
            // Take into consideration that subject user may be deleted.
            $subject_user_context = context_user::instance($subject_user_id, IGNORE_MISSING);
            if ($subject_user_context === false) {
                return false;
            }
            return !$subject_user_context->is_user_access_prevented($manager_id);
        }

        // Take into consideration that subject user may be deleted.
        $subject_user_context = context_user::instance($subject_user_id, IGNORE_MISSING);
        if ($subject_user_context === false) {
            return false;
        }
        return access::has_capability(
            'mod/perform:manage_subject_user_participation',
            $subject_user_context,
            $manager_id
        );
    }

    /**
     * Has the user the capability to report on all subjects?
     *
     * @param int $user_id
     * @return bool
     */
    public static function has_report_on_all_subjects_capability(int $user_id): bool {
        $user_context = context_user::instance($user_id);

        return has_capability('mod/perform:report_on_all_subjects_responses', $user_context, $user_id);
    }

    /**
     * Has the user the capability to report on subject responses in any context?
     *
     * @param int $user_id
     * @return bool
     */
    public static function can_potentially_report_on_subjects(int $user_id): bool {
        if (static::has_report_on_all_subjects_capability($user_id)) {
            return true;
        }

        return has_role_with_capability(
            'mod/perform:report_on_subject_responses',
            [CONTEXT_USER, CONTEXT_SYSTEM, CONTEXT_TENANT],
            $user_id
        );
    }

    /**
     * Can a user (user_id) can see performance data for at least one subject in the activity
     * an element (element_id) is part of.
     *
     * @param int $user_id
     * @param int $element_id
     * @return bool
     */
    public static function can_report_on_element(int $user_id, int $element_id): bool {
        if (static::has_report_on_all_subjects_capability($user_id)) {
            return true;
        }

        // Early exit if they can not even potentially manage any participants
        if (!has_role_with_capability(
            'mod/perform:report_on_subject_responses',
            [CONTEXT_USER, CONTEXT_SYSTEM, CONTEXT_TENANT],
            $user_id
        )) {
            return false;
        }

        [$sql, $params] = access::get_has_capability_sql('mod/perform:report_on_subject_responses', 'c.id', $user_id);

        return builder::table(section_element::TABLE, 'se')
            ->select_raw('se.id')
            ->join([section::TABLE, 's'], 's.id', 'se.section_id')
            ->join([participant_section::TABLE, 'ps'], 'ps.section_id', 's.id')
            ->join([participant_instance::TABLE, 'pi'], 'pi.id', 'ps.participant_instance_id')
            ->join([subject_instance_entity::TABLE, 'si'], 'si.id', 'pi.subject_instance_id')
            ->join(['context', 'c'], function ($join) {
                $join->where_field('c.instanceid', 'si.subject_user_id')
                    ->where('c.contextlevel', CONTEXT_USER);
            })
            ->where('se.element_id', $element_id)
            ->where_raw($sql, $params)
            ->exists();
    }

    /**
     * @param int $subject_user_id
     * @param int $viewing_user_id
     * @return bool
     */
    public static function can_report_on_user(int $subject_user_id, int $viewing_user_id): bool {
        if (empty($subject_user_id) || empty($viewing_user_id)) {
            return false;
        }

        // Take into consideration that subject user may be deleted.
        $subject_user_context = context_user::instance($subject_user_id, IGNORE_MISSING);
        if ($subject_user_context === false) {
            return false;
        }

        if (has_capability('mod/perform:report_on_subject_responses', $subject_user_context, $viewing_user_id)) {
            return true;
        }

        $viewing_user_context = context_user::instance($viewing_user_id);

        if (static::has_report_on_all_subjects_capability($viewing_user_id)) {
            if (!empty($CFG->tenantsenabled)) {
                if ($viewing_user_context->tenantid) {
                    // The current user and the subject users have to share a tenant
                    return tenant_util::do_contexts_share_same_tenant($viewing_user_context, $subject_user_context);
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Returns true if historic activities are enabled
     *
     * @return bool
     */
    public static function is_historic_activities_enabled(): bool {
        if (get_config(null, 'showhistoricactivities') &&
            (advanced_feature::is_enabled('appraisals') || advanced_feature::is_enabled('feedback360'))
        ) {
            return true;
        }
        return false;
    }
}