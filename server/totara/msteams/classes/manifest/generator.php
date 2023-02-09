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
 * @package totara_msteams
 */

namespace totara_msteams\manifest;

use context_system;
use Exception;
use lang_string;
use moodle_url;
use stdClass;
use stored_file;
use totara_core\util\language;
use totara_msteams\auth_helper;
use totara_msteams\hook\bot_command_list_hook;
use totara_msteams\page_helper;

defined('MOODLE_INTERNAL') || die;

/**
 * A class to generate a manifest.
 * This class does not verify admin settings.
 */
final class generator {
    private const MANIFEST_JSON = 'manifest.json';
    private const COLOR_PNG = 'color.png';
    private const OUTLINE_PNG = 'outline.png';

    /**
     * @var string
     */
    private $lang;

    /**
     * Constructor.
     *
     * @param string|null $lang
     */
    public function __construct(?string $lang = null) {
        global $CFG;
        $this->lang = $lang ?? $CFG->lang;
    }

    /**
     * Generate a manifest file and related files.
     * @param output $output
     */
    public function generate_files(output $output): void {
        $this->generate_manifest_json($output);
        $this->generate_images($output);
    }

    /**
     * Return the localised language string in the totara_msteams.
     *
     * @param string $identifier
     * @param stdClass|array $a
     * @param string|null $lang
     * @return string
     */
    private function get_string(string $identifier, $a = null, string $lang = null): string {
        return (new lang_string($identifier, 'totara_msteams', $a, $lang ?? $this->lang))->out();
    }

