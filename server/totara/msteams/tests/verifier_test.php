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

use core\oauth2\api;
use core\oauth2\issuer;
use totara_msteams\check\checkable;
use totara_msteams\check\checks\admin_catalog;
use totara_msteams\check\checks\admin_frame;
use totara_msteams\check\checks\admin_gridimage;
use totara_msteams\check\checks\bot_appid;
use totara_msteams\check\checks\bot_common;
use totara_msteams\check\checks\bot_secret;
use totara_msteams\check\checks\mf_appid;
use totara_msteams\check\checks\mf_appversion;
use totara_msteams\check\checks\mf_desc;
use totara_msteams\check\checks\mf_descfull;
use totara_msteams\check\checks\mf_name;
use totara_msteams\check\checks\mf_namefull;
use totara_msteams\check\checks\mf_package;
use totara_msteams\check\checks\pub_mpnid;
use totara_msteams\check\checks\pub_name;
use totara_msteams\check\checks\pub_website;
use totara_msteams\check\checks\site_privacy;
use totara_msteams\check\checks\site_terms;
use totara_msteams\check\checks\sso_auth;
use totara_msteams\check\checks\sso_id;
use totara_msteams\check\checks\sso_scope;
use totara_msteams\check\checks\url_common;
use totara_msteams\check\checks\wwwroot;
use totara_msteams\check\status;
use totara_msteams\check\verifier;

defined('MOODLE_INTERNAL') || die;

/**
 * Test verifier and checkable classes.
 * @coversDefaultClass totara_msteams\check\verifier
 */
class totara_msteams_verifier_testcase extends advanced_testcase {
    public function setUp(): void {
        $this->setAdminUser();
    }

    /**
     * @covers ::execute
     * @covers ::get_results
     */
    public function test_verifier() {
        global $CFG;
        // Let admin_catalog fail.
        $CFG->catalogtype = 'moodle';
        $verifier = new verifier();
        // Always fails because of no preparations.
        $this->assertFalse($verifier->execute());
        // See if checkable classes are in alphabetical order.
        $results = $verifier->get_results();
        $classes = array_map(function ($result) {
            return get_class($result->class);
        }, $results);
        $this->assertEquals([
            admin_catalog::class,
            admin_frame::class,
            admin_gridimage::class,
            bot_appid::class,
            bot_secret::class,
            mf_appid::class,
            mf_appversion::class,
            mf_desc::class,
            mf_descfull::class,
            mf_name::class,
            mf_namefull::class,
            mf_package::class,
            pub_mpnid::class,
            pub_name::class,
            pub_website::class,
            site_privacy::class,
            site_terms::class,
            sso_auth::class,
            sso_id::class,
            sso_scope::class,
            wwwroot::class,
        ], $classes);
        // See if the first failure is caused by the first class.
        $this->assertEquals(current($results)->class->get_report(), $verifier->get_report());
    }

    /**
     * Assert checkable::check() and report if fails.
     *
     * @param integer $expected
     * @param checkable $check
     */
    private function assert_check(int $expected, checkable $check): void {
        $result = $check->check();
        $report = $result === status::PASS ? '(PASS)' : $check->get_report();
        $this->assertEquals($expected, $result, $report);
    }

