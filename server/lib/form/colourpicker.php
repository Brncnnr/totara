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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @package core_form
 */

require_once("HTML/QuickForm/text.php");

class MoodleQuickForm_colourpicker extends HTML_QuickForm_text implements templatable {
    use templatable_form_element {
        export_for_template as export_for_template_base;
    }

    /*
     * html for help button, if empty then no help
     *
     * @var string
     */
    public $_helpbutton = '';
    public $_hiddenLabel = false;

    public function __construct($elementname = null, $elementlabel = null, $attributes = null, $options = null) {
        parent::__construct($elementname, $elementlabel, $attributes);
        /* Pretend we are a 'static' MoodleForm element so that we get the core_form/element-static template where
           we can render our own markup via core_renderer::mform_element() in lib/outputrenderers.php.
           used in combination with 'use' statement above and export_for_template() method below. */
        $this->setType('static');
    }

    public function setHiddenLabel($hiddenLabel) {
        $this->_hiddenLabel = $hiddenLabel;
    }

    public function toHtml() {
        global $PAGE, $OUTPUT;
        $id = $this->getAttribute('id');
        $PAGE->requires->js_init_call('M.util.init_colour_picker', [$id]);
        $colour = $this->getValue();

        $content = $OUTPUT->render_from_template('core_form/colourpicker', [
            'name' => $this->getName(),
            'id' => $this->getAttribute('id'),
            'value' => $colour,
        ]);

        return $content;
    }

    /**
     * get html for help button
     *
     * @return  string html for help button
     */
    public function getHelpButton() {
        return $this->_helpbutton;
    }

    /**
     * @return string
     */
    public function getElementTemplateType() {
        if ($this->_flagFrozen) {
            return 'static';
        } else {
            return 'default';
        }
    }

    public function export_for_template(renderer_base $output) {
        $context = $this->export_for_template_base($output);
        $context['html'] = $this->toHtml();
        $context['staticlabel'] = false; // Not a static label!
        return $context;
    }
}
