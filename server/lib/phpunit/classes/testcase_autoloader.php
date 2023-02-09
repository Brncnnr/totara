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
 * PHPUnit autoloader for Moodle.
 *
 * @package    core_phpunit
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_phpunit;

use ReflectionClass;

/**
 * Please notice that phpunit testcases obey frankenstyle naming rules,
 * that is full component prefix + _testcase postfix. The files are expected
 * in tests directory inside each component. There are some extra tests
 * directories which require both classname and file path.
 *
 * Examples:
 *
 * vendor/bin/phpunit lib/tests/component_test.php
 *
 * @deprecated since Totara 14.0
 *
 * @package    core_phpunit
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testcase_autoloader implements \PHPUnit\Runner\TestSuiteLoader {

    public function load(string $suiteClassFile): ReflectionClass {
        // NOTE: This is a temporary hack before we rename all testcase files to match class names!
        if (file_exists($suiteClassFile)) {
            $preclasses = get_declared_classes();
            \PHPUnit\Util\Fileloader::checkAndLoad($suiteClassFile);
            $content = file_get_contents($suiteClassFile);
            if (preg_match('/class ([A-Za-z0-9_]+_test(case)?) extends/', $content, $matches)) {
                $suiteClassName = $matches[1];
                if (class_exists($suiteClassName, false)) {
                    $class = new ReflectionClass($suiteClassName);
                    if (!$class->isAbstract() && $class->isSubclassOf(\PHPUnit\Framework\TestCase::class)) {
                        if (realpath($suiteClassFile) === realpath($class->getFileName())) {
                            return $class;
                        }
                    }
                }
            }
            $postclasses = get_declared_classes();
            $diff = array_diff($postclasses, $preclasses);
            foreach ($diff as $suiteClassName) {
                $class = new ReflectionClass($suiteClassName);
                if ($class->isAbstract()) {
                    continue;
                }
                if (!$class->isSubclassOf(\PHPUnit\Framework\TestCase::class)) {
                    continue;
                }
                if (realpath($suiteClassFile) !== realpath($class->getFileName())) {
                    continue;
                }
                return $class;
            }
        }
        throw new \PHPUnit\Framework\Exception(
            sprintf("Testcase class could not be found in '%s'.", $suiteClassFile)
        );
    }

    public function reload(ReflectionClass $aClass): ReflectionClass {
        return $aClass;
    }
}
