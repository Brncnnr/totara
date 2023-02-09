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
 * @package editor_weka
 */
namespace editor_weka\extension\abstraction;

/**
 * This interface normally goes with any custom extension that the system does not
 * want it to appear everywhere with the core variant definition.
 *
 * If your extension is used for specific place and does not want it to be included in any other
 * places apart from your plugin's place then implement this extension and your extension will
 * be excluded by default.
 *
 * By default all the child extensions are set to be a generic extension. With this interface it will help
 * to blacklist the extension itself from the list of extensions provided from variant.
 */
interface specific_custom_extension {
}