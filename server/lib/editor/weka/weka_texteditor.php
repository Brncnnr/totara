<?php
/**
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
 * @author  Simon Chester <simon.chester@totaralearning.com>
 * @package editor_weka
 */

use core\editor\abstraction\context_aware_editor;
use core\json_editor\helper\document_helper;
use editor_weka\config\factory;
use editor_weka\extension\extension;
use editor_weka\variant;
use totara_core\identifier\component_area;
use totara_tui\output\component;

final class weka_texteditor extends texteditor implements context_aware_editor {
    /**
     * The context's id where this editor is used.
     * @var int
     */
    private $context_id;

    /**
     * @var bool
     */
    private $show_toolbar;

    /**
     * weka_texteditor constructor.
     * @param null $unused - This argument is no longer used as of Totara 13.3.
     */
    public function __construct($unused = null) {
        if ($unused !== null) {
            debugging(
                'The first argument to weka_texteditor::_construct() is no longer used, please review your code',
                DEBUG_DEVELOPER
            );
        }

        $this->context_id = null;
        $this->show_toolbar = true;
    }

    /**
     * This function had been deprecated, please use {@see weka_texteditor::set_context_id()} instead.
     *
     * @param int $value
     * @return void
     *
     * @deprecated since Totara 13.3
     */
    public function set_contextid(int $value): void {
        debugging(
            "The function \\weka_texteditor::set_contextid had been deprecated, " .
            "please use \\weka_texteditor::set_context_id instead",
            DEBUG_DEVELOPER
        );

        $this->set_context_id($value);
    }

    /**
     * This function had been deprecated, please use {@see weka_texteditor::get_context_id()} instead.
     *
     * @return int|null
     *
     * @deprecated since Totara 13.3
     */
    public function get_contextid(): ?int {
        debugging(
            "The function \\weka_texteditor::get_contextid had been deprecated, " .
            "please use \\weka_texteditor::get_context_id instead",
            DEBUG_DEVELOPER
        );

        return $this->get_context_id();
    }

    /**
     * @param int $context_id
     * @return void
     */
    public function set_context_id(int $context_id): void {
        $this->context_id = $context_id;
    }

    /**
     * @return int|null
     */
    public function get_context_id(): ?int {
        return $this->context_id;
    }

    /**
     * {@inheritdoc}
     */
    public function supported_by_browser() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get_supported_formats() {
        return [FORMAT_JSON_EDITOR => FORMAT_JSON_EDITOR];
    }

    /**
     * {@inheritdoc}
     */
    public function get_preferred_format() {
        return FORMAT_JSON_EDITOR;
    }

    /**
     * {@inheritdoc}
     */
    public function supports_repositories() {
        return true;
    }

    /**
     * This function will try to invoke {@see extension::create()}
     *
     * @param string|null $component
     * @param string|null $area
     *
     * @return extension[]
     * @deprecated since Totara 13.3
     */
    public function get_extensions(string $component = 'editor_weka', string $area = 'learn'): array {
        debugging(
            "The function \\weka_texteditor::get_extensions had been deprecated, " .
            "please use \\editor_weka\\variant::get_extensions instead",
            DEBUG_DEVELOPER
        );

        $variant_name = "{$component}-{$area}";

        $context_id = context_system::instance()->id;
        if (!empty($this->context_id)) {
            $context_id = $this->context_id;
        }

        $variant = variant::create($variant_name, $context_id);
        $variant->set_component_area(new component_area($component, $area));

        return $variant->get_extensions();
    }

    /**
     * @param string|null $component Deprecated since totara 13.3
     * @param string|null $area      Deprecated since totara 13.3
     *
     * @return bool
     */
    public function show_toolbar(?string $component = null, ?string $area = null): bool {
        if (!empty($component)) {
            debugging(
                "The parameter '\$component' had been deprecated and no longer used, please update all calls",
                DEBUG_DEVELOPER
            );
        }

        if (!empty($area)) {
            debugging(
                "The parameter '\$area' had been deprecated and no longer used, please update all calls"
            );
        }

        return $this->show_toolbar;
    }

    /**
     * @param bool $show
     * @return void
     */
    public function set_show_toolbar(bool $show): void {
        $this->show_toolbar = $show;
    }

    /**
     * @inheritDoc
     * @param string     $elementid
     * @param array|null $options
     * @param array|null $fpoptions
     *
     * @return void
     */
    public function use_editor($elementid, array $options = null, $fpoptions = null): void {
        global $PAGE;

        if (null === $this->context_id) {
            if (isset($options['context'])) {
                $context = $options['context'];
                $this->set_context_id($context->id);
            }
        }

        if (null === $options) {
            // Set options to empty array. So that it is safe to set props down this code.
            $options = [];
        }

        if (array_key_exists('show_toolbar', $options)) {
            $this->set_show_toolbar($options['show_toolbar']);
        }

        // Start finding the draft_item_id within file picker options.
        if (is_array($fpoptions)) {
            $draft_item_id = null;
            if (isset($fpoptions['image'])) {
                $image_option = $fpoptions['image'];
                $draft_item_id = $image_option->itemid;
            } else if (isset($fpoptions['media'])) {
                $media_option = $fpoptions['media'];
                $draft_item_id = $media_option->itemid;
            } else if (isset($fpoptions['link'])) {
                $link_option = $fpoptions['link'];
                $draft_item_id = $link_option->itemid;
            } else if (isset($fpoptions['subtitle'])) {
                $subtitle_option = $fpoptions['subtitle'];
                $draft_item_id = $subtitle_option->itemid;
            }

            if (!empty($draft_item_id)) {
                $options['item_id'] = $draft_item_id;
            }
        }

        $params = $this->prepare_editor_options($elementid, $options);
        $jscode = $this->get_js_import_code($params);

        $PAGE->requires->js_init_code($jscode);
    }

