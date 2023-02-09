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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_tenant
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Report of tenant participants for non-tenant users.
 */
final class rb_tenant_participants_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;
    public $defaultsortcolumn, $defaultsortorder;

    public function __construct(array $data) {
        $this->embeddedparams = $data;
        $this->url = $this->get_url($data);
        $this->source = 'user';
        $this->shortname = 'tenant_participants';
        $this->fullname = get_string('participantsreport', 'totara_tenant');
        $this->columns = array(
            array(
                'type' => 'user',
                'value' => 'namelinkicon',
                'heading' => get_string('userfullname', 'totara_reportbuilder'),
            ),
            array(
                'type' => 'user',
                'value' => 'username',
                'heading' => get_string('username', 'totara_reportbuilder'),
            ),
            array(
                'type' => 'user',
                'value' => 'emailunobscured',
                'heading' => get_string('useremail', 'totara_reportbuilder'),
            ),
            array(
                'type' => 'user',
                'value' => 'deleted',
                'heading' => get_string('userstatus', 'totara_reportbuilder'),
            ),
            array(
                'type' => 'user',
                'value' => 'lastloginrelative',
                'heading' => get_string('lastlogin', 'totara_reportbuilder'),
            ),
            array(
                'type' => 'user',
                'value' => 'tenantmember',
                'heading' => null,
            ),
            array(
                'type' => 'user',
                'value' => 'actions',
                'heading' => get_string('actions', 'totara_reportbuilder'),
            ),
        );

        $this->filters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'user',
                'value' => 'username',
                'advanced' => 1,
            ),
            array(
                'type' => 'user',
                'value' => 'emailunobscured',
                'advanced' => 1,
                'fieldname' => get_string('useremail', 'totara_reportbuilder'),
            ),
        );

        // No restrictions.
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        parent::__construct();
    }

    /**
     * @param array $data
     * @return string
     */
    public function get_url(array $data): string {
        global $SESSION;
        if (isset($data['participantstenantid']) && is_numeric($data['participantstenantid'])) {
            $SESSION->participantstenantid = $data['participantstenantid'];
            return '/totara/tenant/participants.php?id=' . $data['participantstenantid'];
        }

        if (isset($SESSION->participantstenantid)) {
            return '/totara/tenant/participants.php?id=' . $SESSION->participantstenantid;
        }

        // Fallback to make unit passed.
        return '/totara/tenant/participants.php';

    }

    /**
     * There is no user data here.
     * @return null|boolean always false
     */
    public function embedded_global_restrictions_supported() {
        return false;
    }

    /**
     * Can searches be saved?
     *
     * @return bool
     */
    public static function is_search_saving_allowed() : bool {
        return false;
    }

    /**
     * Check if the user is capable of accessing this report.
     *
     * @param int $reportfor id of the user that this report is being generated for
     * @param reportbuilder $report the report object - can use get_param_value to get params
     * @return bool true if the user can access this report
     */
    public function is_capable($reportfor, $report) {
        global $CFG, $DB, $USER;

        if (empty($CFG->tenantsenabled)) {
            return false;
        }

        // Make sure members do not get access because here might be extra columns.
        if ($reportfor === null) {
            $reportfor = $USER->id;
        }
        $usercontext = context_user::instance($reportfor, IGNORE_MISSING);
        if (!$usercontext or $usercontext->tenantid) {
            return false;
        }

        $tenantid = $report->get_param_value('participantstenantid');
        if (!$tenantid) {
            return false;
        }
        $tenantcontext = context_tenant::instance($tenantid, IGNORE_MISSING);
        if (!$tenantcontext) {
            return false;
        }
        if (has_capability('totara/tenant:view', $tenantcontext, $reportfor) and has_capability('moodle/user:viewalldetails', $tenantcontext, $reportfor)) {
            return true;
        }
        $tenant = \core\record\tenant::fetch($tenantid, IGNORE_MISSING);
        if (!$tenant) {
            return false;
        }
        $categorycontext = context_coursecat::instance($tenant->categoryid, IGNORE_MISSING);
        if (!$categorycontext) {
            return false;
        }
        return has_capability('totara/tenant:viewparticipants', $categorycontext, $reportfor);
    }
}
