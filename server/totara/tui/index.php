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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_tui
 */

require(__DIR__ . '/../../config.php');

$tc = optional_param('tc', null, PARAM_RAW);
$component = optional_param('component', null, PARAM_RAW);

$params = [];
if ($tc !== null) {
    $params['tc'] = $tc;
}
if ($component !== null) {
    $params['component'] = $component;
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/totara/tui/index.php', $params);

require_login();
require_capability('moodle/site:config', $context);

$PAGE->set_pagelayout('noblocks');
$PAGE->set_title(get_string('samples', 'totara_tui'));

// Get data for the page.
$output = $PAGE->get_renderer('totara_tui');
$component = \totara_tui\output\framework::vue('samples/pages/Samples');

echo $output->header();
echo $output->render($component);
echo $output->footer();
