<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @package totara
 * @subpackage reportbuilder
 *
 */

defined("MOODLE_INTERNAL") || die();

global $CFG;
require_once($CFG->dirroot . "/totara/reportbuilder/lib.php");

use mod_facetoface\{asset_helper, facilitator_helper, room_helper};
use mod_facetoface\{seminar, seminar_event, seminar_session};

/**
 * Unit test for facetoface_rooms reprot builder
 * and the test is testing the SQL query of custom report plust
 * embedded report
 */
class mod_facetoface_rooms_reportbuilder_testcase extends advanced_testcase {
    /**
     * Saving all the columns for the report builder
     * @param rb_facetoface_base_source    $src
     * @param int                           $id     The report builder id
     */
    private function set_up_columns(rb_facetoface_base_source $src, int $id): void {
        global $DB;
        $columnoptions = $src->defaultcolumns;
        $so = 1;

        foreach ($columnoptions as $columnoption) {
            $heading = isset($columnoption['heading']) ? $columnoption['heading'] : null;
            $column = $src->new_column_from_option(
                $columnoption['type'],
                $columnoption['value'],
                null,
                null,
                $heading,
                !empty($heading),
                0
            );

            $item = [
                'reportid'      => $id,
                'type'          => $column->type,
                'value'         => $column->value,
                'heading'       => $column->heading,
                'hidden'        => $column->hidden,
                'transform'     => $column->transform,
                'aggregate'     => $column->aggregate,
                'sortorder'     => $so,
                'customheading' => 0,
            ];

            $DB->insert_record("report_builder_columns", (object) $item);
            $so += 1;
        }
    }

    /**
     * Saving all the filters for the report builder
     * @param rb_facetoface_base_source    $src
     * @param int                           $id     The report builder id
     */
    public function set_up_filters(rb_facetoface_base_source $src, int $id): void {
        global $DB;
        $filteroptions = $src->filteroptions;
        $so = 1;

        foreach ($filteroptions as $filteroption) {
            if ($filteroption->value !== 'published') {
                continue;
            }
            $item = [
                'reportid' => $id,
                'type' => $filteroption->type,
                'value' => $filteroption->value,
                'sortorder' => 1,
                'advanced' => 0,
                'filtername' => $filteroption->label,
                'customname' => '',
                'region' => rb_filter_type::RB_FILTER_REGION_STANDARD,
                'defaultvalue' => ''
            ];

            $DB->insert_record("report_builder_filters", (object) $item);
            $so += 1;
        }
    }

    /**
     * Helper method to setup the
     * report builder within the phpunit system
     *
     * @param string            $source
     * @param string            $shortname
     * @param stdClass          $user                 The user that is creating the report
     * @param bool              $userembedded         Determine whether using the default source report or not
     * @return reportbuilder
     */
    private function set_up_report_builder(string $source, string $shortname, stdClass $user, $userembedded = false): reportbuilder {
        global $DB;
        $id = null;

        $config = (new rb_config())->set_reportfor($user->id);

        if ($userembedded) {
            return reportbuilder::create_embedded($shortname, $config);
        }

        $rp = [
            'shortname'         => 'f2fr_test',
            'source'            => $source,
            'fullname'          => 'This is SPARTAN',
            'hidden'            => 0,
            'embed'             => 0,
            'accessmode'        => 0,
            'contentmode'       => 0,
            'description'       => 'wowow',
            'recordsperpage'    => 40,
            'toolbarsearch'     => 1,
            'globalrestriction' => 1,
            'timemodified'      => time(),
            'defaultsortorder'  => 4,
        ];

        $id = $DB->insert_record('report_builder', (object)$rp);

        /** @var rb_facetoface_base_source $src */
        $src = reportbuilder::get_source_object($rp['source']);
        $this->set_up_columns($src, $id);
        $this->set_up_filters($src, $id);

        return reportbuilder::create($id, $config);
    }

