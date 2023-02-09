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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package editor_weka
 */
namespace editor_weka\extension;

/**
 * @method static attachment create(array $options)
 */
final class attachment extends extension {
    /**
     * @var array
     */
    private $accept_types;

    /**
     * attachment constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->accept_types = [];
    }

    /**
     * @return string
     */
    public function get_js_path(): string {
        return 'editor_weka/extensions/attachment';
    }

    /**
     * @param array $options
     * @return void
     */
    public function set_options(array $options): void {
        global $CFG;
        parent::set_options($options);

        if (array_key_exists('accept_types', $options) && is_array($options['accept_types'])) {
            require_once("{$CFG->dirroot}/lib/filelib.php");
            $this->accept_types = file_get_typegroup('extension', $options['accept_types']);
        }
    }

    /**
     * @return array
     */
    public function get_accept_types(): array {
        return $this->accept_types;
    }

    /**
     * @return array
     */
    public function get_js_parameters(): array {
        $rtn = parent::get_js_parameters();
        $rtn['accept_types'] = $this->get_accept_types();

        return $rtn;
    }
}