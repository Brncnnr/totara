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

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");
require_once("{$CFG->dirroot}/mod/facetoface/renderer.php");

use enrol_totara_facetoface\watcher\seminar_watcher;
use mod_facetoface\{seminar, signup, seminar_event, seminar_session, render_event_info_option};
use totara_job\job_assignment;
use mod_facetoface\signup\state\booked;

/**
 * Class mod_facetoface_seminar_renderer_testcase
 */
class mod_facetoface_seminar_renderer_testcase extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        // Make sure we're not dealing with stale cache data on the enrol_totara_facetoface watcher.
        seminar_watcher::reset_enrol_plugin();
    }

    protected function tearDown(): void {
        seminar_watcher::reset_enrol_plugin();
        parent::tearDown();
    }

    /**
     * @param int $approvaltype
     * @param int $approvaltype
     * @return seminar
     */
    private function create_facetoface(int $approvaltype): seminar {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course([], ['createsections' => true]);

        /** @var \mod_facetoface\testing\generator $f2fgen */
        $f2fgen = $generator->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance([
            'course' => $course->id,
            'approvaltype' => $approvaltype
        ]);

        $seminarevent = new seminar_event();
        $seminarevent->set_facetoface($f2f->id)->save();

        $time = time() + 3600;
        $seminarsession = new seminar_session();
        $seminarsession->set_sessionid($seminarevent->get_id())
            ->set_timestart($time)
            ->set_timefinish($time + 7200)
            ->save();

        return new seminar($f2f->id);
    }

    /**
     * @param int $numberofusers
     * @return stdClass[]
     */
    public function create_users(int $numberofusers): array {
        $generator = $this->getDataGenerator();
        $manager = $generator->create_user();
        $managerja = job_assignment::create_default($manager->id);

        $users = [];
        for ($i = 0; $i < $numberofusers; $i++) {
            $user = $generator->create_user();
            job_assignment::create_default($user->id, ['managerjaid' => $managerja->id]);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * @param int           $numberofsignup
     * @param seminar_event $seminarevent
     * @param string        $state
     * @return void
     */
    private function create_signups(int $numberofsignup, seminar_event $seminarevent, string $state): void {
        $generator = $this->getDataGenerator();
        $users = $this->create_users($numberofsignup);
        foreach ($users as $user) {
            $generator->enrol_user($user->id, $seminarevent->get_seminar()->get_course());
            $signup = new signup();
            $signup->set_userid($user->id)->set_sessionid($seminarevent->get_id());
            $signup->save();

            $signup->set_skipapproval(true);
            $signup->switch_state($state);
        }
    }

    /**
     * Instantiate the mod_facetoface_renderer.
     *
     * @return \mod_facetoface_renderer
     */
    private function create_f2f_renderer() : mod_facetoface_renderer {
        global $PAGE;

        $renderer = new mod_facetoface_renderer($PAGE, null);

        return $renderer;
    }

    /**
     * Create a DOMDocument without warnings and errors.
     *
     * @param string $html
     * @return DOMDocument
     */
    private static function new_domdocument(string $html) : DOMDocument {
        $doc = new DOMDocument();
        $doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR); // requires PHP 7.2+, 7.1.4+, 7.0.18+
        return $doc;
    }

    /**
     * @return array
     */
    public function provide_method_approvals_type(): array {
        return array(
            array(seminar::APPROVAL_MANAGER, 'Request approval'),
            array(seminar::APPROVAL_NONE, 'Sign-up'),
            array(seminar::APPROVAL_SELF, 'Sign-up'),
            array(seminar::APPROVAL_ROLE, 'Request approval'),
            array(seminar::APPROVAL_ADMIN, 'Request approval'),
        );
    }

    /**
     * This is the test suite of rendering the facetoface information on the page, whereas any session within facetoface
     * that has enabled with case
     * + approval_manager       => Request approval
     * + approval_self          => Sign-up
     * + approval_none          => Sign-up
     * + approval_role          => Request approval
     * + approval_admin         => Request approval
     * Depending on different approval type, the displaytext would be changed dynammically within the map above
     *
     * @dataProvider provide_method_approvals_type
     * @param int $approvaltype
     * @param string $displaytext
     * @return void
     */
    public function test_rendering_session_with_approval_type(int $approvaltype, string $displaytext): void {
        global $DB, $PAGE;
        $PAGE->set_url("/");

        // Creating users here
        $users = $this->create_users(1);
        $user = $users[0];
        $this->setUser($user);

        $f2f = $this->create_facetoface($approvaltype);

        if ($approvaltype == seminar::APPROVAL_ROLE) {
            $roleapprover1 = $this->getDataGenerator()->create_user();
            $trainerrole = $DB->get_record('role', array('shortname' => 'teacher'));
            $approvalrole = $trainerrole->id;
            $seminarevent = $f2f->get_events()->current();
            $DB->set_field('facetoface', 'approvalrole', $approvalrole, ['id' => $f2f->get_id()]);
            $DB->insert_record('facetoface_session_roles', (object)['sessionid'=> $seminarevent->get_id(), 'roleid' => $approvalrole, 'userid' => $roleapprover1->id]);
        }


        $this->getDataGenerator()->enrol_user($user->id, $f2f->get_course());

        // See the text of the submit button in the event page.
        $renderer = $this->create_f2f_renderer();
        $seminarevent = $f2f->get_events()->current();
        $signup = signup::create($user->id, $seminarevent);
        $content = $renderer->render_seminar_event_information($signup, new render_event_info_option());
        $doc = $this->new_domdocument($content);
        $button = $doc->getElementById('id_submitbutton');
        $this->assertNotNull($button);
        $this->assertStringContainsString($displaytext, $button->getAttribute('value'));
    }

    /**
     * @return void
     */
    public function test_rendering_session_with_waitlist_enabled_and_capacity_is_full(): void {
        global $PAGE;
        $PAGE->set_url("/");

        // Setting user's session here
        $users = $this->create_users(1);
        $user = $users[0];
        $this->setUser($user);

        $f2f = $this->create_facetoface(seminar::APPROVAL_MANAGER);
        $this->getDataGenerator()->enrol_user($user->id, $f2f->get_course());

        // Update the session to allow over book and change the capacity to 2, instead of 10, so that the sign-ups could
        // reach to full capacity.
        /** @var seminar_event $seminarevent */
        $seminarevent = $f2f->get_events()->current();
        $seminarevent->set_allowoverbook(1)->set_capacity(2)->save();

        $this->create_signups(2, $seminarevent, booked::class);

        // See the text of the submit button in the event page.
        $renderer = $this->create_f2f_renderer();
        $signup = signup::create($user->id, $seminarevent);
        $content = $renderer->render_seminar_event_information($signup, new render_event_info_option());
        $doc = $this->new_domdocument($content);
        $button = $doc->getElementById('id_submitbutton');
        $this->assertNotNull($button);
        $this->assertSame("Request approval", $button->getAttribute('value'));
    }
}