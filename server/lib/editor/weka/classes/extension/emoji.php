<?php
/**
 * This file is part of Totara LMS
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package editor_weka
 */
namespace editor_weka\extension;

use core\orm\query\builder;
use editor_weka\entity\emoji as entity;

/**
 * @method static attachment create(array $option)
 */
final class emoji extends extension {
    /**
     * @var array
     */
    private $emojis;

    /**
     * emoji constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->emojis = [];
    }

    /**
     * @return string
     */
    public function get_js_path(): string {
        return 'editor_weka/extensions/emoji';
    }

    /**
     * @param array $options
     * @return void
     */
    public function set_options(array $options): void {
        parent::set_options($options);

        // Fetch all active emojis from db.
        $builder = builder::table(entity::TABLE);
        $builder->select(['id', 'name', 'category', 'pattern', 'shortcode']);
        $builder->where('active', '=', '1');

        $this->emojis = array_values($builder->fetch());
    }

    /**
     * @return array
     */
    public function get_emojis(): array {
        return $this->emojis;
    }

    /**
     * @return array
     */
    public function get_js_parameters(): array {
        $rtn = parent::get_js_parameters();
        $rtn['emojis'] = $this->get_emojis();

        return $rtn;
    }
}