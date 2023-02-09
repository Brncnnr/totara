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

defined('MOODLE_INTERNAL') || die;

use core\oauth2\api;
use core\oauth2\issuer;
use totara_msteams\auth_helper;
use totara_msteams\manifest\generator;
use totara_msteams\manifest\outputs\memory_output;
use totara_msteams\manifest\outputs\zip_output;
use totara_msteams\page_helper;

/**
 * Test manifest\generator class.
 */
class manifest_generator_testcase extends advanced_testcase {
    use \core_phpunit\language_pack_faker_trait;
    /** @var string */
    private $manifestid;

    /** @var string */
    private $ssoid;

    /** @var string */
    private $botid;

    /** @var integer */
    private $issuerid;

    /** @var generator */
    private $generator;

    /** @var memory_output */
    private $output;

    public function setUp(): void {
        global $CFG;
        $this->setAdminUser();
        $this->manifestid = \core\uuid::generate();
        $this->ssoid = \core\uuid::generate();
        $this->botid = \core\uuid::generate();
        self::enable_oauth2_plugin(true);
        $this->issuerid = self::add_microsoft_oauth2_issuer($this->ssoid);
        self::add_microsoft_oauth2_endpoints($this->issuerid);
        set_config('manifest_app_id', $this->manifestid, 'totara_msteams');
        set_config('manifest_app_version', '1.0.0', 'totara_msteams');
        set_config('manifest_app_package_name', 'com.example.totara.msteams.test', 'totara_msteams');
        set_config('manifest_app_name', 'ToTaRa', 'totara_msteams');
        set_config('manifest_app_fullname', 'Test Totara App', 'totara_msteams');
        set_config('manifest_app_description', 'Test short description', 'totara_msteams');
        set_config('manifest_app_fulldescription', 'Test long description', 'totara_msteams');
        set_config('manifest_app_accent_color', '#123456', 'totara_msteams');
        set_config('sso_app_id', $this->ssoid, 'totara_msteams');
        set_config('sso_scope', 'api://example.com/'.$this->ssoid, 'totara_msteams');
        set_config('bot_app_id', $this->botid, 'totara_msteams');
        $CFG->wwwroot = 'https://example.com/totara';
        $CFG->publishername = 'Totara';
        $this->generator = new generator();
        $this->output = new memory_output();
    }

    public function tearDown(): void {
        $this->manifestid = null;
        $this->ssoid = null;
        $this->botid = null;
        $this->issuerid = null;
        $this->generator = null;
        $this->output = null;
    }

    /**
     * Add a dummy Microsoft OAuth2 service.
     *
     * @param string $clientid
     * @return integer
     */
    private static function add_microsoft_oauth2_issuer(string $clientid): int {
        return api::init_standard_issuer('microsoft')
            ->set('clientid', $clientid)
            ->set('clientsecret', 'kIa0rAKoUt0u!')
            ->set('enabled', 1)
            ->create()
            ->get('id');
    }

    /**
     * Add Microsoft end points for the dummy OAuth2 service.
     *
     * @param integer $issuerid The issuer ID returned by add_microsoft_oauth2_issuer()
     */
    private static function add_microsoft_oauth2_endpoints(int $issuerid): void {
        api::create_endpoints_for_standard_issuer('microsoft', new issuer($issuerid));
    }

    /**
     * Enable or disable the OAuth2 authentication plugin.
     *
     * @param boolean $enable
     */
    private static function enable_oauth2_plugin(bool $enable): void {
        set_config('auth', $enable ? 'oauth2' : '');
        core_plugin_manager::reset_caches();
    }

    /**
     * Upload a file.
     *
     * @param string $path
     * @param string $filename
     * @param string $filearea
     */
    private function upload(string $path, string $filename, string $filearea) {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/files/externallib.php');

        $usercontextid = context_user::instance($USER->id)->id;
        $content = base64_encode(file_get_contents($path));
        $draftfile = core_files_external::upload(
            $usercontextid, 'user', 'draft', 0, '/',
            $filename, $content, null, null);
        $systemcontextid = context_system::instance()->id;
        file_save_draft_area_files($draftfile['itemid'], $systemcontextid, 'totara_msteams', $filearea, 0);
    }

