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
 * @package weka_notification_placeholder
 */
namespace weka_notification_placeholder;

use editor_weka\extension\abstraction\specific_custom_extension;
use editor_weka\extension\extension as base_extension;

/**
 * Notification placeholder which is only used for plugin totara_notification's placeholder.
 *
 * @method static extension create()
 */
class extension extends base_extension implements specific_custom_extension {
    /**
     * @var string|null
     */
    private $resolver_class_name;

    /**
     * extension constructor.
     * @param string|null $resolver_class_name
     */
    public function __construct(?string $resolver_class_name = null) {
        parent::__construct();
        $this->resolver_class_name = $resolver_class_name;
    }

    /**
     * @param array $options
     * @return void
     */
    public function set_options(array $options): void {
        parent::set_options($options);
        if (array_key_exists('resolver_class_name', $options)) {
            // Note that we skip the validation of event_class_name on purpose
            // here. Because the query to fetch the placeholders provided by the
            // notifiable event class name will validate it.
            $this->resolver_class_name = $options['resolver_class_name'];
        }
    }

    /**
     * @return string
     */
    public function get_js_path(): string {
        return 'weka_notification_placeholder/extension';
    }

    /**
     * @return array
     */
    public function get_js_parameters(): array {
        $data = parent::get_js_parameters();
        $data['resolver_class_name'] = $this->resolver_class_name;

        return $data;
    }
}