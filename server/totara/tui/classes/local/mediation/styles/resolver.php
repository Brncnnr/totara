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

namespace totara_tui\local\mediation\styles;

use totara_core\path;
use totara_tui\local\theme_config;

/**
 * Styles resolver
 */
final class resolver extends \totara_tui\local\mediation\resolver {

    /**
     * @var string The theme name the CSS was requested for
     */
    private $themename;

    /**
     * @var string The component that was requested.
     */
    private $component;

    /**
     * @var string The suffix that was requested (production|development)
     */
    private $suffix;

    /**
     * @var bool True if the css should be flipped for rtl
     */
    private $option_rtl;

    /**
     * @var theme_config The theme config instance. Don't access directly, always use {@see self::get_theme()}
     */
    private $theme;

    /**
     * @var int Tenant ID if user belongs to a tenant.
     */
    private $tenant;

    /**
     * Styles constructor.
     * @param string $mediator The class name of the mediator to use to deliver content.
     * @param string $rev The revision number for this resource request.
     * @param string $themename The theme name the CSS was requested for
     * @param string $component The component that was requested.
     * @param string $suffix The suffix that was requested (production|development)
     * @param int $tenant The tenant id if the user belongs to a tenant
     * @param bool $option_rtl True if the css should be flipped for rtl
     * @throws \coding_exception If mediator is not available or of the correct type.
     */
    public function __construct(
        string $mediator,
        string $rev,
        string $themename,
        string $component,
        string $suffix,
        int $tenant,
        bool $option_rtl = false
    ) {
        $this->themename = $themename;
        $this->component = $component;
        $this->suffix = $suffix;
        $this->tenant = $tenant;
        $this->option_rtl = $option_rtl;
        parent::__construct($mediator, $rev);
    }

    /**
     * @inheritDoc
     * @return string
     */
    protected function calculate_etag(): string {
        $content = join('-', [
            'tui',
            $this->get_rev(),
            $this->themename,
            $this->component,
            $this->suffix,
            ($this->option_rtl) ? 'rtl' : 'ltr',
            $this->tenant,
        ]);
        if ($this->should_use_dev_mode()) {
            $content .= '-' . $this->get_sha_for_etag_comparison();
        }
        $etag = sha1($content);
        return $etag;
    }

    /**
     * @inheritDoc
     * @return path
     */
    protected function calculate_cachefile(): path {
        global $CFG;
        return new path($CFG->localcachedir, 'totara_tui-styles', $this->get_etag());
    }

    /**
     * Returns the theme_config instance that will be needed to resovle the CSS.
     * @return theme_config
     */
    private function get_theme(): theme_config {
        if (!$this->theme) {
            $theme = theme_config::load($this->themename);
            $theme->force_svg_use(true);
            $theme->set_rtl_mode($this->option_rtl);
            if (\core_useragent::is_totara_legacy_browser()) {
                $theme->set_legacy_browser(true);
            }
            if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
                $theme->skip_scss_compilation();
            }
            $this->theme = $theme;
        }
        return $this->theme;
    }

    /**
     * @inheritDoc
     * @return string
     */
    protected function get_sha_for_etag_comparison(): string {
        $theme = $this->get_theme();
        return $theme->get_component_sha($this->component, $this->tenant);
    }

    /**
     * @inheritDoc
     * @return string
     */
    protected function get_content_to_cache() {
        $theme = $this->get_theme();
        return $theme->get_css_content_by($this->component, $this->tenant);
    }
}
