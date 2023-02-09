<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_mvc
 */

use totara_mvc\controller;
use totara_mvc\view;
use totara_mvc\viewable;

defined('MOODLE_INTERNAL') || die();


class totara_mvc_controller_testcase extends advanced_testcase {

    /**
     * Teest that the action() method needs to overridden by the controller
     */
    public function test_action_needs_override(): void {
        // Use an anonymous class here to ease testing
        $controller = new my_test_controller();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches('/No default action defined./');

        $controller->process();
    }

    /**
     * Test that the action() method is calles and the output is rendered
     */
    public function test_action_with_simple_output(): void {
        $controller = new class() extends my_test_controller {
            public function action() {
                return new class implements viewable {
                    public function render(): string {
                        return 'test output';
                    }
                };
            }
        };

        $this->expectOutputString('test output');

        $controller->process();
    }

    /**
     * Test that an exception is thrown if an invalid/non-existing action is called
     */
    public function test_invalid_custom_action(): void {
        $controller = new my_test_controller();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Missing action method action_invalid_method');

        $controller->process('invalid_method');
    }

    /**
     * Test that a custom action is successfully called
     */
    public function test_valid_custom_action(): void {
        $controller = new class() extends my_test_controller {
            public function action_valid_method() {
                return new class implements viewable {
                    public function render(): string {
                        return 'test output';
                    }
                };
            }
        };

        $this->expectOutputString('test output');

        $controller->process('valid_method');
    }

    /**
     * Test that the require login works and the user gets redirected if not logged in
     */
    public function test_require_login(): void {
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Unsupported redirect detected, script execution terminated');

        new class() extends controller {
            protected $url = '/totara/mvc/version.php';

            protected function setup_context(): context {
                return context_system::instance();
            }

            public function action() {
                return new class implements viewable {
                    public function render(): string {
                        return 'test output';
                    }
                };
            }
        };
    }

    public function test_require_login_is_called_on_failed_context_setup(): void {
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Unsupported redirect detected, script execution terminated');

        new class() extends controller {
            protected $url = '/totara/mvc/version.php';

            protected function setup_context(): context {
                throw new InvalidArgumentException('');
            }

            public function action() {
                return new class implements viewable {
                    public function render(): string {
                        return 'test output';
                    }
                };
            }
        };
    }

    public function test_context_setup_exceptions_are_throw_after_authorize(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bad context set up');

        new class() extends controller {
            protected $url = '/totara/mvc/version.php';


            protected function setup_context(): context {
                throw new InvalidArgumentException('Bad context set up');
            }

            public function action() {
                return new class implements viewable {
                    public function render(): string {
                        return 'test output';
                    }
                };
            }

            protected function authorize(): void {

            }
        };
    }

    /**
     * Test that overriding the default context methods works
     */
    public function test_override_default_context(): void {
        $this->setAdminUser();

        $controller = new class() extends controller {
            protected function setup_context(): context {
                return context_user::instance($GLOBALS['USER']->id);
            }
        };

        $this->assertEquals(context_user::instance($GLOBALS['USER']->id), $controller->get_context());
        $this->assertEquals(context_user::instance($GLOBALS['USER']->id), $controller->get_page()->context);
    }

    /**
     * Test that layout can be defined in the controller and it is applied
     */
    public function test_layout_can_be_defined(): void {
        $controller = new class() extends my_test_controller {
            protected $layout = 'custom_layout';
        };

        $page = $controller->get_page();
        $this->assertEquals('custom_layout', $page->pagelayout);
        $this->assertEquals('custom_layout', $controller->get_layout());
    }

    /**
     * Test the shortcut method to get the currently logged in user
     */
    public function test_currently_logged_in_user(): void {
        global $USER;

        $this->setAdminUser();

        $controller = new my_test_controller();

        $this->assertEquals($USER, $controller->currently_logged_in_user());
    }