    /**
     * Add a dummy Microsoft OAuth2 service.
     *
     * @return integer
     */
    private static function add_microsoft_oauth2_issuer(): int {
        return api::init_standard_issuer('microsoft')
            ->set('clientid', '31415926-5358-9793-2384-626433832795')
            ->set('clientsecret', 'kIa0rAKoUt0u!')
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
     * Repeat the crying face.
     * The test requires the character in the supplementary planes of Unicode.
     *
     * @param integer $count how many times to cry?
     * @return string
     */
    private static function cry(int $count): string {
        // U+1F622 aka crying face.
        $ch = json_decode('"\ud83d\ude22"');
        return str_repeat($ch, $count);
    }

    /**
     * @covers totara_msteams\check\checks\admin_catalog::check
     */
    public function test_check_admin_catalog() {
        global $CFG;
        $check = new admin_catalog();
        $CFG->catalogtype = false;
        $this->assert_check(status::FAILED, $check);
        $CFG->catalogtype = 'moodle';
        $this->assert_check(status::FAILED, $check);
        $CFG->catalogtype = 'enhanced';
        $this->assert_check(status::FAILED, $check);
        $CFG->catalogtype = 'totara';
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\admin_frame::check
     */
    public function test_check_admin_frame() {
        global $CFG;
        $check = new admin_frame();
        $CFG->allowframembedding = false;
        $this->assert_check(status::FAILED, $check);
        $CFG->allowframembedding = true;
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\admin_gridimage::check
     */
    public function test_check_admin_gridimage() {
        global $CFG;
        $check = new admin_gridimage();
        $CFG->forcelogin = true;
        $CFG->publishgridcatalogimage = false;
        $this->assert_check(status::SKIPPED, $check);
        $CFG->forcelogin = true;
        $CFG->publishgridcatalogimage = true;
        $this->assert_check(status::PASS, $check);
        $CFG->forcelogin = false;
        $CFG->publishgridcatalogimage = false;
        $this->assert_check(status::PASS, $check);
        $CFG->forcelogin = false;
        $CFG->publishgridcatalogimage = true;
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\bot_common::check_bot
     */
    public function test_check_bot_common() {
        $check = new class extends bot_common {
            public function get_name(): string {
                return '';
            }
            public function get_config_name(): ?string {
                return null;
            }
            public function get_helplink(): ?moodle_url {
                return null;
            }
            public function check(): int {
                return $this->check_bot();
            }
        };
        set_config('bot_feature_enabled', 0, 'totara_msteams');
        set_config('messaging_extension_enabled', 0, 'totara_msteams');
        $this->assert_check(status::SKIPPED, $check);
        set_config('bot_feature_enabled', 1, 'totara_msteams');
        set_config('messaging_extension_enabled', 0, 'totara_msteams');
        $this->assert_check(status::PASS, $check);
        set_config('bot_feature_enabled', 0, 'totara_msteams');
        set_config('messaging_extension_enabled', 1, 'totara_msteams');
        $this->assert_check(status::PASS, $check);
        set_config('bot_feature_enabled', 1, 'totara_msteams');
        set_config('messaging_extension_enabled', 1, 'totara_msteams');
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\bot_appid::check
     */
    public function test_check_bot_id() {
        $check = new bot_appid();
        set_config('bot_feature_enabled', 1, 'totara_msteams');

        set_config('bot_app_id', '', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('bot_app_id', 'kia ora', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('bot_app_id', '00000000-0000-0000-0000-000000000000', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('bot_app_id', '31415926-5358-9793-2384-626433832795', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\bot_secret::check
     */
    public function test_check_bot_secret() {
        $check = new bot_secret();
        $this->assert_check(status::SKIPPED, $check);
        set_config('bot_feature_enabled', 1, 'totara_msteams');

        set_config('bot_app_secret', '', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('bot_app_secret', 'kIa0rAKoUt0u!', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\mf_appid::check
     */
    public function test_check_mf_appid() {
        $check = new mf_appid();
        set_config('manifest_app_id', '', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('manifest_app_id', 'kia ora', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('manifest_app_id', '00000000-0000-0000-0000-000000000000', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('manifest_app_id', '31415926-5358-9793-2384-626433832795', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\mf_appversion::check
     */
    public function test_check_mf_appversion() {
        $check = new mf_appversion();
        set_config('manifest_app_version', '', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        // Not checking the format at the moment.
        set_config('manifest_app_version', 'kia ora', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
        set_config('manifest_app_version', '1.2.3', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\mf_desc::check
     */
    public function test_check_mf_desc() {
        $check = new mf_desc();
        set_config('manifest_app_description', '', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('manifest_app_description', 'kia ora, I am a really long short application description that is no longer short', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('manifest_app_description', 'kia ora', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
        set_config('manifest_app_description', self::cry(mf_desc::MAX_LENGTH / 2), 'totara_msteams');
        $this->assert_check(status::PASS, $check);
        set_config('manifest_app_description', self::cry(mf_desc::MAX_LENGTH / 2 + 1), 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
    }

    /**
     * @covers totara_msteams\check\checks\mf_descfull::check
     */
    public function test_check_mf_descfull() {
        $check = new mf_descfull();
        set_config('manifest_app_fulldescription', '', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('manifest_app_fulldescription', 'x'.str_repeat('long', 1000), 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('manifest_app_fulldescription', 'kia ora', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
        set_config('manifest_app_fulldescription', self::cry(mf_descfull::MAX_LENGTH / 2), 'totara_msteams');
        $this->assert_check(status::PASS, $check);
        set_config('manifest_app_fulldescription', self::cry(mf_descfull::MAX_LENGTH / 2 + 1), 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
    }

    /**
     * @covers totara_msteams\check\checks\mf_name::check
     */
    public function test_check_mf_name() {
        $check = new mf_name();
        set_config('manifest_app_name', '', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('manifest_app_name', "it's a very long short app name", 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('manifest_app_name', 'kia ora', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
        set_config('manifest_app_name', self::cry(mf_name::MAX_LENGTH / 2), 'totara_msteams');
        $this->assert_check(status::PASS, $check);
        set_config('manifest_app_name', self::cry(mf_name::MAX_LENGTH / 2 + 1), 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
    }

    /**
     * @covers totara_msteams\check\checks\mf_namefull::check
     */
    public function test_check_mf_namefull() {
        $check = new mf_namefull();
        set_config('manifest_app_fullname', 'this is a really really long full application name that is excruciatingly longer than 100 characters.', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('manifest_app_fullname', '', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
        set_config('manifest_app_fullname', 'kia ora', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
        set_config('manifest_app_fullname', self::cry(mf_namefull::MAX_LENGTH / 2), 'totara_msteams');
        $this->assert_check(status::PASS, $check);
        set_config('manifest_app_fullname', self::cry(mf_namefull::MAX_LENGTH / 2 + 1), 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
    }

    /**
     * @covers totara_msteams\check\checks\mf_package::check
     */
    public function test_check_mf_package() {
        $check = new mf_package();
        set_config('manifest_app_package_name', '', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        // Not checking the format at the moment, apart from the default package name prior to Totara 13.3.
        set_config('manifest_app_package_name', 'com.totaralearning.microsoft.msteams', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('manifest_app_package_name', 'kia ora', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
        set_config('manifest_app_package_name', 'com.example.totara.msteams', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\pub_mpnid::check
     */
    public function test_check_pub_mpnid() {
        $check = new pub_mpnid();
        // Not checking the format at the moment.
        set_config('publisher_mpnid', '31415926535', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('publisher_mpnid', '', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
        set_config('publisher_mpnid', '0', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
        set_config('publisher_mpnid', 'kia ora', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\pub_name::check
     */
    public function test_check_pub_name() {
        global $CFG;
        $check = new pub_name();
        unset($CFG->publishername);
        $this->assert_check(status::FAILED, $check);
        $CFG->publishername = '';
        $this->assert_check(status::FAILED, $check);
        $CFG->publishername = 'it is a very long publisher name!';
        $this->assert_check(status::FAILED, $check);
        $CFG->publishername = 'kia ora';
        $this->assert_check(status::PASS, $check);
        $CFG->publishername = '0';
        $this->assert_check(status::PASS, $check);
        $CFG->publishername = self::cry(pub_name::MAX_LENGTH / 2);
        $this->assert_check(status::PASS, $check);
        $CFG->publishername = self::cry(pub_name::MAX_LENGTH / 2 + 1);
        $this->assert_check(status::FAILED, $check);
    }

    /**
     * @covers totara_msteams\check\checks\pub_website::check
     */
    public function test_check_pub_website() {
        global $CFG;
        $check = new pub_website();
        $CFG->publisherwebsite = 'http://example.com/totara/';
        $this->assert_check(status::FAILED, $check);
        $CFG->publisherwebsite = 'https://example.com/totara/en-nz/'.str_repeat('toolong/', 252);
        $this->assert_check(status::FAILED, $check);
        $CFG->publisherwebsite = 'https://example.com/totara/';
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\site_privacy::check
     */
    public function test_check_site_privacy_policy() {
        global $CFG;
        $check = new site_privacy();
        $CFG->privacypolicy = 'http://example.com/totara/';
        $this->assert_check(status::FAILED, $check);
        $CFG->privacypolicy = 'https://example.com/totara/en-nz/'.str_repeat('toolong/', 252);
        $this->assert_check(status::FAILED, $check);
        $CFG->privacypolicy = 'https://example.com/totara/';
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\site_terms::check
     */
    public function test_check_site_terms_of_use() {
        global $CFG;
        $check = new site_terms();
        $CFG->termsofuse = 'http://example.com/totara/';
        $this->assert_check(status::FAILED, $check);
        $CFG->termsofuse = 'https://example.com/totara/en-nz/'.str_repeat('toolong/', 252);
        $this->assert_check(status::FAILED, $check);
        $CFG->termsofuse = 'https://example.com/totara/';
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\url_common::check_url
     */
    public function test_check_url_common() {
        global $CFG;
        $check = new class extends url_common {
            public function get_name(): string {
                return '';
            }
            public function get_config_name(): ?string {
                return 'kakapo';
            }
            public function check(): int {
                return $this->check_url('check:kakapo_notset', 'check:kakapo_toolong', 'check:kakapo_insecure');
            }
        };
        $this->overrideLangString('check:kakapo_notset', 'totara_msteams', 'kakapo is not set', true);
        $this->overrideLangString('check:kakapo_toolong', 'totara_msteams', 'kakapo is too long, must be {$a} characters or less', true);
        $this->overrideLangString('check:kakapo_insecure', 'totara_msteams', 'kakapo is insecure, mind our environment', true);

        $CFG->wwwroot = 'http://example.com';
        unset($CFG->kakapo);
        $this->assert_check(status::FAILED, $check);
        $this->assertStringContainsString('kakapo is not set', $check->get_report());
        $CFG->kakapo = '';
        $this->assert_check(status::FAILED, $check);
        $this->assertStringContainsString('kakapo is not set', $check->get_report());

        $CFG->wwwroot = 'https://example.com/totara/en-nz/'.str_repeat('toolong/', 252);
        unset($CFG->kakapo);
        $this->assert_check(status::FAILED, $check);
        $this->assertStringContainsString('kakapo is not set', $check->get_report());
        $CFG->kakapo = '';
        $this->assert_check(status::FAILED, $check);
        $this->assertStringContainsString('kakapo is not set', $check->get_report());

        $CFG->wwwroot = 'https://example.com/totara/';
        unset($CFG->kakapo);
        $this->assert_check(status::SKIPPED, $check);
        $this->assertStringContainsString('The site URL is used by default', $check->get_report());
        $CFG->kakapo = '';
        $this->assert_check(status::SKIPPED, $check);
        $this->assertStringContainsString('The site URL is used by default', $check->get_report());
        $CFG->kakapo = '0';
        $this->assert_check(status::FAILED, $check);
        $this->assertStringContainsString('kakapo is insecure', $check->get_report());

        $CFG->wwwroot = 'http://example.com'; // wwwroot doesn't matter now.
        $CFG->kakapo = 'http://example.com/kakapo/';
        $this->assert_check(status::FAILED, $check);
        $this->assertStringContainsString('kakapo is insecure', $check->get_report());
        $CFG->kakapo = 'https://example.com/kakapo/en-nz/'.str_repeat('toolong/', 252);
        $this->assert_check(status::FAILED, $check);
        $this->assertStringContainsString('kakapo is too long, must be 2048 characters or less', $check->get_report());
        $CFG->kakapo = 'https://example.com/kakapo/';
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\sso_auth::check
     */
    public function test_check_sso_auth() {
        $check = new sso_auth();
        $issuerid = self::add_microsoft_oauth2_issuer();

        self::enable_oauth2_plugin(false);
        set_config('oauth2_issuer', '', 'totara_msteams');
        $this->assert_check(status::SKIPPED, $check);
        set_config('oauth2_issuer', 0, 'totara_msteams');
        $this->assert_check(status::SKIPPED, $check);
        set_config('oauth2_issuer', 42, 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('oauth2_issuer', $issuerid, 'totara_msteams');
        $this->assert_check(status::FAILED, $check);

        self::enable_oauth2_plugin(true);
        set_config('oauth2_issuer', '', 'totara_msteams');
        $this->assert_check(status::SKIPPED, $check);
        set_config('oauth2_issuer', 0, 'totara_msteams');
        $this->assert_check(status::SKIPPED, $check);
        set_config('oauth2_issuer', 42, 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('oauth2_issuer', $issuerid, 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        self::add_microsoft_oauth2_endpoints($issuerid);
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\sso_id::check
     */
    public function test_check_sso_id() {
        $check = new sso_id();
        $this->assert_check(status::SKIPPED, $check);
        self::enable_oauth2_plugin(true);
        $issuerid = self::add_microsoft_oauth2_issuer();
        self::add_microsoft_oauth2_endpoints($issuerid);
        set_config('oauth2_issuer', $issuerid, 'totara_msteams');

        set_config('sso_app_id', '', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('sso_app_id', 'kia ora', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('sso_app_id', '00000000-0000-0000-0000-000000000000', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('sso_app_id', '31415926-5358-9793-2384-626433832795', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
    }

    /**
     * @covers totara_msteams\check\checks\sso_scope::check
     */
    public function test_check_sso_scope() {
        $check = new sso_scope();
        $this->assert_check(status::SKIPPED, $check);
        self::enable_oauth2_plugin(true);
        $issuerid = self::add_microsoft_oauth2_issuer();
        self::add_microsoft_oauth2_endpoints($issuerid);
        set_config('oauth2_issuer', $issuerid, 'totara_msteams');
        set_config('sso_app_id', '31415926-5358-9793-2384-626433832795', 'totara_msteams');

        set_config('sso_scope', '', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('sso_scope', 'kia ora', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('sso_scope', 'https://example.com/31415926-5358-9793-2384-626433832795', 'totara_msteams');
        $this->assert_check(status::FAILED, $check);
        set_config('sso_scope', 'api://example.com/31415926-5358-9793-2384-626433832795', 'totara_msteams');
        $this->assert_check(status::PASS, $check);
    }

    public function test_check_wwwroot() {
        global $CFG;
        $check = new wwwroot();
        $CFG->wwwroot = 'ftp://example.com';
        $this->assert_check(status::FAILED, $check);
        $CFG->wwwroot = 'http://example.com';
        $this->assert_check(status::FAILED, $check);
        $CFG->wwwroot = 'https://example.com';
        $this->assert_check(status::PASS, $check);
    }
}