    /**
     * Convert to the localised string.
     *
     * @param string|lang_string $string
     * @return string
     */
    private function to_localised_string($string): string {
        if ($string instanceof lang_string) {
            return $string->out($this->lang);
        } else {
            return $string;
        }
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function to_json($value): string {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Throw an exception.
     *
     * @param string $filename
     * @throws Exception
     */
    private function handle_error(string $filename): void {
        throw new Exception("Can not write to '{$filename}'");
    }

    /**
     * @param output $output
     */
    private function generate_manifest_json(output $output): void {
        $manifest = $this->create_manifest_template();
        $this->add_single_sign_on($manifest);
        $this->add_dynamic_tabs($manifest);
        $this->add_static_tabs($manifest);
        $this->add_bot_feature($manifest);
        $this->add_messaging_extension_feature($manifest);
        $this->add_developer_information($manifest);
        $this->add_languages($manifest, $output);
        $json = self::to_json($manifest);
        if (!$output->write(self::MANIFEST_JSON, $json)) {
            $this->handle_error(self::MANIFEST_JSON);
        }
    }

    /**
     * Create the manifest basis.
     *
     * @return array
     */
    private function create_manifest_template(): array {
        global $CFG;

        $fullqualdomainname = parse_url($CFG->wwwroot, PHP_URL_HOST);
        $manifestid = get_config('totara_msteams', 'manifest_app_id');

        return [
            '$schema' => 'https://developer.microsoft.com/en-us/json-schemas/teams/v1.5/MicrosoftTeams.schema.json',
            'manifestVersion' => '1.5',
            'version' => get_config('totara_msteams', 'manifest_app_version'),
            'id' => $manifestid,
            'packageName' => get_config('totara_msteams', 'manifest_app_package_name'),
            'developer' => [
                // To be filled.
            ],
            'icons' => [
                'color' => self::COLOR_PNG,
                'outline' => self::OUTLINE_PNG,
            ],
            'name' => [
                'short' => get_config('totara_msteams', 'manifest_app_name'),
                'full' => get_config('totara_msteams', 'manifest_app_fullname'),
            ],
            'description' => [
                'short' => get_config('totara_msteams', 'manifest_app_description'),
                'full' => get_config('totara_msteams', 'manifest_app_fulldescription'),
            ],
            'accentColor' => get_config('totara_msteams', 'manifest_app_accent_color') ?: $this->get_string('settings:manifest_accent_colour_default'),
            'permissions' => [
                'identity',
                'messageTeamMembers',
            ],
            'validDomains' => [
                $fullqualdomainname,
                'token.botframework.com',
            ],
        ];
    }

    /**
     * Add the single sign-on part
     *
     * @param array $manifest
     */
    private function add_single_sign_on(array &$manifest): void {
        if (auth_helper::get_oauth2_issuer(false) !== null) {
            $manifest['webApplicationInfo'] = [
                'id' => get_config('totara_msteams', 'sso_app_id'),
                'resource' => get_config('totara_msteams', 'sso_scope')
            ];
        }
    }

    /**
     * Add dynamic tabs.
     *
     * @param array $manifest
     */
    private function add_dynamic_tabs(array &$manifest): void {
        $manifest['configurableTabs'] = [[
            'configurationUrl' => (new moodle_url('/totara/msteams/tabs/config.php'))->out(false),
            'canUpdateConfiguration' => true,
            'scopes' => [
                'team',
            ]],
        ];
    }

    /**
     * Add static tabs.
     *
     * @param array $manifest
     */
    private function add_static_tabs(array &$manifest): void {
        $tabs_enabled = page_helper::get_available_tabs();
        $manifest['staticTabs'] = array_map(function($id, $data) {
            return [
                'entityId' => $id,
                'name' => $this->to_localised_string($data['name']),
                'contentUrl' => (new moodle_url($data['url']))->out(false),
                'websiteUrl' => (new moodle_url($data['externalUrl'] ?? $data['redirectUrl']))->out(false),
                'scopes' => ['personal']
            ];
        }, array_keys($tabs_enabled), $tabs_enabled);
    }

    /**
     * Add the bot feature if enabled.
     *
     * @param array $manifest
     */
    private function add_bot_feature(array &$manifest): void {
        $botfeatureenabled = get_config('totara_msteams', 'bot_feature_enabled');
        if ($botfeatureenabled) {
            $botappid = get_config('totara_msteams', 'bot_app_id');
            $bot = [
                'botId' => $botappid,
                'needsChannelSelector' => false,
                'isNotificationOnly' => false,
                'scopes' => [
                    'personal',
                ],
            ];
            $hook = new bot_command_list_hook();
            $hook->execute();
            $commands = array_map(function ($data) {
                return [
                    'title' => $this->to_localised_string($data[0]),
                    'description' => $this->to_localised_string($data[1])
                ];
            }, $hook->commandlist);
            if (!empty($commands)) {
                $bot['commandLists'] = [[
                    'scopes' => [
                        'personal',
                    ],
                    'commands' => $commands,
                ]];
            }
            $manifest['bots'] = [$bot];
        }
    }

    /**
     * Add the messaging extension feature if enabled.
     *
     * @param array $manifest
     */
    private function add_messaging_extension_feature(array &$manifest): void {
        $messagingextensionenabled = get_config('totara_msteams', 'messaging_extension_enabled');
        if ($messagingextensionenabled) {
            $botappid = get_config('totara_msteams', 'bot_app_id');
            $manifest['composeExtensions'] = [[
                'botId' => $botappid,
                'canUpdateConfiguration' => false,
                'commands' => [[
                    'id' => 'searchCommand',
                    'context' => ['compose', 'commandBox'],
                    'title' => $this->get_string('botfw:mx_command_search'),
                    'description' => $this->get_string('botfw:mx_command_search_desc'),
                    'type' => 'query',
                    'initialRun' => true,
                    'parameters' => [[
                        'name' => 'search',
                        'title' => $this->get_string('botfw:mx_command_search_term'),
                        'description' => $this->get_string('botfw:mx_command_search_term_desc'),
                        'inputType' => 'text'
                    ]]
                ]]
            ]];
        }
    }

    /**
     * Add developer's information.
     *
     * @param array $manifest
     */
    private function add_developer_information(array &$manifest): void {
        global $CFG;
        $url_or_default = function ($url) use ($CFG) {
            return (string)$url !== '' ? (string)$url : $CFG->wwwroot;
        };
        $manifest['developer'] = [
            'name' => $CFG->publishername,
            'websiteUrl' => $url_or_default($CFG->publisherwebsite),
            'privacyUrl' => $url_or_default($CFG->privacypolicy),
            'termsOfUseUrl' => $url_or_default($CFG->termsofuse),
        ];
        $mpnid = (string)get_config('totara_msteams', 'publisher_mpnid');
        if ($mpnid !== '') {
            $manifest['developer']['mpnId'] = $mpnid;
        }
    }

    /**
     * Add localised strings.
     *
     * @param array $manifest
     * @param output $output
     */
    private function add_languages(array &$manifest, output $output): void {
        $installedlangs = get_string_manager()->get_list_of_translations(true);
        $currentlang = $this->lang;
        // Do not add the multi-language section if English is the only installed language pack.
        if (count($installedlangs) === 1 && isset($installedlangs[$currentlang])) {
            return;
        }

        unset($installedlangs[$currentlang]);
        $languages = array_keys($installedlangs);
        $langs = $this->generate_languages($languages, $output);

        $manifest['localizationInfo'] = [
            'defaultLanguageTag' => language::convert_to_ietf_format($currentlang),
            'additionalLanguages' => array_map(function ($lang, $json) {
                return [
                    'languageTag' => language::convert_to_ietf_format($lang),
                    'file' => $json,
                ];
            }, array_keys($langs), $langs)
        ];
    }

    /**
     * Generate a language JSON.
     *
     * @param array $languages
     * @param output $output
     * @return array of [lang_code => filename]
     */
    private function generate_languages(array $languages, output $output): array {
        $tabs_enabled = page_helper::get_available_tabs();
        $result = [];
        foreach ($languages as $lang) {
            $filename = $lang.'.json';
            $content = [
                '$schema' => 'https://developer.microsoft.com/json-schemas/teams/v1.5/MicrosoftTeams.Localization.schema.json'
            ];
            // Static tabs.
            $i = 0;
            foreach ($tabs_enabled as $data) {
                $name = $data['name'];
                if ($name instanceof lang_string) {
                    $content["staticTabs[{$i}].name"] = $name->out($lang);
                }
                $i++;
            }

            // Bot command names.
            $botfeatureenabled = get_config('totara_msteams', 'bot_feature_enabled');
            if ($botfeatureenabled) {
                $hook = new bot_command_list_hook();
                $hook->execute();
                $i = 0;
                foreach ($hook->commandlist as [$command, $help]) {
                    if ($command instanceof lang_string) {
                        $content["bots[0].commandLists[0].commands[{$i}].title"] = $command->out($lang);
                    }
                    if ($help instanceof lang_string) {
                        $content["bots[0].commandLists[0].commands[{$i}].description"] = $help->out($lang);
                    }
                    $i++;
                }
            }

            // Messaging extension.
            $messagingextensionenabled = get_config('totara_msteams', 'messaging_extension_enabled');
            if ($messagingextensionenabled) {
                $content["composeExtensions[0].commands[0].title"] = $this->get_string('botfw:mx_command_search', null, $lang);
                $content["composeExtensions[0].commands[0].description"] = $this->get_string('botfw:mx_command_search_desc', null, $lang);
                $content["composeExtensions[0].commands[0].parameters[0].title"] = $this->get_string('botfw:mx_command_search_term', null, $lang);
                $content["composeExtensions[0].commands[0].parameters[0].description"] = $this->get_string('botfw:mx_command_search_term_desc', null, $lang);
            }

            if (!$output->write($filename, self::to_json($content))) {
                $this->handle_error($filename);
            }
            $result[$lang] = $filename;
        }
        return $result;
    }

    /**
     * Copy images.
     *
     * @param output $output
     */
    private function generate_images(output $output): void {
        global $CFG;
        $fatofilename = [
            'manifest_app_icon_color' => self::COLOR_PNG,
            'manifest_app_icon_outline' => self::OUTLINE_PNG,
        ];
        $fs = get_file_storage();
        foreach ($fatofilename as $filearea => $filename) {
            $files = $fs->get_area_files(context_system::instance()->id, 'totara_msteams', $filearea, 0, 'sortorder,filepath,filename', false);
            if (!empty($files)) {
                /** @var stored_file $file */
                $file = reset($files);
                if (!$output->write($filename, $file->get_content())) {
                    $this->handle_error($filename);
                }
            } else if (!$output->write($filename, file_get_contents($CFG->dirroot . '/totara/msteams/pix/' . $filename))) {
                $this->handle_error($filename);
            }
        }
    }
}
