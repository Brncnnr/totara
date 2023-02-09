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

namespace totara_tui\local\requirement;

use coding_exception;
use moodle_url;
use totara_core\path;
use totara_tui\local\locator\bundle;
use totara_tui\local\requirement;

/**
 * Represents a SCSS bundle.
 */
final class scss extends requirement {

    public function __construct(string $component) {
        parent::__construct($component, 'tui_bundle.scss');
    }

    /**
     * {@inheritdoc}
     */
    public function get_type(): string {
        return self::TYPE_CSS;
    }

    /**
     * {@inheritdoc}
     */
    public function get_url(array $options = null): moodle_url {
        if (empty($options['theme'])) {
            throw new coding_exception('Theme not specified');
        }
        return $this->make_tui_scss_url($this->component, $options['theme']);
    }

    /**
     * Generate a URL to the the CSS mediator using tui_scss.
     *
     * @param string $component Totara component
     * @param string $theme
     * @return moodle_url
     */
    private function make_tui_scss_url(string $component, string $theme): moodle_url {
        global $CFG, $USER, $SESSION;

        $rev = bundle::get_css_rev();
        $suffix = bundle::get_css_suffix_for_url();
        $direction = right_to_left() ? 'rtl' : 'ltr';
        $tenant = (!isloggedin() || empty($USER->tenantid)) ? 0 : $USER->tenantid;
        if ((!isloggedin() || isguestuser()) && !empty($SESSION->themetenantid)) {
            $tenant = $SESSION->themetenantid;
        }
        $tenant = ($tenant === 0) ? 'notenant' : 'tenant_' . $tenant;

        $arguments = [
            'theme' => $theme,
            'rev' => $rev,
            'type' => $component,
            'suffix' => $suffix,
            'direction' => $direction,
            'tenant' => $tenant,
        ];
        if (empty($CFG->slasharguments)) {
            $url = new moodle_url('/totara/tui/styles.php', $arguments);
        } else {
            $url = new moodle_url('/totara/tui/styles.php');
            $url->set_slashargument("/{$theme}/{$rev}/{$suffix}/{$direction}/{$component}/{$tenant}", 'noparam', true);
        }
        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function get_required_resource(): ?path {
        return bundle::get_bundle_css_file($this->component);
    }
}