    /**
     * Install a fake language pack 'xo_ox'.
     */
    private function create_fake_language_pack() {
        $this->add_fake_language_pack('xo_ox', [
            'totara_msteams' => [
                'botfw:cmd_help' => 'pleh',
                'botfw:cmd_signin' => 'ningis',
                'botfw:cmd_signout' => 'tuongis',
                'botfw:help_help' => 'pleh yalpsid.',
                'botfw:help_signin' => 'ni ngis.',
                'botfw:help_signout' => 'tuo ngis.',
                'botfw:mx_command_search' => 'eugolatac hcraes',
                'botfw:mx_command_search_desc' => 'eugolatac hcraes',
                'botfw:mx_command_search_term' => 'yreuq hcraes',
                'botfw:mx_command_search_term_desc' => 'smret hcraes retne',
                'tab:catalog' => 'gninrael dnif',
                'tab:library' => 'yrarbil',
                'tab:mylearning' => 'gninrael tnerruc',
            ]
        ]);
    }

    /**
     * Assert parameters that are set in the setUp function.
     *
     * @param array $json
     */
    private function assert_json_common(array $json) {
        $this->assertSame('https://developer.microsoft.com/en-us/json-schemas/teams/v1.5/MicrosoftTeams.schema.json', $json['$schema']);
        $this->assertSame('1.5', $json['manifestVersion']);
        $this->assertSame('1.0.0', $json['version']);
        $this->assertSame($this->manifestid, $json['id']);
        $this->assertSame('com.example.totara.msteams.test', $json['packageName']);
        $this->assertSame('color.png', $json['icons']['color']);
        $this->assertSame('outline.png', $json['icons']['outline']);
        $this->assertSame('ToTaRa', $json['name']['short']);
        $this->assertSame('Test Totara App', $json['name']['full']);
        $this->assertSame('Test short description', $json['description']['short']);
        $this->assertSame('Test long description', $json['description']['full']);
        $this->assertSame('#123456', $json['accentColor']);
        $this->assertSame('example.com', $json['validDomains'][0]);
        $this->assertSame('Totara', $json['developer']['name']);
        $this->assertSame('https://example.com/totara', $json['developer']['websiteUrl']);
        $this->assertSame('https://example.com/totara', $json['developer']['privacyUrl']);
        $this->assertSame('https://example.com/totara', $json['developer']['termsOfUseUrl']);
        $this->assertArrayNotHasKey('mpnId', $json['developer']);
        // Configurable tabs are always provided.
        $this->assertCount(1, $json['configurableTabs']);
        // TODO: Need to add a test scenario to confirm the engage-only subscription i.e. flavour.
        // Find learning and Library are always provided anyway.
        $this->assertGreaterThanOrEqual(2, $json['staticTabs']);
    }

    /**
     * Assert both images are default.
     *
     * @param string[] $files
     */
    private function assert_default_images($files) {
        // Make sure both PNG files are copied from the pix folder.
        $this->assertSame('7b4b56af51e59d272abdf6d2d2f328f2', md5($files['color.png']));
        $this->assertSame('3d5c8a8b7ee458b4597b2e726edf3f29', md5($files['outline.png']));
    }

    public function test_generate_basic() {
        $this->generator->generate_files($this->output);
        $files = $this->output->get_files();
        $this->assertCount(3, $files);
        $this->assert_default_images($files);

        $json = json_decode($files['manifest.json'], true);
        $this->assert_json_common($json);
        $this->assertArrayNotHasKey('webApplicationInfo', $json);
        $this->assertArrayNotHasKey('bots', $json);
        $this->assertArrayNotHasKey('composeExtensions', $json);
        $this->assertArrayNotHasKey('localizationInfo', $json);
    }