    /**
     * Test the shortcut method for optional params
     */
    public function test_get_optional_params_shortcuts(): void {
        $_GET['test1'] = 'value1';
        $_GET['test2'] = 2;
        $_GET['test3'] = true;
        $_GET['test4'] = ['a', 'b'];

        $controller = new my_test_controller();

        $this->assertEquals('value1', $controller->get_optional_param('test1', null, PARAM_TEXT));
        $this->assertEquals('2', $controller->get_optional_param('test2', null, PARAM_TEXT));
        $this->assertEquals(2, $controller->get_optional_param('test2', null, PARAM_INT));
        $this->assertEquals(true, $controller->get_optional_param('test3', null, PARAM_BOOL));
        $this->assertEquals('default', $controller->get_optional_param('idontexist', 'default', PARAM_TEXT));
        $this->assertEquals(['a', 'b'], $controller->get_optional_param_array('test4', ['default'], PARAM_TEXT));
        $this->assertEquals(['default'], $controller->get_optional_param_array('idontexist', ['default'], PARAM_TEXT));

        try {
            $controller->get_optional_param('test4', null, PARAM_INT);
            $this->fail('Expected exception for invalid array optional param');
        } catch (moodle_exception $exception) {
            $this->assertStringContainsString(
                'Requested a non-array param but got an array. Please use get_param_array().',
                $exception->getMessage()
            );

            $this->assertDebuggingCalled('Invalid array parameter detected in required_param(): test4');
        }
    }

    /**
     * Test the shortcut method for requireds params
     */
    public function test_get_required_params_shortcuts(): void {
        $_GET['test1'] = 'value1';
        $_GET['test2'] = 2;
        $_GET['test3'] = true;
        $_GET['test4'] = ['a', 'b'];

        $controller = new my_test_controller();

        $this->assertEquals('value1', $controller->get_required_param('test1', PARAM_TEXT));
        $this->assertEquals('2', $controller->get_required_param('test2', PARAM_TEXT));
        $this->assertEquals(2, $controller->get_required_param('test2', PARAM_INT));
        $this->assertEquals(true, $controller->get_required_param('test3', PARAM_BOOL));
        $this->assertEquals(['a', 'b'], $controller->get_required_param_array('test4', PARAM_TEXT));

        try {
            $controller->get_required_param('idontexist', PARAM_BOOL);
            $this->fail('Expected exception for non-existing required param');
        } catch (moodle_exception $exception) {
            $this->assertEquals('A required parameter (idontexist) was missing', $exception->getMessage());
        }

        try {
            $controller->get_required_param('test4', PARAM_BOOL);
            $this->fail('Expected exception for invalid array required param');
        } catch (coding_exception $exception) {
            $this->assertStringContainsString(
                'Requested a non-array param but got an array. Please use get_param_array().',
                $exception->getMessage()
            );

            $this->assertDebuggingCalled('Invalid array parameter detected in required_param(): test4');
        }
    }

    /**
     * Test the shortcut method for require_capability, positive test if user has capability
     */
    public function test_require_capability_shortcut(): void {
        $this->setAdminUser();

        $controller = new my_test_controller();

        $this->assertEquals($controller, $controller->require_capability('moodle/site:config', context_system::instance()));
    }

    /**
     * Test the shortcut method for require_capability, negative test if user does not have capability
     */
    public function test_require_capability_shortcut_fails(): void {
        $controller = new my_test_controller();

        $this->expectException(required_capability_exception::class);
        $controller->require_capability('moodle/site:config', context_system::instance());
    }

    public function test_returning_array_gets_json_encoded(): void {
        $controller = new class() extends my_test_controller {
            protected $require_login = false;

            public function action() {
                return ['foo' => 'bar'];
            }

        };

        $this->expectOutputString(json_encode(['foo' => 'bar']));
        $controller->process();
    }

