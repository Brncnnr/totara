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

namespace mod_quiz\testing;

/**
 * Quiz module test data generator class
 *
 * @package mod_quiz
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class generator extends \core\testing\mod_generator {

    public function create_instance($record = null, array $options = null) {
        global $CFG;

        require_once($CFG->dirroot.'/mod/quiz/locallib.php');
        $record = (object)(array)$record;

        $defaultquizsettings = array(
            'timeopen'               => 0,
            'timeclose'              => 0,
            'preferredbehaviour'     => 'deferredfeedback',
            'attempts'               => 0,
            'attemptonlast'          => 0,
            'grademethod'            => QUIZ_GRADEHIGHEST,
            'decimalpoints'          => 2,
            'questiondecimalpoints'  => -1,
            'attemptduring'          => 1,
            'correctnessduring'      => 1,
            'marksduring'            => 1,
            'specificfeedbackduring' => 1,
            'generalfeedbackduring'  => 1,
            'rightanswerduring'      => 1,
            'overallfeedbackduring'  => 0,
            'attemptimmediately'          => 1,
            'correctnessimmediately'      => 1,
            'marksimmediately'            => 1,
            'specificfeedbackimmediately' => 1,
            'generalfeedbackimmediately'  => 1,
            'rightanswerimmediately'      => 1,
            'overallfeedbackimmediately'  => 1,
            'attemptopen'            => 1,
            'correctnessopen'        => 1,
            'marksopen'              => 1,
            'specificfeedbackopen'   => 1,
            'generalfeedbackopen'    => 1,
            'rightansweropen'        => 1,
            'overallfeedbackopen'    => 1,
            'attemptclosed'          => 1,
            'correctnessclosed'      => 1,
            'marksclosed'            => 1,
            'specificfeedbackclosed' => 1,
            'generalfeedbackclosed'  => 1,
            'rightanswerclosed'      => 1,
            'overallfeedbackclosed'  => 1,
            'questionsperpage'       => 1,
            'shuffleanswers'         => 1,
            'sumgrades'              => 0,
            'grade'                  => 100,
            'timecreated'            => time(),
            'timemodified'           => time(),
            'timelimit'              => 0,
            'overduehandling'        => 'autosubmit',
            'graceperiod'            => 86400,
            'quizpassword'           => '',
            'subnet'                 => '',
            'browsersecurity'        => '',
            'delay1'                 => 0,
            'delay2'                 => 0,
            'showuserpicture'        => 0,
            'showblocks'             => 0,
            'navmethod'              => QUIZ_NAVMETHOD_FREE,
        );

        foreach ($defaultquizsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Age a quiz's responses (and their associated grades) the specified number of seconds
     *
     * This exists to test the 'Later while the quiz is still open' review options.
     *
     * @param string $quizname Quiz whose responses we want to age
     * @param int $seconds Number of seconds to age
     */
    public function age_quiz_responses($quizname, $seconds) {
        global $CFG, $DB;
        $quiz = $DB->get_record('quiz', array('name' => $quizname), '*', MUST_EXIST);
        $sql = "UPDATE {quiz_attempts} 
                   SET timefinish = timefinish - :seconds
                 WHERE quiz = :quizid";
        $DB->execute($sql, ['seconds' => $seconds, 'quizid' => $quiz->id]);

        // Also update grade_grades, which may be hidden based on the quiz response time.
        require_once($CFG->dirroot . "/lib/grade/grade_item.php");
        $grade_item = \grade_item::fetch(array('courseid' => $quiz->course, 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id));
        $sql2 = "UPDATE {grade_grades}
                    SET hidden = hidden - :seconds
                  WHERE itemid = :gradeitemid AND hidden > 1";
        $DB->execute($sql2, ['seconds' => $seconds, 'gradeitemid' => $grade_item->id]);
    }

}
