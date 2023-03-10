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

/**
 * Tests for the quiz_attempt class.
 *
 * @package   mod_quiz
 * @category  test
 * @copyright 2014 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Tests for the quiz_attempt class.
 *
 * @copyright 2014 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_attempt_testcase extends advanced_testcase {

    /**
     * Create quiz and attempt data with layout.
     *
     * @param string $layout layout to set. Like quiz attempt.layout. E.g. '1,2,0,3,4,0,'.
     * @return quiz_attempt the new quiz_attempt object
     */
    protected function create_quiz_and_attempt_with_layout($layout) {

        // Make a user to do the quiz.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        // Make a quiz.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id,
            'grade' => 100.0, 'sumgrades' => 2, 'layout' => $layout]);

        $quizobj = quiz::create($quiz->id, $user->id);


        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();

        $page = 1;
        foreach (explode(',', $layout) as $slot) {
            if ($slot == 0) {
                $page += 1;
                continue;
            }

            $question = $questiongenerator->create_question('shortanswer', null, ['category' => $cat->id]);
            quiz_add_quiz_question($question->id, $quiz, $page);
        }

        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);

        return quiz_attempt::create($attempt->id);
    }

    public function test_attempt_url() {
        $attempt = $this->create_quiz_and_attempt_with_layout('1,2,0,3,4,0,5,6,0');

        $attemptid = $attempt->get_attempt()->id;
        $cmid = $attempt->get_cmid();
        $url = '/mod/quiz/attempt.php';
        $params = ['attempt' => $attemptid, 'cmid' => $cmid, 'page' => 2];

        $this->assertEquals(new moodle_url($url, $params), $attempt->attempt_url(null, 2));

        $params['page'] = 1;
        $this->assertEquals(new moodle_url($url, $params), $attempt->attempt_url(3));

        $questionattempt = $attempt->get_question_attempt(4);
        $expecteanchor = $questionattempt->get_outer_question_div_unique_id();
        $this->assertEquals(new moodle_url($url, $params, $expecteanchor), $attempt->attempt_url(4));

        $this->assertEquals(new moodle_url('#'), $attempt->attempt_url(null, 2, 2));
        $this->assertEquals(new moodle_url('#'), $attempt->attempt_url(3, -1, 1));

        $questionattempt = $attempt->get_question_attempt(4);
        $expecteanchor = $questionattempt->get_outer_question_div_unique_id();
        $this->assertEquals(new moodle_url(null, null, $expecteanchor, null), $attempt->attempt_url(4, -1, 1));

        // Summary page.
        $url = '/mod/quiz/summary.php';
        unset($params['page']);
        $this->assertEquals(new moodle_url($url, $params), $attempt->summary_url());

        // Review page.
        $url = '/mod/quiz/review.php';
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url());

        $params['page'] = 1;
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(3, -1, false));
        $this->assertEquals(new moodle_url($url, $params, $expecteanchor), $attempt->review_url(4, -1, false));

        unset($params['page']);
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(null, 2, true));
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(1, -1, true));

        $params['page'] = 2;
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(null, 2, false));
        unset($params['page']);

        $params['showall'] = 0;
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(null, 0, false));
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(1, -1, false));

        $params['page'] = 1;
        unset($params['showall']);
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(3, -1, false));

        $params['page'] = 2;
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(null, 2));
        $this->assertEquals(new moodle_url('#'), $attempt->review_url(null, -1, null, 0));

        $questionattempt = $attempt->get_question_attempt(3);
        $expecteanchor = '#' . $questionattempt->get_outer_question_div_unique_id();
        $this->assertEquals(new moodle_url($expecteanchor), $attempt->review_url(3, -1, null, 0));

        $questionattempt = $attempt->get_question_attempt(4);
        $expecteanchor = '#' . $questionattempt->get_outer_question_div_unique_id();
        $this->assertEquals(new moodle_url($expecteanchor), $attempt->review_url(4, -1, null, 0));
        $this->assertEquals(new moodle_url('#'), $attempt->review_url(null, 2, true, 0));
        $this->assertEquals(new moodle_url('#'), $attempt->review_url(1, -1, true, 0));
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(null, 2, false, 0));
        $this->assertEquals(new moodle_url('#'), $attempt->review_url(null, 0, false, 0));
        $this->assertEquals(new moodle_url('#'), $attempt->review_url(1, -1, false, 0));

        $params['page'] = 1;
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(3, -1, false, 0));

        // Setup another attempt.
        $attempt = $this->create_quiz_and_attempt_with_layout(
            '1,2,3,4,5,6,7,8,9,10,0,11,12,13,14,15,16,17,18,19,20,0,' .
            '21,22,23,24,25,26,27,28,29,30,0,31,32,33,34,35,36,37,38,39,40,0,' .
            '41,42,43,44,45,46,47,48,49,50,0,51,52,53,54,55,56,57,58,59,60,0');

        $attemptid = $attempt->get_attempt()->id;
        $cmid = $attempt->get_cmid();
        $params = ['attempt' => $attemptid, 'cmid' => $cmid];
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url());

        $params['page'] = 2;
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(null, 2));

        $params['page'] = 1;
        unset($params['showall']);
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(11, -1, false));

        $questionattempt = $attempt->get_question_attempt(12);
        $expecteanchor = $questionattempt->get_outer_question_div_unique_id();
        $this->assertEquals(new moodle_url($url, $params, $expecteanchor), $attempt->review_url(12, -1, false));

        $params['showall'] = 1;
        unset($params['page']);
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(null, 2, true));

        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(1, -1, true));
        $params['page'] = 2;
        unset($params['showall']);
        $this->assertEquals(new moodle_url($url, $params),  $attempt->review_url(null, 2, false));
        unset($params['page']);
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(null, 0, false));
        $params['page'] = 1;
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(11, -1, false));
        $this->assertEquals(new moodle_url($url, $params, $expecteanchor), $attempt->review_url(12, -1, false));
        $params['page'] = 2;
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(null, 2));
        $this->assertEquals(new moodle_url('#'), $attempt->review_url(null, -1, null, 0));

        $questionattempt = $attempt->get_question_attempt(3);
        $expecteanchor = $questionattempt->get_outer_question_div_unique_id();
        $this->assertEquals(new moodle_url(null, null, $expecteanchor), $attempt->review_url(3, -1, null, 0));

        $questionattempt = $attempt->get_question_attempt(4);
        $expecteanchor = $questionattempt->get_outer_question_div_unique_id();
        $this->assertEquals(new moodle_url(null, null, $expecteanchor), $attempt->review_url(4, -1, null, 0));

        $this->assertEquals(new moodle_url('#'), $attempt->review_url(null, 2, true, 0));
        $this->assertEquals(new moodle_url('#'), $attempt->review_url(1, -1, true, 0));

        $params['page'] = 2;
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(null, 2, false, 0));
        $this->assertEquals(new moodle_url('#'), $attempt->review_url(null, 0, false, 0));
        $this->assertEquals(new moodle_url('#'), $attempt->review_url(1, -1, false, 0));

        $params['page'] = 1;
        $this->assertEquals(new moodle_url($url, $params), $attempt->review_url(11, -1, false, 0));
    }
}
