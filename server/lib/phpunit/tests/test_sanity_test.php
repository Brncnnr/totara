<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package core
 */

use core_phpunit\testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * Check sanity of unit tests.
 *
 * @group core
 */
class core_phpunit_test_sanity_testcase extends testcase {

    /**
     * A common mistake when using expectException() is to assume that after the exception is thrown, the rest
     * of the test method is still executed. But that's not the case, and it can go undetected.
     * So here we scan all test files for assert calls after expectException() calls in the same method.
     * Also, we check for multiple calls of expectException() in the same method.
     *
     * When the expectException() call is nested in a code block inside the function, we don't check any further as
     * it's correctly done in many places when conditionally expecting an exception depending on data provider data.
     * In that case it's OK when asserts follow.
     *
     * @return void
     */
    public function test_no_assert_after_expect_exception(): void {
        foreach ($this->get_unit_test_files_iterator() as $file) {
            $this->verify_no_assert_after_expect_exception($file->getPathname());
        }
    }

    /**
     * @param string $test_file
     * @return void
     */
    private function verify_no_assert_after_expect_exception(string $test_file): void {
        $file_contents = file_get_contents($test_file);

        // We are only interested in the tests that use expectException() or expectExceptionMessage().
        if (false === strpos($file_contents, 'expectException')) {
            return;
        }

        // We are only interested in certain php tokens and the order in which they occur.
        $tokens = array_filter(
            token_get_all($file_contents),
            function ($token) {
                return $this->is_assert_token($token)
                    || $this->is_function_head_token($token)
                    || $this->is_expect_token($token)
                    || $this->is_opening_brace_token($token)
                    || $this->is_closing_brace_token($token);
            }
        );
        unset($file_contents);

        $found_expect_in_current_method = false;
        $num_expect_exception = 0;
        $num_expect_exception_message = 0;
        $depth = 0;
        foreach ($tokens as $token) {
            if ($this->is_opening_brace_token($token)) {
                $depth ++;
            } else if ($this->is_closing_brace_token($token)) {
                $depth --;
            } else if ($depth < 2 && $this->is_expect_token($token)) {
                $found_expect_in_current_method = true;
                if ($this->is_expect_exception_token($token)) {
                    $num_expect_exception ++;
                    if ($num_expect_exception > 1) {
                        $this->fail('Multiple expectException() calls in one method detected: ' . $test_file . ':' . $token[2]);
                    }
                } else {
                    $num_expect_exception_message ++;
                    if ($num_expect_exception_message > 1) {
                        $this->fail('Multiple expectExceptionMessage() calls in one method detected: ' . $test_file . ':' . $token[2]);
                    }
                }
            } else if ($this->is_function_head_token($token)) {
                // It's a new method, so reset our variables.
                $found_expect_in_current_method = false;
                $depth = 0;
                $num_expect_exception = 0;
                $num_expect_exception_message = 0;
            } else if ($found_expect_in_current_method && $this->is_assert_token($token)) {
                $this->fail('Assert call detected after expectException() in: ' . $test_file . ':' . $token[2]);
            }
        }
    }

    /**
     * Get an iterator over all the unit test files. We identify the files by having the _test.php suffix and being
     * in a /tests/ directory.
     *
     * @return RecursiveIteratorIterator
     */
    private function get_unit_test_files_iterator(): RecursiveIteratorIterator {
        global $CFG;
        $dir = new RecursiveDirectoryIterator($CFG->dirroot);
        $files = new RecursiveCallbackFilterIterator($dir, function ($file, $key, $iterator) {
            if ($iterator->hasChildren()) {
                return true;
            }
            return (
                $file->isFile()
                && substr($file->getFilename(), -9) === '_test.php'
                && false !== strpos($file->getPathname(), '/tests/')
            );
        });
        return new RecursiveIteratorIterator($files);
    }

    private function is_function_head_token($token): bool {
        return is_array($token) && $token[0] === T_FUNCTION;
    }

    private function is_expect_token($token): bool {
        return $this->is_expect_exception_token($token) || $this->is_expect_exception_message_token($token);
    }

    private function is_expect_exception_token($token): bool {
        return is_array($token) && ($token[1] === 'expectException');
    }

    private function is_expect_exception_message_token($token): bool {
        return is_array($token) && ($token[1] === 'expectExceptionMessage');
    }

    private function is_assert_token($token): bool {
        return is_array($token) && strpos($token[1], 'assert') === 0;
    }

    private function is_opening_brace_token($token): bool {
        return $token === '{';
    }

    private function is_closing_brace_token($token): bool {
        return $token === '}';
    }
}
