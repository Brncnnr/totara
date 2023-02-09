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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package core_course
 */

namespace core_course\output;

use core_course\local\archive_progress_helper\output\context\confirmation_page;
use core_renderer;
use single_button;

/**
 * Course archive progress confirmation page renderer.
 *
 * Explain the common methods that will be passed through this renderer
 *
 * @method string header() header() Renders the page header
 * @see \core_renderer::header()
 * @method string heading() heading(string $text, int $level = 2, string $classes = null, string $id = null) Renders an hX header
 * @see \core_renderer::heading()
 * @method string footer() footer() Renders an hX header
 * @see \core_renderer::footer()
 */
final class archive_progress_confirmation_renderer extends core_renderer {

    /**
     * Renders the archive completion page.
     *
     * @param confirmation_page $page_properties
     *
     * @return string
     */
    public function page(confirmation_page $page_properties): string {
        $data = [
            'heading' => clean_string($page_properties->get_heading()),
            'confirmation_modal' => $this->generate_confirmation_modal($page_properties),
        ];

        return $this->render_from_template(
            'core_course/archive_progress',
            (object)$data
        );
    }

    /**
     * Generate confirmation modal.
     *
     * @param confirmation_page $page_properties
     *
     * @return string
     */
    private function generate_confirmation_modal(confirmation_page $page_properties): string {
        $continue_button = new single_button($page_properties->get_confirmation_url(), get_string('continue'), 'post', true);
        $cancel_button = new single_button($page_properties->get_cancel_url(), get_string('cancel'), 'get', false);

        return $this->confirm($page_properties->get_message(), $continue_button, $cancel_button);
    }
}
