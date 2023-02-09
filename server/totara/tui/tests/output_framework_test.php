<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_tui
 */

use \totara_tui\output\framework;

defined('MOODLE_INTERNAL') || die();

class totara_tui_output_framework_test extends advanced_testcase {

    public function test_new_instance() {
        self::assertInstanceOf(framework::class, framework::new_instance());
    }

    public function test_new_instances_are_unique() {
        self::assertNotSame(framework::new_instance(), framework::new_instance());
    }

    public function test_get() {
        $page = new moodle_page();
        $framework = framework::get($page);
        self::assertInstanceOf(framework::class, $framework);
    }

    public function test_initialise_requires_tui() {
        $framework = framework::new_instance();

        $property = new ReflectionProperty($framework, 'components');
        $property->setAccessible(true);
        self::assertCount(0, $property->getValue($framework));

        $framework->initialise();

        $components = $property->getValue($framework);
        self::assertCount(1, $components);
        self::assertTrue(in_array('tui', $components));
    }

    public function test_get_head_code() {
        $page = new moodle_page();
        /** @var core_renderer $renderer */
        $renderer = $page->get_renderer('core');
        self::assertInstanceOf(core_renderer::class, $renderer);
        $framework = framework::new_instance();
        $framework->get_head_code($page, $renderer);

        $property = new ReflectionProperty($framework, 'css_urls');
        $property->setAccessible(true);
        $urls = $property->getValue($framework);

        $vendors_file = \totara_tui\local\locator\bundle::get_vendors_file();
        if ($vendors_file && file_exists($vendors_file)) {
            self::assertIsArray($urls);
            self::assertCount(2, $urls);
            /** @var moodle_url[] $urls */
            self::assertSame('/totara/tui/styles.php/ventura/1/p/ltr/tui/notenant', $urls[0]->out_as_local_url());
            self::assertSame('/totara/tui/styles.php/ventura/1/p/ltr/theme_ventura/notenant', $urls[1]->out_as_local_url());
        }
    }

    /**
     * Tests that a theme with no immediate Tui resources will still have its CSS/JS requirement added if the parent
     * themes do have tui resources.
     * Tui styles/javascript loads resources for the current theme, and in the same request for the theme parents.
     */
    public function test_get_head_code_unknown_theme() {
        $theme = $this->createMock('theme_config');
        $theme->name = 'bananas';
        $theme->parents = ['ventura', 'legacy', 'base'];

        $page = new moodle_page();
        $property = new ReflectionProperty($page, '_theme');
        $property->setAccessible(true);
        $property->setValue($page, $theme);

        self::assertSame('bananas', $page->theme->name);

        /** @var core_renderer $renderer */
        $framework = framework::new_instance();
        $framework->get_head_code($page, new core_renderer($page, RENDERER_TARGET_GENERAL));

        $property = new ReflectionProperty($framework, 'css_urls');
        $property->setAccessible(true);
        $urls = $property->getValue($framework);

        $vendors_file = \totara_tui\local\locator\bundle::get_vendors_file();
        if ($vendors_file && file_exists($vendors_file)) {
            self::assertIsArray($urls);
            self::assertCount(2, $urls);
            /** @var moodle_url[] $urls */
            self::assertSame('/totara/tui/styles.php/bananas/1/p/ltr/tui/notenant', $urls[0]->out_as_local_url());
            self::assertSame('/totara/tui/styles.php/bananas/1/p/ltr/theme_bananas/notenant', $urls[1]->out_as_local_url());
        }
    }

    public function test_inject_css_urls() {
        $a = ['a', 'b/tui_scss', 'c'];

        $page = new moodle_page();
        /** @var core_renderer $renderer */
        $renderer = $page->get_renderer('core');
        self::assertInstanceOf(core_renderer::class, $renderer);
        $framework = $page->requires->framework(framework::class);
        $framework->get_head_code($page, $renderer);

        /** @var string[] $a */
        $framework->inject_css_urls($a);
        /** @var string[]|moodle_url[] $a */

        self::assertCount(5, $a);
        self::assertSame('a', $a[0]);
        self::assertSame('/totara/tui/styles.php/ventura/1/p/ltr/tui/notenant', $a[1]->out_as_local_url());
        self::assertSame('/totara/tui/styles.php/ventura/1/p/ltr/theme_ventura/notenant', $a[2]->out_as_local_url());
        self::assertSame('b/tui_scss', $a[3]);
        self::assertSame('c', $a[4]);
    }