    public function test_generate_sso() {
        set_config('oauth2_issuer', $this->issuerid, 'totara_msteams');
        $this->assertNotNull(auth_helper::get_oauth2_issuer(true));

        $this->generator->generate_files($this->output);
        $files = $this->output->get_files();
        $this->assertCount(3, $files);
        $this->assert_default_images($files);

        $json = json_decode($files['manifest.json'], true);
        $this->assert_json_common($json);
        $this->assertArrayNotHasKey('bots', $json);
        $this->assertArrayNotHasKey('composeExtensions', $json);
        $this->assertArrayNotHasKey('localizationInfo', $json);
        $this->assertSame($this->ssoid, $json['webApplicationInfo']['id']);
        $this->assertSame('api://example.com/'.$this->ssoid, $json['webApplicationInfo']['resource']);
    }

    public function test_generate_bot() {
        set_config('bot_feature_enabled', 1, 'totara_msteams');

        $this->generator->generate_files($this->output);
        $files = $this->output->get_files();
        $this->assertCount(3, $files);
        $this->assert_default_images($files);

        $json = json_decode($files['manifest.json'], true);
        $this->assert_json_common($json);
        $this->assertArrayNotHasKey('webApplicationInfo', $json);
        $this->assertArrayNotHasKey('composeExtensions', $json);
        $this->assertArrayNotHasKey('localizationInfo', $json);

        $this->assertCount(1, $json['bots']);
        $this->assertSame($this->botid, $json['bots'][0]['botId']);
        $this->assertCount(1, $json['bots'][0]['commandLists']);
        $this->assertCount(3, $json['bots'][0]['commandLists'][0]['commands']);
    }

    public function test_generate_messaging_extension() {
        set_config('messaging_extension_enabled', 1, 'totara_msteams');

        $this->generator->generate_files($this->output);
        $files = $this->output->get_files();
        $this->assertCount(3, $files);
        $this->assert_default_images($files);

        $json = json_decode($files['manifest.json'], true);
        $this->assert_json_common($json);
        $this->assertArrayNotHasKey('webApplicationInfo', $json);
        $this->assertArrayNotHasKey('bots', $json);
        $this->assertArrayNotHasKey('localizationInfo', $json);

        $this->assertCount(1, $json['composeExtensions']);
        $this->assertSame($this->botid, $json['composeExtensions'][0]['botId']);
        $this->assertCount(1, $json['composeExtensions'][0]['commands']);
        $this->assertSame(true, $json['composeExtensions'][0]['commands'][0]['initialRun']);
        $this->assertCount(1, $json['composeExtensions'][0]['commands'][0]['parameters']);
    }

    public function test_generate_developer_info() {
        global $CFG;
        $CFG->publishername = 'Totara Learning Solutions';
        $CFG->publisherwebsite = 'https://totaralearning.com';
        $CFG->privacypolicy = 'https://example.com/privacy-policy/';
        $CFG->termsofuse = 'https://example.com/terms-of-use/';
        set_config('publisher_mpnid', 'ToTaRa!!!!', 'totara_msteams');

        $this->generator->generate_files($this->output);
        $files = $this->output->get_files();
        $this->assertCount(3, $files);
        $this->assert_default_images($files);

        $json = json_decode($files['manifest.json'], true);
        $this->assertSame('Totara Learning Solutions', $json['developer']['name']);
        $this->assertSame('https://totaralearning.com', $json['developer']['websiteUrl']);
        $this->assertSame('https://example.com/privacy-policy/', $json['developer']['privacyUrl']);
        $this->assertSame('https://example.com/terms-of-use/', $json['developer']['termsOfUseUrl']);
        $this->assertSame('ToTaRa!!!!', $json['developer']['mpnId']);
    }