    /**
     * Allow editor to customise template and init itself in Totara forms.
     *
     * @param array $result
     * @param array $editoroptions
     * @param array $fpoptions
     * @param array $fptemplates
     *
     * @return void
     */
    public function totara_form_use_editor(&$result, array $editoroptions, array $fpoptions, array $fptemplates) {
        $this->set_text($result['text']);
        $this->use_editor($result['id'], $editoroptions, $fpoptions);
    }

    /**
     * Only returning files - without the directories.
     * Note that this function will return the actual area files given by $component and $area
     *
     * @param string $component
     * @param string $area
     * @param int    $item_id
     *
     * @return stored_file[]
     * @deprecated since Totara 13.3
     */
    public function get_files(string $component, string $area, int $item_id): array {
        global $CFG;

        debugging(
            "The function \\weka_texteditor::get_files is deprecated and no longer used, " .
            "pleas use \\file_storage::get_area_files instead",
            DEBUG_DEVELOPER
        );

        if (empty($this->context_id)) {
            // There is no chances to fetch the file without context.
            return [];
        }

        require_once("{$CFG->dirroot}/lib/filelib.php");
        $fs = get_file_storage();

        $files = $fs->get_area_files(
            $this->context_id,
            $component,
            $area,
            $item_id
        );

        if (empty($files)) {
            return [];
        }

        return array_filter(
            $files,
            function (stored_file $file): bool {
                return !$file->is_directory();
            }
        );
    }

    /**
     * Only returning files without the directories.
     * Note that this function will only return files that had moved to the draft area.
     *
     * @param int      $draft_item_id
     * @param int|null $user_id
     * @return stored_file[]
     *
     * @deprecated since Totara 13.3
     */
    public function get_draft_files(int $draft_item_id, ?int $user_id = null): array {
        global $USER, $CFG;
        debugging(
            "The function \\weka_texteditor::get_draft_files had been deprecated. " .
            "Please use \\file_storage::get_area_files instead",
            DEBUG_DEVELOPER
        );

        if (empty($user_id)) {
            $user_id = $USER->id;
        }

        require_once("{$CFG->dirroot}/lib/filelib.php");
        $fs = get_file_storage();

        // Most of the draft files are stored under the user's context.
        $user_context = context_user::instance($user_id);
        $files = $fs->get_area_files(
            $user_context->id,
            'user',
            'draft',
            $draft_item_id,
            "itemid, filepath, filename",
            false
        );

        return array_values($files);
    }

    /**
     * @param string     $elementid
     * @param array|null $options
     *
     * @return array
     */
    private function prepare_editor_options(string $elementid, ?array $options = null): array {
        $params = [
            'id' => (string) $elementid,
            'extensions' => [],

            // Always shows the toolbar.
            'showtoolbar' => true,
            'file_item_id' => $options['item_id'] ?? null,
            'context_id' => $this->context_id ?? context_system::instance()->id,
            'files' => [],
        ];

        $component = $options['component'] ?? 'editor_weka';
        $area = $options['area'] ?? 'learn';

        // Build up the extensions metadata for the editor. Note that the extension metadata has to match with the
        // type declared in the schema.graphqls for the editor's extension type.
        $variant_name = "{$component}-{$area}";
        $variant = variant::create($variant_name, $params['context_id']);
        $variant->set_component_area(new component_area($component, $area));

        $extensions = $variant->get_extensions();
        foreach ($extensions as $extension) {
            $opt = $extension->get_js_parameters();
            $json = null;
            if (!empty($opt)) {
                $json = json_encode($opt);

                if (JSON_ERROR_NONE !== json_last_error()) {
                    $message = json_last_error_msg();
                    debugging("Error when encoding the json: {$message}");

                    // Reset to null if there is any error.
                    $json = null;
                }
            }

            $params['extensions'][] = [
                'name' => $extension->get_extension_name(),
                'tuicomponent' => $extension->get_js_path(),

                // It is an json_encoded string, as same as the property declared for
                // editor's extension type in graphql
                'options' => $json,
            ];
        }

        return $params;
    }

    /**
     * @param array $params
     * @return string
     */
    private function get_js_import_code(array $params): string {
        $component = new component('editor_weka/pages/WekaIntegration', [
            'params' => $params,
        ]);
        $html_encoded = json_encode($component->out_html());
        $id_encoded = json_encode($params['id']);

        if (JSON_ERROR_NONE != json_last_error()) {
            throw new coding_exception("Cannot encode JSON parameters");
        }

        return /** @lang JavaScript */ "
            ;(function(){
                var textarea = document.getElementById({$id_encoded});
                textarea.style.display = 'none';
                var temp = document.createElement('div');
                temp.innerHTML = {$html_encoded};
                textarea.parentNode.insertBefore(temp.firstElementChild, textarea);
                document.dispatchEvent(new CustomEvent('nodes-updated', {
                    detail: { nodes: [temp.firstElementChild], }
                }));
            })();
        ";
    }

    /**
     * {@inheritdoc}
     */
    public function get_js_module(string $framework): ?string {
        return $framework === 'tui' ? 'editor_weka/interface' : null;
    }
}
