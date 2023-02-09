<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\webapi;

use core\format;
use core\webapi\formatter\field\text_field_formatter;
use totara_webapi\graphql;
use totara_webapi\endpoint_type\factory as endpoint_type_factory;

class execution_context extends \core\webapi\execution_context {
    /** @var \stdClass */
    private $device;

    public function __construct(string $operationname, \stdClass $device) {
        parent::__construct(
            endpoint_type_factory::get_instance(graphql::TYPE_MOBILE),
            $operationname
        );
        $this->device = $device;
    }

    /**
     * Return device id used to access mobile API.
     * @return int|null
     */
    public function get_device_id() : ?int {
        if (isset($this->device->id)) {
            return $this->device->id;
        }
        return null;
    }

    /**
     * Format text to HTML and link files to pluginfile.php script if all options specified.
     *
     * @param string|null $text
     * @param string $format
     * @param array $options - includes 'context', 'component' and 'filearea' for pluginfile.php relinking
     * @return string
     */
    public function format_text(?string $text, $format = FORMAT_HTML, array $options = []) {
        global $CFG;

        $context = $options['context'] ?? \context_system::instance();

        $formatter = new text_field_formatter(format::FORMAT_HTML, $context);
        $formatter->set_text_format($format)
            ->set_additional_options($options);

        if (!empty($options['context']) && !empty($options['component']) && !empty($options['filearea'])) {
            $itemid = $options['itemid'] ?? null;
            $formatter->set_pluginfile_url_options($options['context'], $options['component'], $options['filearea'], $itemid);
        } else {
            $formatter->disabled_pluginfile_url_rewrite();
        }

        $text = $formatter->format($text);

        if ($text === null) {
            return null;
        }
        $text = str_replace($CFG->wwwroot . '/pluginfile.php', $CFG->wwwroot . '/totara/mobile/pluginfile.php', $text);
        return $text;
    }
}