    public function data_custom_images(): array {
        return [
            [null, null, '7b4b56af51e59d272abdf6d2d2f328f2', '3d5c8a8b7ee458b4597b2e726edf3f29'],
            ['logo1.png', null, 'cf546d92e63c56eaae521c73996b3e7f', '3d5c8a8b7ee458b4597b2e726edf3f29'],
            [null, 'logo2.png', '7b4b56af51e59d272abdf6d2d2f328f2', 'f0da7489fbdba47ce7f73e15753d7102'],
            ['logo1.png', 'logo2.png', 'cf546d92e63c56eaae521c73996b3e7f', 'f0da7489fbdba47ce7f73e15753d7102'],
        ];
    }

    /**
     * @param string|null $colorpng file data of color.png
     * @param string|null $outlinepng file data of outline.png
     * @param string $colorexpected md5 hash of color.png
     * @param string $outlineexpected md5 hash of outline.png
     * @dataProvider data_custom_images
     */
    public function test_generate_custom_images(?string $colorpng, ?string $outlinepng, string $colorexpected, string $outlineexpected) {
        if ($colorpng !== null) {
            $this->upload(__DIR__.'/fixtures/'.$colorpng, $colorpng, 'manifest_app_icon_color');
        }
        if ($outlinepng !== null) {
            $this->upload(__DIR__.'/fixtures/'.$outlinepng, $outlinepng, 'manifest_app_icon_outline');
        }

        $this->generator->generate_files($this->output);
        $files = $this->output->get_files();
        $this->assertCount(3, $files);
        $this->assertSame($colorexpected, md5($files['color.png']));
        $this->assertSame($outlineexpected, md5($files['outline.png']));
    }

    public function data_languages(): array {
        return [
            ['en', 'xo_ox', 'en', 'xo-OX'],
            ['xo_ox', 'en', 'xo-OX', 'en'],
        ];
    }