    public function test_returning_std_class_gets_json_encoded(): void {
        $std_class = new stdClass();
        $std_class->foo = 'bar';

        $controller = new class() extends my_test_controller {
            protected $require_login = false;

            public function action() {
                $std_class = new stdClass();
                $std_class->foo = 'bar';
                return $std_class;
            }
        };

        $this->expectOutputString(json_encode($std_class));
        $controller->process();
    }

    public function test_returning_to_string_class(): void {
        $controller = new class() extends my_test_controller {
            protected $require_login = false;

            public function action() {
                return new class() {
                    public function __toString() {
                        return 'test output';
                    }
                };
            }
        };

        $this->expectOutputString('test output');
        $controller->process();
    }

    public function test_returning_invalid_class(): void {
        $controller = new class() extends my_test_controller {
            protected $require_login = false;

            public function action() {
                return new class() {
                    public function just_another_method(): string {
                        return 'test output';
                    }
                };
            }
        };

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches('/Expected controller action to return either an implementation of/');
        $controller->process();
    }

    public function test_returning_json_serializable_gets_json_encoded(): void {
        $std_class = new stdClass();
        $std_class->foo = 'bar';

        $controller = new class() extends my_test_controller {
            protected $require_login = false;

            public function action() {
                return new class() implements \JsonSerializable {
                    #[\ReturnTypeWillChange]
                    public function jsonSerialize() {
                        return ['foo' => 'bar'];
                    }
                };
            }
        };

        $this->expectOutputString(json_encode(['foo' => 'bar']));
        $controller->process();
    }

    public function test_single_action(): void {
        $this->setAdminUser();

        $_GET['name'] = 'World';

        $controller = new class extends my_test_controller {
            public function action() {
                $param = $this->get_required_param('name', PARAM_TEXT);

                return view::create('totara_mvc/test', ['name' => $param]);
            }
        };

        $this->expectOutputRegex('/Hello World\!/');

        $controller->process();
    }

    public function test_override_url_param(): void {
        global $PAGE;

        $this->setAdminUser();

        $controller = new class extends my_test_controller {
            protected $url = '/totara/mvc/classes/controller.php';

            public function action() {
                return new view('totara_mvc/test');
            }
        };

        ob_start();
        $controller->process();
        ob_end_clean();

        $this->assertTrue($PAGE->has_set_url());
        $this->assertEquals(
            'https://www.example.com/moodle/totara/mvc/classes/controller.php',
            $PAGE->url->out()
        );
    }

    public function test_missing_url(): void {
        $this->setAdminUser();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('You have to define an url for this controller.');

        $controller = new class extends my_test_controller {
            protected $url = '';

            public function action() {
                return new view('totara_mvc/test');
            }
        };

        $controller->process();
    }

    public function test_set_url_with_params(): void {
        global $PAGE;

        $this->setAdminUser();

        $controller = new class extends my_test_controller {

            public function action() {
                $this->set_url('/totara/mvc/classes/controller.php', ['param1' => 'value1']);
                return new view('totara_mvc/test');
            }
        };

        ob_start();
        $controller->process();
        ob_end_clean();

        $this->assertTrue($PAGE->has_set_url());
        $this->assertEquals(
            'https://www.example.com/moodle/totara/mvc/classes/controller.php?param1=value1',
            $PAGE->url->out()
        );
    }

    public function test_set_url_with_moodle_url_object(): void {
        global $PAGE;

        $this->setAdminUser();

        $controller = new class extends my_test_controller {
            public function action() {
                $this->set_url(new moodle_url('/totara/mvc/classes/controller.php', ['param1' => 'value1']));

                return new view('totara_mvc/test');
            }
        };

        ob_start();
        $controller->process();
        ob_end_clean();

        $this->assertTrue($PAGE->has_set_url());
        $this->assertEquals(
            'https://www.example.com/moodle/totara/mvc/classes/controller.php?param1=value1',
            $PAGE->url->out()
        );
    }

}

class my_test_controller extends controller {

    protected $url = '/totara/mvc/version.php';

    protected function setup_context(): context {
        return context_system::instance();
    }

    protected function authorize(): void {

    }

}