    /**
     * Insert a site-wide resource as
     * well as an ad-hoc resource
     *
     * @param string $type one of asset, facilitator or room
     * @param stdClass $user
     * @return stdClass[] rooms
     */
    private function create_face2face_resources(string $type, stdClass $user): array {
        $addsitewide = "add_site_wide_{$type}";
        $addcustom = "add_custom_{$type}";
        /** @var \mod_facetoface\testing\generator $f2fgen */
        $f2fgen = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        return [
            $f2fgen->{$addsitewide}([
                'name'              => "test1",
                'capacity'          => 10,
                'allowconflicts'    => 1,
                'description'       => "",
                'usercreated'       => $user->id,
                'usermodified'      => $user->id,
                'timecreated'       => time(),
                'timemodified'      => time(),
            ]),
            $f2fgen->{$addcustom}([
                'name'              => 'test2',
                'capacity'          => 15,
                'allowconflicts'    => 1,
                'description'       => "",
                'usercreated'       => $user->id,
                'usermodified'      => $user->id,
                'timecreated'       => time(),
                'timemodified'      => time(),
            ])
        ];
    }

    /**
     * @return array of [type, source, shortname]
     */
    public function data_provider_types(): array {
        return [
            ['asset', 'facetoface_asset', 'facetoface_assets'],
            ['facilitator', 'facetoface_facilitator', 'facetoface_facilitators'],
            ['room', 'facetoface_rooms', 'facetoface_rooms'] // watch out!
        ];
    }

    /**
     * @return array of [type, source, shortname, published, filteredCount]
     */
    public function data_provider_types_post(): array {
        $data = [];
        foreach ($this->data_provider_types() as $args) {
            $data[] = array_merge($args, [[], 2]);
            $data[] = array_merge($args, [['published' => 0], 1]);
            $data[] = array_merge($args, [['published' => 1], 1]);
        }
        return $data;
    }

    /**
     * Test suite for checking whether
     * the sql is actually working or not
     *
     * @dataProvider data_provider_types_post
     */
    public function test_query_vacancy(string $type, string $source, string $shortname, array $post, int $filteredcount): void {
        global $USER;

        $this->setAdminUser();
        $user = $USER;

        $_POST = $post;
        $reportbuilder = $this->set_up_report_builder($source, $shortname, $user, false);
        $data = $this->create_face2face_resources($type, $user);

        $this->assertEquals($filteredcount, $reportbuilder->get_filtered_count());
    }

    /**
     * The test suite for the case
     * of one resource of record is the custom
     * resource and it is in use.
     *
     * @dataProvider data_provider_types_post
     */
    public function test_query_occupied(string $type, string $source, string $shortname, array $post, int $filteredcount): void {
        global $USER;

        $this->setAdminUser();
        $user = $USER;

        $_POST = $post;
        $reportbuilder = $this->set_up_report_builder($source, $shortname, $user, false);
        $data = $this->create_face2face_resources($type, $user);
        $ghost = $data[1];

        $seminar = new seminar();
        $seminar->set_name('test seminar')->save();
        $seminarevent = new seminar_event();
        $seminarevent->set_facetoface($seminar->get_id())->save();
        $seminarsession = new seminar_session();
        $seminarsession->set_sessionid($seminarevent->get_id())->set_timestart(time() + HOURSECS)->set_timefinish(time() + HOURSECS * 2)->save();

        $helper = "mod_facetoface\\{$type}_helper";
        $helper::sync($seminarsession->get_id(), [$ghost->id]);

        $this->assertEquals($filteredcount, $reportbuilder->get_filtered_count());
    }

    /**
     * The test suite for the scenario that
     * the viewing of report is embedded report
     *
     * Therefore the base query would have been tweaked
     * a bit. However, we still expect the embedded report will
     * only look into those global rooms.
     *
     * @see rb_facetoface_rooms_reportbuilder_test::test_query
     * @dataProvider data_provider_types
     */
    public function test_embedded_query(string $type, string $source, string $shortname): void {
        global $USER;

        $this->setAdminUser();
        $user = $USER;

        $reportbuilder = $this->set_up_report_builder($source, $shortname, $user, true);
        $data = $this->create_face2face_resources($type, $user);

        $this->assertEquals(1, $reportbuilder->get_filtered_count());
    }
}