    /**
     * @param string $primary primary language - en / xo_ox
     * @param string $secondary secondary language - xo_ox / en
     * @param string $primary_tag primary language - en / xo-OX
     * @param string $secondary_tag secondary language - xo-OX / en
     * @dataProvider data_languages
     */
    public function test_generate_localisation(string $primary, string $secondary, string $primary_tag, string $secondary_tag) {
        $this->create_fake_language_pack();

        set_config('bot_feature_enabled', 1, 'totara_msteams');
        set_config('messaging_extension_enabled', 1, 'totara_msteams');

        $this->generator = new generator($primary);
        $this->generator->generate_files($this->output);
        $files = $this->output->get_files();
        $this->assertCount(4, $files);
        $this->assert_default_images($files);

        $json = json_decode($files['manifest.json'], true);
        $this->assert_json_common($json);

        $this->assertSame($primary_tag, $json['localizationInfo']['defaultLanguageTag']);
        $this->assertCount(1, $json['localizationInfo']['additionalLanguages']);
        $this->assertSame($secondary_tag, $json['localizationInfo']['additionalLanguages'][0]['languageTag']);
        $this->assertSame("{$secondary}.json", $json['localizationInfo']['additionalLanguages'][0]['file']);

        $locjson = json_decode($files["{$secondary}.json"], true);
        $this->assertSame('https://developer.microsoft.com/json-schemas/teams/v1.5/MicrosoftTeams.Localization.schema.json', $locjson['$schema']);
        $tabs = page_helper::get_available_tabs();
        $i = 0;
        foreach ($tabs as $tab) {
            $this->assertSame($tab['name']->out($primary), $json['staticTabs'][$i]['name']);
            $this->assertSame($tab['name']->out($secondary), $locjson["staticTabs[{$i}].name"]);
            $i++;
        }

        $strings = [
            ['en' => get_string('botfw:cmd_help', 'totara_msteams'), 'xo_ox' => 'pleh'],
            ['en' => get_string('botfw:help_help', 'totara_msteams'), 'xo_ox' => 'pleh yalpsid.'],
            ['en' => get_string('botfw:cmd_signin', 'totara_msteams'), 'xo_ox' => 'ningis'],
            ['en' => get_string('botfw:help_signin', 'totara_msteams'), 'xo_ox' => 'ni ngis.'],
            ['en' => get_string('botfw:cmd_signout', 'totara_msteams'), 'xo_ox' => 'tuongis'],
            ['en' => get_string('botfw:help_signout', 'totara_msteams'), 'xo_ox' => 'tuo ngis.'],
            ['en' => get_string('botfw:mx_command_search', 'totara_msteams'), 'xo_ox' => 'eugolatac hcraes'],
            ['en' => get_string('botfw:mx_command_search_desc', 'totara_msteams'), 'xo_ox' => 'eugolatac hcraes'],
            ['en' => get_string('botfw:mx_command_search_term', 'totara_msteams'), 'xo_ox' => 'yreuq hcraes'],
            ['en' => get_string('botfw:mx_command_search_term_desc', 'totara_msteams'), 'xo_ox' => 'smret hcraes retne'],
        ];

        $this->assertSame($strings[0][$primary], $json['bots'][0]['commandLists'][0]['commands'][0]['title']);
        $this->assertSame($strings[0][$secondary], $locjson['bots[0].commandLists[0].commands[0].title']);
        $this->assertSame($strings[1][$primary], $json['bots'][0]['commandLists'][0]['commands'][0]['description']);
        $this->assertSame($strings[1][$secondary], $locjson['bots[0].commandLists[0].commands[0].description']);
        $this->assertSame($strings[2][$primary], $json['bots'][0]['commandLists'][0]['commands'][1]['title']);
        $this->assertSame($strings[2][$secondary], $locjson['bots[0].commandLists[0].commands[1].title']);
        $this->assertSame($strings[3][$primary], $json['bots'][0]['commandLists'][0]['commands'][1]['description']);
        $this->assertSame($strings[3][$secondary], $locjson['bots[0].commandLists[0].commands[1].description']);
        $this->assertSame($strings[4][$primary], $json['bots'][0]['commandLists'][0]['commands'][2]['title']);
        $this->assertSame($strings[4][$secondary], $locjson['bots[0].commandLists[0].commands[2].title']);
        $this->assertSame($strings[5][$primary], $json['bots'][0]['commandLists'][0]['commands'][2]['description']);
        $this->assertSame($strings[5][$secondary], $locjson['bots[0].commandLists[0].commands[2].description']);
        $this->assertSame($strings[6][$primary], $json['composeExtensions'][0]['commands'][0]['title']);
        $this->assertSame($strings[6][$secondary], $locjson['composeExtensions[0].commands[0].title']);
        $this->assertSame($strings[7][$primary], $json['composeExtensions'][0]['commands'][0]['description']);
        $this->assertSame($strings[7][$secondary], $locjson['composeExtensions[0].commands[0].description']);
        $this->assertSame($strings[8][$primary], $json['composeExtensions'][0]['commands'][0]['parameters'][0]['title']);
        $this->assertSame($strings[8][$secondary], $locjson['composeExtensions[0].commands[0].parameters[0].title']);
        $this->assertSame($strings[9][$primary], $json['composeExtensions'][0]['commands'][0]['parameters'][0]['description']);
        $this->assertSame($strings[9][$secondary], $locjson['composeExtensions[0].commands[0].parameters[0].description']);
    }

    public function test_zip_output() {
        global $CFG;
        $zipfilepath = $CFG->dataroot . '/temp/mfgenzop.zip';
        @unlink($zipfilepath);
        $output = new zip_output();
        $this->assertTrue($output->open($zipfilepath));
        $this->generator->generate_files($output);
        $output->close();

        $zip = new ZipArchive();
        $zip->open($zipfilepath);
        $this->assertEquals(3, $zip->count());
        $manifest = $zip->getFromName('manifest.json');
        $this->assertNotFalse($manifest);
        $json = json_decode($manifest, true);
        $this->assert_json_common($json);
        $colorpng = $zip->getFromName('color.png');
        $this->assertNotFalse($colorpng);
        $outlinepng = $zip->getFromName('outline.png');
        $this->assertNotFalse($outlinepng);
        $this->assert_default_images(['color.png' => $colorpng, 'outline.png' => $outlinepng]);
        $zip->close();
    }
}
