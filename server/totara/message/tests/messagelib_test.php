<?php
/**
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
 * @package totara_message
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
/** @var core_config $CFG */
require_once($CFG->dirroot . "/totara/message/messagelib.php");

/**
 * Class messagelib_test
 * @method fail(string $message)
 * @group totara_message
 */
class totara_message_messagelib_testcase extends \core_phpunit\testcase {
    /**
     * Return the mockup of event data.
     *
     * First Case: the mockup $eventdata would use attr contexturl
     * as an instance of `moodle_url`
     *
     * Second Case: the mockup $eventdata would use attr contexturl
     * as a string url
     *
     * @return array
     */
    public function provide_data(): array {
        $casemessagehtml = [
            /** @lang text */"There has been a new comment on the plan.<br /><br />",
            /** @lang text */"<strong>Learner: </strong> Bolo bala<br />",
            /** @lang text */"<strong>Plan: </strong> Plan 1<br />",
            /** @lang text */"<strong>Comment: </strong><div class=\"text_to_html\">cece</div>",
            /** @lang text */" - <em>Comment by Admin User on Tuesday, 26 June 2018, 3:55 PM</em>"
        ];

        $casemessage = [
            "There has been a new comment on the plan.",
            "LEARNER: Bolo bala",
            "PLAN: Plan 1",
            "COMMENT:",
            "cece - _Comment by Admin User on Tuesday, 26 June 2018, 3:55 PM_",
        ];

        $case1expected = [
            /** @lang text */"There has been a new comment on the plan.<br /><br />",
            /** @lang text */" <strong>Learner: </strong> Bolo bala<br /> ",
            /** @lang text */"<strong>Plan: </strong> Plan 1<br /> ",
            /** @lang text */"<strong>Comment: </strong>",
            /** @lang text */"<div class=\"text_to_html\">cece</div>",
            /** @lang text */"  - <em>Comment by Admin User on Tuesday, 26 June 2018, 3:55 PM</em><br />",
            /** @lang text */"<br />More details can be found at:<br /><br />",
            /** @lang text */"<a href=\"http://totara.local/totara/plan/components/program/view.php?id=1&amp;itemid=1%23comments\">",
            /** @lang text */"http://totara.local/totara/plan/components/program/view.php?id=1&amp;itemid=1%23comments</a>",
            /** @lang text */"<br /><br /><hr />To change your preferences for receiving these emails, go to your <a href=\"https://www.example.com/moodle/message/notificationpreferences.php?userid=2\">Notification preferences</a>."
        ];

        $case2expected = [
            /** @lang text */"There has been a new comment on the plan.<br /><br />",
            /** @lang text */" <strong>Learner: </strong> Bolo bala<br /> ",
            /** @lang text */"<strong>Plan: </strong> Plan 1<br /> ",
            /** @lang text */"<strong>Comment: </strong>",
            /** @lang text */"<div class=\"text_to_html\">cece</div>",
            /** @lang text */"  - <em>Comment by Admin User on Tuesday, 26 June 2018, 3:55 PM</em><br />",
            /** @lang text */"<br />More details can be found at:<br /><br />",
            /** @lang text */"<a href=\"http://totara.local/totara/plan/components/program/view.php\">",
            /** @lang text */"http://totara.local/totara/plan/components/program/view.php</a>",
            /** @lang text */"<br /><br /><hr />To change your preferences for receiving these emails, go to your <a href=\"https://www.example.com/moodle/message/notificationpreferences.php?userid=2\">Notification preferences</a>."
        ];

        return [
            [
                [
                    'contexturlname' => "Plan 1",
                    'icon' => 'learningplan-newcomment',
                    'subject' => 'New comment on Bolo bala\'s plan "Plan 1"',
                    'fullmessage' => implode("\n", $casemessage),
                    'fullmessagehtml' => implode(" ", $casemessagehtml),
                    'contexturl' => new moodle_url (
                            'http://totara.local/totara/plan/components/program/view.php',
                            [
                                'id' => 1,
                                'itemid' => '1#comments'
                            ]
                        ),
                ],
                implode("", $case1expected),
            ],
            [
                [
                    'contexturlname' => "Plan 1",
                    'icon' => 'learningplan-newcomment',
                    'subject' => 'New comment on Bolo bala\'s plan "Plan 1"',
                    'fullmessage' => implode("\n", $casemessage),
                    'fullmessagehtml' => implode(" ", $casemessagehtml),
                    'contexturl' => "http://totara.local/totara/plan/components/program/view.php"
                ],
                implode("", $case2expected)
            ],
        ];
    }


    /**
     * Setting the global user admin for
     * the test suite.
     *
     * Unset the config.smtphosts so that
     * the method we are testing would not
     * sending any email and complain about it
     *
     * Test case of sending totara message
     * to other user, whereas the $eventdata
     * is being mock up, and with a little bit of tweak
     * to make sure that no email got sent out but
     * the format html is being transformed.
     *
     * As Object is passed by reference any way,
     * so it makes the test way easier.
     *
     * @dataProvider    provide_data
     * @param array     $eventdata
     * @param string    $expected
     */
    public function test_tm_alert_send(array $eventdata, string $expected): void {
        global $CFG;
        $CFG->smtphosts = null;

        $this->setAdminUser();
        $user = $GLOBALS['USER'];

        if (!is_object($user) || !isset($user->id)) {
            $this->fail("No User for the test suite");
        }

        /** @var stdClass $eventdata */
        $eventdata = (object) $eventdata;
        $eventdata->userto = $user;
        $eventdata->userfrom = $user;
        $eventdata2 = clone $eventdata;

        tm_alert_send($eventdata);
        $this->assertEquals($expected, $eventdata->fullmessagehtml);

        // Send again re-using the $eventdata object. This caused problems before with repeatedly appended context links
        // as tm_alert_send() manipulates $eventdata and doesn't clone it (see TL-20258).
        tm_alert_send($eventdata2);
        $this->assertEquals($expected, $eventdata2->fullmessagehtml);
    }
}