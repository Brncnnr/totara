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


use totara_mvc\view;
use totara_mvc\view_override;
use totara_mvc\viewable;

defined('MOODLE_INTERNAL') || die();


class totara_mvc_view_testcase extends advanced_testcase {

    public function test_view_no_template() {
        $view = new view(null, ['name' => 'James']);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Expected a template but no template was defined in this view.');

        $view->render();
    }

    public function test_view_with_constructor_template() {
        $view = new view('totara_mvc/test', ['name' => 'James']);

        $output = $view->render();

        $this->assertMatchesRegularExpression('/Hello James!/', $output);
    }

    public function test_view_with_set_template() {
        $view = (new view(null, ['name' => 'James']))
            ->set_template('totara_mvc/test');

        $output = $view->render();

        $this->assertMatchesRegularExpression('/Hello James!/', $output);
    }

    public function test_define_title_as_array() {
        $view = new class(null) extends view {
            protected $title = ['pluginname', 'totara_mvc'];
        };

        $this->assertEquals(get_string('pluginname', 'totara_mvc'), $view->get_title());
    }

    public function test_define_title_as_string() {
        $view = new class(null) extends view {
            protected $title = 'my title';
        };

        $this->assertEquals('my title', $view->get_title());
    }

    public function test_set_title_as_string() {
        $view = new view(null);
        $view->set_title('my title');

        $this->assertEquals('my title', $view->get_title());
    }

    public function test_set_get_data() {
        $data = ['name' => 'James'];
        $view = new view(null, $data);

        $this->assertEquals($data, $view->get_data());
    }

    public function test_set_title_is_propagated_to_page() {
        $view = new view('totara_mvc/test');
        $view->set_title('my title');

        $view->render();

        $this->assertEquals('my title', $view->get_page()->title);
        $this->assertEquals('my title', $view->get_page()->heading);
    }

    public function test_render_string() {
        $view = new view('totara_mvc/test', 'test output');
        $this->assertMatchesRegularExpression('/test output/', $view->render());
    }

    public function test_render_nested_views() {
        $view = new view(null, new view(null, new view(null, 'test output')));
        $this->assertMatchesRegularExpression('/test output/', $view->render());

        $nested_view = new view('totara_mvc/test', ['name' => 'Cook']);
        $view = new view('totara_mvc/test', ['name' => $nested_view]);
        $this->assertMatchesRegularExpression('/Hello Hello Cook!!/', $view->render());

        $nested_view2 =  new view('totara_mvc/test', ['name' => $nested_view]);
        $view = new view('totara_mvc/test', ['name' => $nested_view2]);
        $this->assertMatchesRegularExpression('/Hello Hello Hello Cook!!!/', $view->render());
    }

    public function test_render_recursive_viewable() {
        $viewable = new class() implements viewable {
            public function render(): string {
                return 'test output';
            }
        };
        $view = new view('totara_mvc/test2', ['name' => $viewable]);
        $this->assertMatchesRegularExpression('/test output/', $view->render());
    }

    public function test_render_widget_directly() {
        $button = new single_button(new moodle_url('/'), 'test button', 'get');
        $view = new view(null, $button);

        $this->assertMatchesRegularExpression('/test button/', $view->render());
    }

    public function test_render_recursive_widget() {
        $data = ['name' => new single_button(new moodle_url('/'), 'test button', 'get')];
        $view = new view('totara_mvc/test2', $data);

        $this->assertMatchesRegularExpression('/test button/', $view->render());
    }

    public function test_get_core_renderer() {
        global $OUTPUT;

        $this->assertEquals($OUTPUT, view::core_renderer());
        $this->assertEquals($OUTPUT, (new view(null))->get_renderer());
    }

    public function test_overrides() {
        $override1 = $this->getMockBuilder(view_override::class)
            ->getMock();

        $override1->expects($this->once())
            ->method('apply')
            ->with($this->isInstanceOf(view::class));

        $override2 = $this->getMockBuilder(view_override::class)
            ->getMock();

        $override2->expects($this->once())
            ->method('apply')
            ->with($this->isInstanceOf(view::class));

        $view = new view('totara_mvc/test', ['name' => 'James']);

        $view->add_override($override1);
        $view->add_override($override2);

        $overrides = $view->get_overrides();
        $this->assertCount(2, $overrides);
        $this->assertContains($override1, $overrides);
        $this->assertContains($override2, $overrides);

        $view->render();

        $view->clear_overrides();

        $this->assertEmpty($view->get_overrides());
    }

}
