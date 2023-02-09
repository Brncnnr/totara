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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package theme_msteams
 */

defined('MOODLE_INTERNAL' || die());

/** @var theme_config $THEME */
$THEME->doctype = 'html5';
$THEME->name = 'msteams';
$THEME->sheets = ['custom'];
$THEME->editor_sheets = [];
// NOTE: please update theme\msteams\core_renderer::PARENT_THEME if you change the parent theme to something other than ventura.
$THEME->parents = ['ventura', 'legacy', 'base'];
$THEME->enable_dock = false;
$THEME->enable_hide = true;
$THEME->minify_css = false;
$THEME->hidefromselector = true;

$THEME->rendererfactory = 'theme_overridden_renderer_factory';

// The theme needs to be added to all Moodle layouts.
$THEME->layouts = array(
    // No custom layouts for this theme.
);
