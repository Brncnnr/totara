<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package weka_simple_multi_lang
 */
namespace weka_simple_multi_lang;

use context;
use editor_weka\extension\abstraction\specific_custom_extension;
use editor_weka\extension\extension as base_extension;

/**
 * Extension class for weka. It is about multi-lang filter.
 */
class extension extends base_extension implements specific_custom_extension {
    /**
     * @var int|null
     */
    private $context_id;

    /**
     * @var bool
     */
    private $compact;

    /**
     * @var string|null
     */
    private $placeholder_resolver_class_name;

    /**
     * extension constructor.
     */
    public function __construct() {
        global $CFG;

        // Ensure we can reach filter library functions.
        require_once("{$CFG->dirroot}/lib/filterlib.php");

        parent::__construct();
        $this->context_id = null;
        $this->compact = false;
        $this->placeholder_resolver_class_name = null;
    }

    /**
     * @return string
     */
    public function get_js_path(): string {
        return "weka_simple_multi_lang/extension";
    }

    /**
     * @return array
     */
    public function get_js_parameters(): array {
        // Check the availability of multi_lang filter within the context.
        $context_id = $this->context_id ?? SYSCONTEXTID;
        $available_filters = filter_get_active_in_context(context::instance_by_id($context_id, MUST_EXIST));

        return [
            'compact' => $this->compact,
            'is_active' => isset($available_filters['multilang']),
            'placeholder_resolver_class_name' => $this->placeholder_resolver_class_name,
        ];
    }

    /**
     * @param array $options
     * @return void
     */
    public function set_options(array $options): void {
        parent::set_options($options);

        if (isset($options['context_id'])) {
            $this->context_id = $options['context_id'];
        }

        if (isset($options['compact'])) {
            $this->compact = (bool) $options['compact'];
        }

        if (isset($options['placeholder_resolver_class_name'])) {
            $this->placeholder_resolver_class_name = $options['placeholder_resolver_class_name'];
        }
    }
}