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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\language;

use core_component;
use Exception;

/**
 * Helper class to generate a local language string file.
 */
abstract class local_lang_generator {
    /** @var string */
    private $lang;

    /**
     * Get a component name.
     * @return string
     */
    abstract protected function get_component(): string;

    /**
     * Get a custom language substitution strategy.
     * @return substitutor
     */
    abstract protected function get_substitutor(): substitutor;

    /**
     * Get custom language strings. These strings are not substituted.
     * @return string[]
     */
    abstract protected function get_strings(): array;

    /**
     * Constructor.
     * @param string $lang
     */
    public function __construct(string $lang = '') {
        $this->lang = $lang ?: current_language();
    }

    /**
     * @return string
     */
    private function get_local_path(): string {
        global $CFG;
        [$type, $name] = core_component::normalize_component($this->get_component());
        // Taken from string_manager_standard::load_component_strings
        if ($type === 'mod') {
            // Bloody mod hack.
            $file = $name;
        } else if ($type == 'rb_source') {
            // Hack for rb_sources.
            $file = 'rb_source_' . $name;
        } else {
            $file = $type . '_' . $name;
        }
        return "{$CFG->langlocalroot}/{$this->lang}_local/{$file}.php";
    }

    /**
     * @param string[] $string
     * @return string[]
     */
    private function generate_strings(array $string): array {
        $new_string = [];
        $new_string = array_merge($new_string, $this->get_substituted_strings($string));
        $new_string = array_merge($new_string, $this->get_strings());
        ksort($new_string);
        return $new_string;
    }

    /**
     * @param string[] $string
     * @return string[]
     */
    private function get_substituted_strings(array $string): array {
        $new_string = [];
        $substitutor = $this->get_substitutor();
        $new_string = [];
        foreach ($string as $key => $value) {
            $new_value = $substitutor->substitute_string($value);
            if ($new_value !== $value) {
                $new_string[$key] = $new_value;
            }
        }
        return $new_string;
    }

    /**
     * @param string[] $string
     * @return string
     */
    private function serialise_strings(array $string): string {
        [$type, $name] = core_component::normalize_component($this->get_component());
        $output = "<?php\n";
        $output .= "/**\n";
        $output .= " * @package    {$type}\n";
        $output .= " * @subpackage {$name}\n";
        $output .= " * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later\n";
        $output .= " */\n";
        foreach ($string as $key => $value) {
            $output .= '$string[' . var_export($key, true) . '] = ' . var_export($value, true) . ";\n";
        }
        return $output;
    }

    /**
     * Generate the contents of a langage file.
     * @return string
     */
    final public function generate_contents(): string {
        $sm = get_string_manager();
        $string = $sm->load_component_strings($this->get_component(), $this->lang, true, true);
        $new_string = $this->generate_strings($string);
        return $this->serialise_strings($new_string);
    }

    /**
     * Generate a langage file.
     * This function overwrites a local language file.
     * Admin has to 'Save changes to the language pack' in the language customisation page
     * if there is already some customisation.
     */
    final public function generate_file(): void {
        global $CFG;
        $contents = $this->generate_contents();
        $path = $this->get_local_path();
        $dir = dirname($path);
        @mkdir($dir, $CFG->directorypermissions, true);
        if (!file_put_contents($path, $contents)) {
            throw new Exception("Cannot write to a language file '{$path}'");
        }
    }
}
