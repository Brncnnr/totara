<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

use mod_facetoface\asset_list;

defined('MOODLE_INTERNAL') || die();

/**
 * Test suite of searching the asset with distinct records, and pagination is correcly rendered
 */
class mod_facetoface_asset_search_testcase extends advanced_testcase {

    /**
     * Creating a course, and a seminar activity for the course
     * @return array
     */
    private function create_course_with_seminar() {
        /** @var \core\testing\generator $generator */
        $generator = $this->getDataGenerator();
        /** @var \mod_facetoface\testing\generator $f2fgenerator */
        $f2fgenerator = $generator->get_plugin_generator('mod_facetoface');

        $course = $generator->create_course([], ['createsections' => true]);
        $f2f = $f2fgenerator->create_instance((object)['course' => $course->id]);


        return array($course, $f2f);
    }

    /**
     * Generating global assets and a session with lots of sessions dates that use assets
     *
     * @param stdClass $user
     * @param stdClass $f2f
     * @param int $numberofassets
     *
     * @return stdClass
     */
    private function create_session_with_assets(stdClass $user, stdClass $f2f, $numberofassets=50) {
        global $DB;

        /** @var \mod_facetoface\testing\generator $f2fgenerator */
        $f2fgenerator = $this->getDataGenerator()->get_plugin_generator("mod_facetoface");

        // session time is for session date, and this will increase to avoid one of constraints in db
        $sessiontime = time() + 3600;
        $time = time();

        $sessionid = $f2fgenerator->add_session((object)['facetoface' => $f2f->id]);

        for ($i = 0; $i < $numberofassets; $i++) {
            $asset = $f2fgenerator->add_site_wide_asset([
                'name' => "asset_{$i}",
                'usercreated' => $user->id,
                'usermodified' => $user->id,
                'timecreated' => $time,
                'timemodified' => $time
            ]);

            if ($i % 2 === 0) {
                $sessiondate = (object)[
                    'sessionid' => $sessionid,
                    'timestart' => $sessiontime,
                    'timefinish' => $sessiontime + 7200,
                    'sessiontimezone' => 'Pacific/Auckland',
                    'assetids' => [$asset->id],
                ];

                $sessiondateid = $DB->insert_record("facetoface_sessions_dates", $sessiondate);
                $DB->insert_record("facetoface_asset_dates", (object)[
                    'sessionsdateid' => $sessiondateid,
                    'assetid' => $asset->id
                ]);

                $sessiontime += 14400;
            }
        }

        $session = new stdClass;
        $session->id = $sessionid;
        return $session;
    }

    /**
     * The environment is 50 assets with 25 record in asset session date, therefore, the sql count within search would
     * give a result about 60 records, and this happened because the result set also includes duplicated entries. The
     * test suite is to ensure that there should be no duplicated entries included, and as a result it should give out
     * a number 50 as the total when search for keyword.
     *
     * @return void
     */
    public function test_search_asset_with_distinct_record() {
        global $USER, $CFG;

        require_once($CFG->dirroot . '/mod/facetoface/dialogs/seminar_dialog_content.php');

        $this->setAdminUser();

        list($course, $f2f) = $this->create_course_with_seminar();
        $session = $this->create_session_with_assets($USER, $f2f);

        $selected = 0;
        $offset = 0;
        $timestart = time();
        $timefinish = time();
        $seminarevent = new \mod_facetoface\seminar_event($session->id);

        $assetslist = asset_list::get_available(0, 0, $seminarevent);
        $availableassets = asset_list::get_available($timestart, $timefinish, $seminarevent);
        $selectedids = explode(',', $selected);
        $allassets = [];
        $selectedassets = [];
        $unavailableassets = [];
        foreach ($assetslist as $assetid => $asset) {

            $dialogdata = (object)[
                'id' => $assetid,
                'fullname' => $asset->get_name(),
                'custom' => $asset->get_custom(),
            ];

            customfield_load_data($dialogdata, "facetofaceasset", "facetoface_asset");
            if (!$availableassets->contains($assetid) && $seminarevent->get_cancelledstatus() == 0) {
                $unavailableassets[$assetid] = $assetid;
                $dialogdata->fullname .= get_string('assetalreadybooked', 'mod_facetoface');
            }

            if ($dialogdata->custom && $seminarevent->get_cancelledstatus() == 0) {
                $dialogdata->fullname .= ' (' . get_string('facetoface', 'mod_facetoface') . ': ' . format_string($f2f->name) . ')';
            }

            if (in_array($assetid, $selectedids)) {
                $selectedassets[$assetid] = $dialogdata;
            }

            $allassets[$assetid] = $dialogdata;
        }

        $params = [
            'facetofaceid' => $f2f->id,
            'sessionid' => $session->id,
            'timestart' =>  $timestart,
            'timefinish' => $timefinish,
            'selected' => $selected,
            'offset' => $offset,
        ];

        $dialog = new \seminar_dialog_content();
        $dialog->baseurl = '/mod/facetoface/asset/ajax/sessionassets.php';
        $dialog->proxy_dom_data(array('id', 'custom', 'fullname'));
        $dialog->type = \totara_dialog_content::TYPE_CHOICE_MULTI;
        $dialog->items = $allassets;
        $dialog->disabled_items = $unavailableassets;
        $dialog->selected_items = $selectedassets;
        $dialog->selected_title = 'itemstoadd';
        $dialog->lang_file = 'mod_facetoface';
        $dialog->createid = 'show-editcustomasset' . $offset . '-dialog';
        $dialog->customdata = $params;
        $dialog->search_code = '/mod/facetoface/dialogs/search.php';
        $dialog->searchtype = 'facetoface_asset';
        $dialog->string_nothingtodisplay = 'error:nopredefinedassets';
        // Additional url parameters needed for pagination in the search tab.
        $dialog->urlparams = $params;

        $_POST = [
            'query' => 'asset',
            'page' => 0
        ];

        $content = $dialog->generate_search();
        $paging_rendering_expected = '<div class="search-paging"><div class="paging"></div></div>';
        $this->assertStringContainsString($paging_rendering_expected, $content);
    }
}
