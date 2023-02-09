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
 * @package totara_comment
 */
namespace totara_comment\webapi;

use totara_core\identifier\component_area;
use weka_texteditor;

/**
 * A helper class that contains set of integration functions between totara_comment and editor weka.
 */
class editor_weka_helper {
    /**
     * Preventing this class from construction.
     * editor_weka_helper constructor.
     */
    private function __construct() {
    }

    /**
     * A helper function to mock the editor's configuration based on the given $identifier under
     * totara_comment-comment_area identifier. So that at the editor weka usage we can fetch for
     * 'totara_comment' for the actual component-area's configuration.
     *
     * @param component_area    $identifier     The component/area that is using the totara_comment plugin.
     * @param string            $comment_area   The comment's area of which the weka_texteditor are used for.
     * @param int               $context_id
     *
     * @return weka_texteditor
     * @deprecated since Totara 13.3
     */
    public static function create_mask_editor(component_area $identifier,
                                              string $comment_area, int $context_id): weka_texteditor {
        global $CFG;

        debugging(
            "The function \\totara_comment\\webapi\\editor_weka_helper::create_mask_editor had been deprecated " .
            "and no longer used. The behaviour of this function had also been changed. Please update all calls",
            DEBUG_DEVELOPER
        );
        require_once("{$CFG->dirroot}/lib/editor/weka/lib.php");

        // We want an editor of totara_comment with the area of comment.
        $editor = new weka_texteditor();
        $editor->set_context_id($context_id);

        return $editor;
    }
}