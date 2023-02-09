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

defined('MOODLE_INTERNAL') || die;

abstract class question_testcase extends advanced_testcase {

    /**
     * Tolerance accepted in some unit tests when float operations are involved.
     */
    const GRADE_DELTA = 0.00000005;

    public function assert($expectation, $compare, $notused = '') {

        if (get_class($expectation) === 'question_pattern_expectation') {
            $this->assertMatchesRegularExpression($expectation->pattern, $compare,
                'Expected regex ' . $expectation->pattern . ' not found in ' . $compare);
            return;

        } else if (get_class($expectation) === 'question_no_pattern_expectation') {
            $this->assertDoesNotMatchRegularExpression($expectation->pattern, $compare,
                'Unexpected regex ' . $expectation->pattern . ' found in ' . $compare);
            return;

        } else if (get_class($expectation) === 'question_contains_tag_with_attributes') {
            $this->assertTag(array('tag'=>$expectation->tag, 'attributes'=>$expectation->expectedvalues), $compare,
                'Looking for a ' . $expectation->tag . ' with attributes ' . html_writer::attributes($expectation->expectedvalues) . ' in ' . $compare);
            foreach ($expectation->forbiddenvalues as $k=>$v) {
                $attr = $expectation->expectedvalues;
                $attr[$k] = $v;
                $this->assertNotTag(array('tag'=>$expectation->tag, 'attributes'=>$attr), $compare,
                    $expectation->tag . ' had a ' . $k . ' attribute that should not be there in ' . $compare);
            }
            return;

        } else if (get_class($expectation) === 'question_contains_tag_with_attribute') {
            $attr = array($expectation->attribute=>$expectation->value);
            $this->assertTag(array('tag'=>$expectation->tag, 'attributes'=>$attr), $compare,
                'Looking for a ' . $expectation->tag . ' with attribute ' . html_writer::attributes($attr) . ' in ' . $compare);
            return;

        } else if (get_class($expectation) === 'question_does_not_contain_tag_with_attributes') {
            $this->assertNotTag(array('tag'=>$expectation->tag, 'attributes'=>$expectation->attributes), $compare,
                'Unexpected ' . $expectation->tag . ' with attributes ' . html_writer::attributes($expectation->attributes) . ' found in ' . $compare);
            return;

        } else if (get_class($expectation) === 'question_contains_select_expectation') {
            $tag = array('tag'=>'select', 'attributes'=>array('name'=>$expectation->name),
                'children'=>array('count'=>count($expectation->choices)));
            if ($expectation->enabled === false) {
                $tag['attributes']['disabled'] = 'disabled';
            } else if ($expectation->enabled === true) {
                // TODO
            }
            foreach(array_keys($expectation->choices) as $value) {
                if ($expectation->selected === $value) {
                    $tag['child'] = array('tag'=>'option', 'attributes'=>array('value'=>$value, 'selected'=>'selected'));
                } else {
                    $tag['child'] = array('tag'=>'option', 'attributes'=>array('value'=>$value));
                }
            }

            $this->assertTag($tag, $compare, 'expected select not found in ' . $compare);
            return;

        } else if (get_class($expectation) === 'question_check_specified_fields_expectation') {
            $expect = (array)$expectation->expect;
            $compare = (array)$compare;
            foreach ($expect as $k=>$v) {
                if (!array_key_exists($k, $compare)) {
                    $this->fail("Property {$k} does not exist");
                }
                if ($v != $compare[$k]) {
                    $this->fail("Property {$k} is different");
                }
            }
            $this->assertTrue(true);
            return;

        } else if (get_class($expectation) === 'question_contains_tag_with_contents') {
            $this->assertTag(array('tag'=>$expectation->tag, 'content'=>$expectation->content), $compare,
                'Looking for a ' . $expectation->tag . ' with content ' . $expectation->content . ' in ' . $compare);
            return;
        }

        throw new coding_exception('Unknown expectiontion:'.get_class($expectation));
    }
}
