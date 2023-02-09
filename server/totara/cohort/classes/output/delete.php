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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort\output;

use totara_core\advanced_feature;

final class delete extends \core\output\template {

    public static function create_from_audience($audiencedata) {

        $unenrol = $audiencedata->unenrol;

        $items = [];
        $enrolledcourses = new \stdClass();
        $enrolledcourses->area = get_string('courses');
        if ($unenrol) {
            $enrolledcourses->changes = get_string('deletechangesunenroldelete', 'totara_cohort');
        } else {
            $enrolledcourses->changes = get_string('deletechangessuspend', 'totara_cohort');
        }
        $enrolledcourses->scopecount = $audiencedata->enrolled_course_count;
        $items[] = $enrolledcourses;

        if (!advanced_feature::is_disabled('programs')) {
            $enrolledprogs = new \stdClass();
            $enrolledprogs->area = get_string('programs', 'totara_program');
            if ($unenrol) {
                $enrolledprogs->changes = get_string('deletechangesunassigndelete', 'totara_cohort');
            } else {
                $enrolledprogs->changes = get_string('deletechangesunassignsuspend', 'totara_cohort');
            }
            $enrolledprogs->scopecount = $audiencedata->enrolled_program_count;
            $items[] = $enrolledprogs;
        }

        if (!advanced_feature::is_disabled('certifications')) {
            $enrolledcerts = new \stdClass();
            $enrolledcerts->area = get_string('certifications', 'totara_program');
            if ($unenrol) {
                $enrolledcerts->changes = get_string('deletechangesunassigndelete', 'totara_cohort');
            } else {
                $enrolledcerts->changes = get_string('deletechangesunassignsuspend', 'totara_cohort');
            }
            $enrolledcerts->scopecount = $audiencedata->enrolled_certification_count;
            $items[] = $enrolledcerts;
        }

        if (!advanced_feature::is_disabled('container_workspace')) {
            $enrolledworkspaces = new \stdClass();
            $enrolledworkspaces->area = get_string('spaces', 'container_workspace');
            $enrolledworkspaces->changes = get_string('deletechangesdelete', 'totara_cohort');
            $enrolledworkspaces->scopecount = $audiencedata->enrolled_workspace_count;
            $items[] = $enrolledworkspaces;
        }

        if (!advanced_feature::is_disabled('goals')) {
            $goals = new \stdClass();
            $goals->area = get_string('goals', 'totara_hierarchy');
            $goals->changes = get_string('deletechangesunassign', 'totara_cohort');
            $goals->scopecount = $audiencedata->goals_count;
            $items[] = $goals;
        }

        $roles = new \stdClass();
        $roles->area = get_string('roles');
        $roles->changes = get_string('deletechangesunassign', 'totara_cohort');
        $roles->scopecount = $audiencedata->roles_count;
        $items[] = $roles;

        // Catalog items
        $hiddencourses = new \stdClass();
        $hiddencourses->area = get_string('coursecatalog', 'totara_cohort');
        $hiddencourses->changes = get_string('deletechangeshidecourses', 'totara_cohort');
        $hiddencourses->scopecount = $audiencedata->visible_courses;
        $items[] = $hiddencourses;

        if (!advanced_feature::is_disabled('programs')) {
            $hiddenprogs = new \stdClass();
            $hiddenprogs->area = get_string('coursecatalog', 'totara_cohort');
            $hiddenprogs->changes = get_string('deletechangeshideprogs', 'totara_cohort');
            $hiddenprogs->scopecount = $audiencedata->visible_progs;
            $items[] = $hiddenprogs;
        }

        if (!advanced_feature::is_disabled('certifications')) {
            $hiddencerts = new \stdClass();
            $hiddencerts->area = get_string('coursecatalog', 'totara_cohort');
            $hiddencerts->changes = get_string('deletechangeshidecerts', 'totara_cohort');
            $hiddencerts->scopecount = $audiencedata->visible_certs;
            $items[] = $hiddencerts;
        }

        // Plugins
        foreach ($audiencedata->affected_plugins as $affected_plugin) {
            $item = new \stdClass();
            $item->area = $affected_plugin->area;
            $item->changes = $affected_plugin->changes;
            $item->scopecount = $affected_plugin->count;
            $items[] = $item;
        }

        $data = [];
        $data['cohortid'] = $audiencedata->id;
        $data['sesskey'] = sesskey();
        $data['items'] = $items;
        $data['contextid'] = $audiencedata->contextid;
        $data['showall'] = $audiencedata->showall;
        $a = new \stdClass();
        $a->name = $audiencedata->name;
        $a->idnumber = $audiencedata->idnumber;
        $data['headingtext'] = get_string('deleteaudience', 'totara_cohort', $a);

        return new delete($data);
    }
}
