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
 * @author  Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core_theme
 * @category test
 */

use core\files\type\web_image;
use core\theme\file\theme_file;

/**
 * @coversDefaultClass \core\theme\file\theme_file
 *
 * @group core_theme
 */
class mock_theme_file extends theme_file {

    /** @var bool */
    private $has_default = true;

    /**
     * mock_theme_file constructor.
     *
     * @param theme_config|null $theme_config
     */
    public function __construct(?theme_config $theme_config = null) {
        parent::__construct($theme_config);
        $this->type = new web_image();
    }

    /**
     * @return bool
     */
    public function has_default(): bool {
        return $this->has_default;
    }

    /**
     * @param bool $has_default
     */
    public function set_has_default(bool $has_default): void {
        $this->has_default = $has_default;
    }

    /**
     * @inheritDoc
     */
    public static function get_id(): string {
        return 'core_theme/mock_file';
    }

    /**
     * @inheritDoc
     */
    public function get_component(): string {
        return 'core_theme';
    }

    /**
     * @inheritDoc
     */
    public function get_area(): string {
        return 'mock';
    }

    /**
     * @inheritDoc
     */
    public function get_ui_key(): string {
        return 'mock_file';
    }

    /**
     * @inheritDoc
     */
    public function get_ui_category(): string {
        return 'mock';
    }

    /**
     * @inheritDoc
     */
    public function get_type(): \core\files\type\file_type {
        return $this->type;
    }
}