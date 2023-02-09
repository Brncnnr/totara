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
 * @package mod_facetoface
 */

use core_phpunit\testcase;
use mod_facetoface\testing\generator;
use mod_facetoface\{seminar_event, signup};
use mod_facetoface\seminar;
use core\json_editor\node\paragraph;
use mod_facetoface\form\attendees_message;

class mod_facetoface_attendees_message_testcase extends testcase {
    /**
     * Create number of users.
     * @param int $numberofusers
     * @return array
     */
    private function create_users(int $numberofusers = 5): array {
        $gen = $this->getDataGenerator();
        $a = [];
        for ($i = 0; $i < $numberofusers; $i++) {
            $a[] = $gen->create_user();
        }

        return $a;
    }

    /**
     * @return void
     */
    public function test_send_message(): void {
        $gen = $this->getDataGenerator();

        $this->setAdminUser();
        /** @var generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
       
        $seminar = $this->create_seminar();

        $event = new seminar_event($f2fgen->add_session(['facetoface' => $seminar->get_id(), 'sessiondates' => [time()]]));
        $event->set_facetoface($seminar->get_id());
        $event->save();

        $users = $this->create_users(2);
        $user_ids = null;
        foreach ($users as $i => $user) {
            $gen->enrol_user($user->id, $event->get_seminar()->get_course());

            if ($i === count($users) - 1) {
                $user_ids .= $user->id;
            } else {
                $user_ids .= $user->id . ',';
            }

            $signup = signup::create($user->id, $event);
            $signup->save();
        }

        $cm = $event->get_seminar()->get_coursemodule();
        $context = context_module::instance($cm->id);

        $baseurl = new moodle_url('/mod/facetoface/attendees/messageusers.php', array('s' => $event->get_id()));
        $form = new attendees_message($baseurl, ['s' => $event->get_id(), 'seminarevent' => $event, 'context' => $context]);

        $mock_data = [
            'recipient_group' => [1],
            'recipients_selected' => $user_ids,
            'subject' => 'Subject test',
            'body' => [
                'text' => json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text('This is a test')]
                ]),
                'format' => FORMAT_JSON_EDITOR,
            ],
        ];

        $mform = $form->_form;
        $mform->_flagSubmitted = true;
        $mform->_freezeAll = false;
        $mform->updateSubmission($mock_data, []);

        $sink = $this->redirectEmails();
        $form->send_message();
        $message = $sink->get_messages();
        $body = $message[0]->body;
        self::assertStringContainsString('This is a test', $body);
        self::assertStringContainsString('Subject test', $message[0]->subject);
    }

    /**
     * @return seminar
     */
    private function create_seminar(): seminar{
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        /** @var generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $f2f = $f2fgen->create_instance(['course' => $course->id]);

        $s = new seminar($f2f->id);
        $s->set_attendancetime(seminar::EVENT_ATTENDANCE_UNRESTRICTED)->save();
        return $s;
    }

}