    public function test_inject_js_urls() {
        $a = ['a', 'b/tui', 'c'];

        $page = new moodle_page();
        /** @var core_renderer $renderer */
        $renderer = $page->get_renderer('core');
        self::assertInstanceOf(core_renderer::class, $renderer);
        $framework = $page->requires->framework(framework::class);
        $framework->get_head_code($page, $renderer);

        /** @var string[] $a */
        $framework->inject_js_urls($a, true);
        /** @var string[]|moodle_url[] $a */

        self::assertCount(6, $a);
        self::assertSame('a', $a[0]);
        self::assertSame('b/tui', $a[1]);
        self::assertSame('c', $a[2]);
        self::assertSame('/totara/tui/javascript.php/1/p/vendors', $a[3]->out_as_local_url());
        self::assertSame('/totara/tui/javascript.php/1/p/tui', $a[4]->out_as_local_url());
        self::assertSame('/totara/tui/javascript.php/1/p/theme_ventura', $a[5]->out_as_local_url());

        $framework->inject_js_urls($a, false);
        self::assertCount(6, $a);
    }

    public function test_require_component() {
        $page = new moodle_page;
        $framework = framework::get($page);

        // Tui is required on all pages.
        self::assertTrue($framework->is_component_required('tui'));
        // Confirm nothing changes if we then try to require it again.
        $framework->require_component('tui');
        self::assertTrue($framework->is_component_required('tui'));

        self::assertFalse($framework->is_component_required('samples'));
        $framework->require_component('samples');
        self::assertTrue($framework->is_component_required('samples'));
        $framework->require_component('samples');
        self::assertTrue($framework->is_component_required('samples'));

        self::assertFalse($framework->is_component_required('samples/pages/samples'));
        $framework->require_component('samples/pages/samples');
        self::assertTrue($framework->is_component_required('samples/pages/samples'));
    }

    public function test_require_component_empty_string() {
        self::expectExceptionMessage('component is required');
        $page = new moodle_page;
        $framework = framework::get($page);
        $framework->require_component('');
    }

    public function test_vue_require_component() {
        $page = new moodle_page;
        $framework = framework::get($page);

        // Tui is required on all pages.
        self::assertTrue($framework->is_component_required('tui'));
        // Confirm nothing changes if we then try to require it again.
        $component = framework::vue('tui', $page);
        self::assertInstanceOf(\totara_tui\output\component::class, $component);
        self::assertTrue($framework->is_component_required('tui'));

        self::assertFalse($framework->is_component_required('samples'));
        self::assertFalse($framework->is_component_required('samples/pages/samples'));
        $component = framework::vue('samples/pages/samples', $page);
        self::assertInstanceOf(\totara_tui\output\component::class, $component);
        self::assertTrue($framework->is_component_required('samples'));
        self::assertFalse($framework->is_component_required('samples/pages/samples'));
    }

    public function test_vue_require_component_default_page() {
        global $PAGE;
        $framework = framework::get($PAGE);

        // Tui is required on all pages.
        self::assertTrue($framework->is_component_required('tui'));
        // Confirm nothing changes if we then try to require it again.
        $component = framework::vue('tui');
        self::assertInstanceOf(\totara_tui\output\component::class, $component);
        self::assertTrue($framework->is_component_required('tui'));

        self::assertFalse($framework->is_component_required('samples'));
        self::assertFalse($framework->is_component_required('samples/pages/samples'));
        $component = framework::vue('samples/pages/samples');
        self::assertInstanceOf(\totara_tui\output\component::class, $component);
        self::assertTrue($framework->is_component_required('samples'));
        self::assertFalse($framework->is_component_required('samples/pages/samples'));
    }

    public function test_require_vue() {
        $page = new moodle_page;
        $framework = framework::get($page);

        // Tui is required on all pages.
        self::assertTrue($framework->is_component_required('tui'));
        // Confirm nothing changes if we then try to require it again.
        $framework->require_vue('tui');
        self::assertTrue($framework->is_component_required('tui'));

        // Test a component we haven't loaded
        self::assertFalse($framework->is_component_required('samples'));
        $framework->require_vue('samples/pages/samples');
        self::assertTrue($framework->is_component_required('samples'));
    }

    public function test_get_final_components() {
        $page = new moodle_page;
        $framework = framework::get($page);

        // Tui is required on all pages.
        self::assertSame(['tui'], $framework->get_final_components());

        // Confirm nothing changes if we then try to require it again.
        $framework->require_vue('tui');
        self::assertSame(['tui'], $framework->get_final_components());
    }

    public function test_get_bundles() {
        $page = new moodle_page;
        $framework = framework::get($page);

        // Tui is required on all pages.
        self::assertCount(3, $framework->get_bundles());
        self::assertCount(2, $framework->get_bundles(\totara_tui\local\requirement::TYPE_JS));
        self::assertCount(1, $framework->get_bundles(\totara_tui\local\requirement::TYPE_CSS));

        // Confirm nothing changes if we then try to require it again.
        $framework->require_vue('tui');
        self::assertCount(3, $framework->get_bundles());
        self::assertCount(2, $framework->get_bundles(\totara_tui\local\requirement::TYPE_JS));
        self::assertCount(1, $framework->get_bundles(\totara_tui\local\requirement::TYPE_CSS));
    }

}
