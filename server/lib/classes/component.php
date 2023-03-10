<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Components (core subsystems + plugins) related code.
 *
 * @package    core
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Constants used in version.php files, these must exist when core_component executes.

/** Software maturity level - internals can be tested using white box techniques. */
define('MATURITY_ALPHA',    50);
/** Software maturity level - feature complete, ready for preview and testing. */
define('MATURITY_BETA',     100);
/** Software maturity level - tested, will be released unless there are fatal bugs. */
define('MATURITY_RC',       150);
/** Software maturity level - the latest rolling Totara release. */
define('MATURITY_EVERGREEN',  190);
/** Software maturity level - ready for production deployment. */
define('MATURITY_STABLE',   200);
/** Any version - special value that can be used in $plugin->dependencies in version.php files. */
define('ANY_VERSION', 'any');


/**
 * Collection of components related methods.
 */
class core_component {
    /** @var array list of ignored directories - watch out for auth/db exception */
    protected static $ignoreddirs = array('CVS'=>true, '_vti_cnf'=>true, 'simpletest'=>true, 'db'=>true, 'yui'=>true, 'tests'=>true, 'classes'=>true, 'fonts'=>true);
    /** @var array list plugin types that support subplugins, do not add more here unless absolutely necessary */
    protected static $supportsubplugins = array('mod', 'editor', 'totara', 'tool', 'local');

    protected static $normalised_component_map = [
        '' => ['core', null],
        'core' => ['core', null],
        'moodle' => ['core', null],
        'admin' => ['core', 'admin'],
        'core_admin' => ['core', 'admin'],
        'moodle_admin' => ['core', 'admin'],
    ];

    /** @var array cache of plugin types */
    protected static $plugintypes = null;
    /** @var array cache of plugin locations */
    protected static $plugins = null;
    /** @var array cache of core subsystems */
    protected static $subsystems = null;
    /** @var array subplugin type parents */
    protected static $parents = null;
    /** @var array subplugins */
    protected static $subplugins = null;
    /** @var array list of all known classes that can be autoloaded */
    protected static $classmap = null;
    /** @var array list of all classes that have been renamed to be autoloaded */
    protected static $classmaprenames = null;
    /** @var array list of some known files that can be included. */
    protected static $filemap = null;
    /** @var int|float core version. */
    protected static $version = null;
    /** @var array list of the files to map. */
    protected static $filestomap = array('lib.php', 'settings.php');
    /** @var array associative array of PSR-0 namespaces and corresponding paths. */
    protected static $psr0namespaces = array(
        'Horde' => 'lib/horde/framework/Horde',
        'Mustache' => 'lib/mustache/src/Mustache',
    );
    /** @var array associative array of PRS-4 namespaces and corresponding paths. */
    protected static $psr4namespaces = array(
        'MoodleHQ\\RTLCSS' => 'lib/rtlcss',
        'MatthiasMullie\\Minify' => 'lib/minify/matthiasmullie-minify/src/',
        'MatthiasMullie\\PathConverter' => 'lib/minify/matthiasmullie-pathconverter/src/',
        'IMSGlobal\LTI' => 'lib/ltiprovider/src',
        // Totara: extra libraries
        'RedeyeVentures\\GeoPattern' => 'lib/geopattern-php/GeoPattern',
        'MongoDB' => 'cache/stores/mongodb/MongoDB',
        'Firebase\\JWT' => 'lib/php-jwt/src',
    );

    /** @var string Totara build */
    protected static $totarabuild = null;

    /**
     * Class loader for Frankenstyle named classes in standard locations.
     * Frankenstyle namespaces are supported.
     *
     * The expected location for core classes is:
     *    1/ core_xx_yy_zz ---> lib/classes/xx_yy_zz.php
     *    2/ \core\xx_yy_zz ---> lib/classes/xx_yy_zz.php
     *    3/ \core\xx\yy_zz ---> lib/classes/xx/yy_zz.php
     *
     * The expected location for plugin classes is:
     *    1/ mod_name_xx_yy_zz ---> mod/name/classes/xx_yy_zz.php
     *    2/ \mod_name\xx_yy_zz ---> mod/name/classes/xx_yy_zz.php
     *    3/ \mod_name\xx\yy_zz ---> mod/name/classes/xx/yy_zz.php
     *
     * @param string $classname
     */
    public static function classloader($classname) {
        self::init();

        if (isset(self::$classmap[$classname])) {
            // Global $CFG is expected in included scripts.
            global $CFG;
            // Function include would be faster, but for BC it is better to include only once.
            include_once(self::$classmap[$classname]);
            return;
        }
        if (isset(self::$classmaprenames[$classname]) && isset(self::$classmap[self::$classmaprenames[$classname]])) {
            $newclassname = self::$classmaprenames[$classname];
            $debugging = "Class '%s' has been renamed for the autoloader and is now deprecated. Please use '%s' instead.";
            debugging(sprintf($debugging, $classname, $newclassname), DEBUG_DEVELOPER);
            if (PHP_VERSION_ID >= 70000 && preg_match('#\\\null(\\\|$)#', $classname)) {
                throw new \coding_exception("Cannot alias $classname to $newclassname");
            }
            class_alias($newclassname, $classname);
            return;
        }

        $file = self::psr_classloader($classname);
        // If the file is found, require it.
        if (!empty($file)) {
            require($file);
            return;
        }
    }

    /**
     * Check if a class exists in our class map or in the psr classes. This can be used as an alternative to the
     * class_exists() function provided by PHP. It does check if the requested class exists in our class map or is part
     * of the PSR0 and PSR4 namespaces we support.
     *
     * In some cases this method performs better than using class_exists() with autoloading as you can opt out
     * of the more costly PSR class loader.
     *
     * @param string $classname
     * @param bool $include_psr defaults to false, use with care as going through psr classes might have a performance impact
     * @return bool
     */
    public static function class_exists(string $classname, bool $include_psr = false): bool {
        $exists = isset(self::$classmap[$classname]);

        if (!$exists && $include_psr) {
            $exists = self::psr_classloader($classname) !== false;
        }

        return $exists;
    }

    /**
     * Return the path to a class from our defined PSR-0 or PSR-4 standard namespaces on
     * demand. Only returns paths to files that exist.
     *
     * Adapated from http://www.php-fig.org/psr/psr-4/examples/ and made PSR-0
     * compatible.
     *
     * @param string $class the name of the class.
     * @return string|bool The full path to the file defining the class. Or false if it could not be resolved or does not exist.
     */
    protected static function psr_classloader($class) {
        // Iterate through each PSR-4 namespace prefix.
        foreach (self::$psr4namespaces as $prefix => $path) {
            $file = self::get_class_file($class, $prefix, $path, array('\\'));
            if (!empty($file) && file_exists($file)) {
                return $file;
            }
        }

        // Iterate through each PSR-0 namespace prefix.
        foreach (self::$psr0namespaces as $prefix => $path) {
            $file = self::get_class_file($class, $prefix, $path, array('\\', '_'));
            if (!empty($file) && file_exists($file)) {
                return $file;
            }
        }

        return false;
    }

    /**
     * Return the path to the class based on the given namespace prefix and path it corresponds to.
     *
     * Will return the path even if the file does not exist. Check the file esists before requiring.
     *
     * @param string $class the name of the class.
     * @param string $prefix The namespace prefix used to identify the base directory of the source files.
     * @param string $path The relative path to the base directory of the source files.
     * @param string[] $separators The characters that should be used for separating.
     * @return string|bool The full path to the file defining the class. Or false if it could not be resolved.
     */
    protected static function get_class_file($class, $prefix, $path, $separators) {
        global $CFG;

        // Does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            // No, move to the next prefix.
            return false;
        }
        $path = $CFG->dirroot . '/' . $path;

        // Get the relative class name.
        $relativeclass = substr($class, $len);

        // Replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php.
        $file = $path . str_replace($separators, '/', $relativeclass) . '.php';

        return $file;
    }


    /**
     * Initialise caches, always call before accessing self:: caches.
     *
     * @return bool returns true if the cached version got used, false if it wasn't cached yet
     */
    protected static function init(): bool {
        global $CFG;

        // Init only once per request/CLI execution, we ignore changes done afterwards.
        if (isset(self::$plugintypes)) {
            return true;
        }

        if (defined('IGNORE_COMPONENT_CACHE') and IGNORE_COMPONENT_CACHE) {
            self::fill_all_caches();
            return false;
        }

        if (!empty($CFG->alternative_component_cache)) {
            // Hack for heavily clustered sites that want to manage component cache invalidation manually.
            $cachefile = $CFG->alternative_component_cache;

            if (file_exists($cachefile)) {
                if (CACHE_DISABLE_ALL) {
                    // Verify the cache state only on upgrade pages.
                    $content = self::get_cache_content();
                    if (sha1_file($cachefile) !== sha1($content)) {
                        die('Outdated component cache file defined in $CFG->alternative_component_cache, can not continue');
                    }
                    return false;
                }
                $cache = array();
                include($cachefile);
                self::$plugintypes      = $cache['plugintypes'];
                self::$plugins          = $cache['plugins'];
                self::$subsystems       = $cache['subsystems'];
                self::$parents          = $cache['parents'];
                self::$subplugins       = $cache['subplugins'];
                self::$classmap         = $cache['classmap'];
                self::$classmaprenames  = $cache['classmaprenames'];
                self::$filemap          = $cache['filemap'];
                return true;
            }

            if (!is_writable(dirname($cachefile))) {
                die('Can not create alternative component cache file defined in $CFG->alternative_component_cache, can not continue');
            }

            // Lets try to create the file, it might be in some writable directory or a local cache dir.

        } else {
            // Note: $CFG->cachedir MUST be shared by all servers in a cluster,
            //       use $CFG->alternative_component_cache if you do not like it.
            $cachefile = "$CFG->cachedir/core_component.php";
        }

        if (!CACHE_DISABLE_ALL and !self::is_developer()) {
            // 1/ Use the cache only outside of install and upgrade.
            // 2/ Let developers add/remove classes in developer mode.
            if (is_readable($cachefile)) {
                $cache = false;
                include($cachefile);
                if (!is_array($cache)) {
                    // Something is very wrong.
                } else if (!isset($cache['version'])) {
                    // Something is very wrong.
                } else if ((float) $cache['version'] !== (float) self::fetch_core_version()) {
                    // Outdated cache. We trigger an error log to track an eventual repetitive failure of float comparison.
                    error_log('Resetting core_component cache after core upgrade to version ' . self::fetch_core_version());
                } else if (!isset($cache['totarabuild']) or $cache['totarabuild'] !== self::fetch_totarabuild()) {
                    error_log('Resetting core_component cache after totara upgrade to build ' . self::fetch_totarabuild());
                } else if ($cache['plugintypes']['mod'] !== "$CFG->dirroot/mod") {
                    // $CFG->dirroot was changed.
                } else {
                    // The cache looks ok, let's use it.
                    self::$plugintypes      = $cache['plugintypes'];
                    self::$plugins          = $cache['plugins'];
                    self::$subsystems       = $cache['subsystems'];
                    self::$parents          = $cache['parents'];
                    self::$subplugins       = $cache['subplugins'];
                    self::$classmap         = $cache['classmap'];
                    self::$classmaprenames  = $cache['classmaprenames'];
                    self::$filemap          = $cache['filemap'];
                    return true;
                }
                // Note: we do not verify $CFG->admin here intentionally,
                //       they must visit admin/index.php after any change.
            }
        }

        if (!isset(self::$plugintypes)) {
            // This needs to be atomic and self-fixing as much as possible.

            $content = self::get_cache_content();
            if (file_exists($cachefile)) {
                if (sha1_file($cachefile) === sha1($content)) {
                    return true;
                }
                // Stale cache detected!
                unlink($cachefile);
            }

            // Permissions might not be setup properly in installers.
            $dirpermissions = !isset($CFG->directorypermissions) ? 02777 : $CFG->directorypermissions;
            $filepermissions = !isset($CFG->filepermissions) ? ($dirpermissions & 0666) : $CFG->filepermissions;

            clearstatcache();
            $cachedir = dirname($cachefile);
            if (!is_dir($cachedir)) {
                mkdir($cachedir, $dirpermissions, true);
            }

            if ($fp = @fopen($cachefile.'.tmp', 'xb')) {
                fwrite($fp, $content);
                fclose($fp);
                @rename($cachefile.'.tmp', $cachefile);
                @chmod($cachefile, $filepermissions);
            }
            @unlink($cachefile.'.tmp'); // Just in case anything fails (race condition).
            self::invalidate_opcode_php_cache($cachefile);
        }

        return false;
    }

    /**
     * Are we in developer debug mode?
     *
     * Note: You need to set "$CFG->debug = (E_ALL | E_STRICT);" in config.php,
     *       the reason is we need to use this before we setup DB connection or caches for CFG.
     *
     * @return bool
     */
    protected static function is_developer() {
        global $CFG;

        // Note we can not rely on $CFG->debug here because DB is not initialised yet.
        if (isset($CFG->config_php_settings['debug'])) {
            $debug = (int)$CFG->config_php_settings['debug'];
        } else {
            return false;
        }

        if ($debug & E_ALL and $debug & E_STRICT) {
            return true;
        }

        return false;
    }

    /**
     * Create cache file content.
     *
     * @private this is intended for $CFG->alternative_component_cache only.
     *
     * @return string
     */
    public static function get_cache_content() {
        if (!isset(self::$plugintypes)) {
            self::fill_all_caches();
        }

        $cache = array(
            'subsystems'        => self::$subsystems,
            'plugintypes'       => self::$plugintypes,
            'plugins'           => self::$plugins,
            'parents'           => self::$parents,
            'subplugins'        => self::$subplugins,
            'classmap'          => self::$classmap,
            'classmaprenames'   => self::$classmaprenames,
            'filemap'           => self::$filemap,
            'version'           => self::$version,
            'totarabuild'       => self::$totarabuild,
        );

        return '<?php
$cache = '.var_export($cache, true).';
';
    }

    /**
     * Fill all caches.
     */
    protected static function fill_all_caches() {
        self::$subsystems = self::fetch_subsystems();

        list(self::$plugintypes, self::$parents, self::$subplugins) = self::fetch_plugintypes();

        self::$plugins = array();
        foreach (self::$plugintypes as $type => $fulldir) {
            self::$plugins[$type] = self::fetch_plugins($type, $fulldir);
        }

        self::fill_classmap_cache();
        self::fill_classmap_renames_cache();
        self::fill_filemap_cache();
        self::fetch_core_version();
        self::fetch_totarabuild();
    }

    /**
     * Get the core version.
     *
     * In order for this to work properly, opcache should be reset beforehand.
     *
     * @return float core version.
     */
    protected static function fetch_core_version() {
        global $CFG;
        if (self::$version === null) {
            $version = null; // Prevent IDE complaints.
            require($CFG->dirroot . '/version.php');
            self::$version = $version;
        }
        return self::$version;
    }

    /**
     * Get the Totara build.
     *
     * In order for this to work properly, opcache should be reset beforehand.
     *
     * @return string build number
     */
    protected static function fetch_totarabuild() {
        global $CFG;
        if (self::$totarabuild === null) {

            $TOTARA = new stdClass(); // Prevent IDE complaints.
            require($CFG->dirroot . '/version.php');
            self::$totarabuild = $TOTARA->build;
        }
        return self::$totarabuild;
    }

    /**
     * Returns list of core subsystems.
     * @return array
     */
    protected static function fetch_subsystems() {
        global $CFG;

        if (!isset($CFG->dirroot)) {
            throw new coding_exception('$CFG has not been correctly initialised.', 'dirroot is missing');
        }

        // NOTE: Any additions here must be verified to not collide with existing add-on modules and subplugins!!!
        $info = array(
            'access'      => null,
            'admin'       => $CFG->dirroot.'/'.$CFG->admin,
            'antivirus'   => $CFG->dirroot . '/lib/antivirus',
            'auth'        => $CFG->dirroot.'/auth',
            'availability' => $CFG->dirroot . '/availability',
            'backup'      => $CFG->dirroot.'/backup/util/ui',
            'badges'      => $CFG->dirroot.'/badges',
            'block'       => $CFG->dirroot.'/blocks',
            'blog'        => $CFG->dirroot.'/blog',
            'bulkusers'   => null,
            'cache'       => $CFG->dirroot.'/cache',
            'calendar'    => $CFG->dirroot.'/calendar',
            'cohort'      => $CFG->dirroot.'/cohort',
            'comment'     => $CFG->dirroot.'/comment',
            //'competency'  => $CFG->dirroot.'/competency', // Totara: not present!
            'completion'  => $CFG->dirroot.'/completion',
            'countries'   => null,
            'course'      => $CFG->dirroot.'/course',
            'currencies'  => null,
            'dbtransfer'  => null,
            'debug'       => null,
            'editor'      => $CFG->dirroot.'/lib/editor',
            'edufields'   => null,
            'enrol'       => $CFG->dirroot.'/enrol',
            'error'       => null,
            'filepicker'  => null,
            'files'       => $CFG->dirroot.'/files',
            'filters'     => null,
            //'fonts'       => null, // Bogus.
            'form'        => $CFG->dirroot.'/lib/form',
            'grades'      => $CFG->dirroot.'/grade',
            'grading'     => $CFG->dirroot.'/grade/grading',
            'group'       => $CFG->dirroot.'/group',
            'help'        => null,
            //'hub'         => null, // Totara: we removed Moodle hubs.
            'imscc'       => null,
            'install'     => null,
            'iso6392'     => null,
            'langconfig'  => null,
            'license'     => null,
            'mathslib'    => null,
            'media'       => $CFG->dirroot.'/media',
            'message'     => $CFG->dirroot.'/message',
            'mimetypes'   => null,
            //'moodle.org'  => null, // Not used any more.
            'my'          => $CFG->dirroot.'/my',
            'notes'       => $CFG->dirroot.'/notes',
            'pagetype'    => null,
            'pix'         => null,
            'plagiarism'  => $CFG->dirroot.'/plagiarism',
            'plugin'      => null,
            'portfolio'   => $CFG->dirroot.'/portfolio',
            //'publish'     => $CFG->dirroot.'/course/publish', // Totara: we removed course publishing.
            'question'    => $CFG->dirroot.'/question',
            'rating'      => $CFG->dirroot.'/rating',
            //'register'    => $CFG->dirroot.'/'.$CFG->admin.'/registration', // Totara: we removed Moodle registration.
            'repository'  => $CFG->dirroot.'/repository',
            'rss'         => $CFG->dirroot.'/rss',
            'role'        => $CFG->dirroot.'/'.$CFG->admin.'/roles',
            'search'      => $CFG->dirroot.'/search',
            'table'       => null,
            'tag'         => $CFG->dirroot.'/tag',
            'timezones'   => null,
            'user'        => $CFG->dirroot.'/user',
            'userkey'     => null,
            'webservice'  => $CFG->dirroot.'/webservice',
            // Totara: extra subsystems - do not add any new!
            'elementlibrary' => $CFG->dirroot.'/elementlibrary',
            'container'     => $CFG->dirroot . '/container',
            'ml'            => $CFG->dirroot.'/ml',
            'virtualmeeting' => $CFG->dirroot.'/integrations/virtualmeeting',
        );

        return $info;
    }

    /**
     * Returns list of known plugin types.
     * @return array
     */
    protected static function fetch_plugintypes() {
        global $CFG;

        $types = array(
            'antivirus'     => $CFG->dirroot . '/lib/antivirus',
            'availability'  => $CFG->dirroot . '/availability/condition',
            'qtype'         => $CFG->dirroot.'/question/type',
            'mod'           => $CFG->dirroot.'/mod',
            'auth'          => $CFG->dirroot.'/auth',
            'calendartype'  => $CFG->dirroot.'/calendar/type',
            'enrol'         => $CFG->dirroot.'/enrol',
            'message'       => $CFG->dirroot.'/message/output',
            'block'         => $CFG->dirroot.'/blocks',
            'media'         => $CFG->dirroot.'/media/player',
            'filter'        => $CFG->dirroot.'/filter',
            'editor'        => $CFG->dirroot.'/lib/editor',
            'format'        => $CFG->dirroot.'/course/format',
            'dataformat'    => $CFG->dirroot.'/dataformat',
            'profilefield'  => $CFG->dirroot.'/user/profile/field',
            'report'        => $CFG->dirroot.'/report',
            'coursereport'  => $CFG->dirroot.'/course/report', // Must be after system reports.
            'gradeexport'   => $CFG->dirroot.'/grade/export',
            'gradeimport'   => $CFG->dirroot.'/grade/import',
            'gradereport'   => $CFG->dirroot.'/grade/report',
            'gradingform'   => $CFG->dirroot.'/grade/grading/form',
            'webservice'    => $CFG->dirroot.'/webservice',
            'repository'    => $CFG->dirroot.'/repository',
            'portfolio'     => $CFG->dirroot.'/portfolio',
            'search'        => $CFG->dirroot.'/search/engine',
            'qbehaviour'    => $CFG->dirroot.'/question/behaviour',
            'qformat'       => $CFG->dirroot.'/question/format',
            'plagiarism'    => $CFG->dirroot.'/plagiarism',
            'totara'        => $CFG->dirroot.'/totara', // must be before admin tools so totara_sync can add to totara tables
            'tool'          => $CFG->dirroot.'/'.$CFG->admin.'/tool',
            'cachestore'    => $CFG->dirroot.'/cache/stores',
            'cachelock'     => $CFG->dirroot.'/cache/locks',
            'container'     => $CFG->dirroot . '/container/type',
            'ml'            => $CFG->dirroot.'/ml',
            'virtualmeeting' => $CFG->dirroot.'/integrations/virtualmeeting',
            'jsoneditor'    => $CFG->dirroot.'/text_format/json_editor/extensions',
        );
        $parents = array();
        $subplugins = array();

        if (!empty($CFG->themedir) and is_dir($CFG->themedir) ) {
            $types['theme'] = $CFG->themedir;
        } else {
            $types['theme'] = $CFG->dirroot.'/theme';
        }

        foreach (self::$supportsubplugins as $type) {
            if ($type === 'local') {
                // Local subplugins must be after local plugins.
                continue;
            }
            $plugins = self::fetch_plugins($type, $types[$type]);
            foreach ($plugins as $plugin => $fulldir) {
                $subtypes = self::fetch_subtypes($fulldir);
                if (!$subtypes) {
                    continue;
                }
                $subplugins[$type.'_'.$plugin] = array();
                foreach($subtypes as $subtype => $subdir) {
                    if (isset($types[$subtype])) {
                        error_log("Invalid subtype '$subtype', duplicate detected.");
                        continue;
                    }
                    $types[$subtype] = $subdir;
                    $parents[$subtype] = $type.'_'.$plugin;
                    $subplugins[$type.'_'.$plugin][$subtype] = array_keys(self::fetch_plugins($subtype, $subdir));
                }
            }
        }
        // Local is always last!
        $types['local'] = $CFG->dirroot.'/local';

        if (in_array('local', self::$supportsubplugins)) {
            $type = 'local';
            $plugins = self::fetch_plugins($type, $types[$type]);
            foreach ($plugins as $plugin => $fulldir) {
                $subtypes = self::fetch_subtypes($fulldir);
                if (!$subtypes) {
                    continue;
                }
                $subplugins[$type.'_'.$plugin] = array();
                foreach($subtypes as $subtype => $subdir) {
                    if (isset($types[$subtype])) {
                        error_log("Invalid subtype '$subtype', duplicate detected.");
                        continue;
                    }
                    $types[$subtype] = $subdir;
                    $parents[$subtype] = $type.'_'.$plugin;
                    $subplugins[$type.'_'.$plugin][$subtype] = array_keys(self::fetch_plugins($subtype, $subdir));
                }
            }
        }

        return array($types, $parents, $subplugins);
    }

    /**
     * Returns list of subtypes.
     * @param string $ownerdir
     * @return array
     */
    protected static function fetch_subtypes($ownerdir) {
        global $CFG;

        $types = array();
        if (file_exists("$ownerdir/db/subplugins.php")) {
            $subplugins = array();
            include("$ownerdir/db/subplugins.php");
            foreach ($subplugins as $subtype => $dir) {
                if (!preg_match('/^[a-z][a-z0-9]*$/', $subtype)) {
                    error_log("Invalid subtype '$subtype'' detected in '$ownerdir', invalid characters present.");
                    continue;
                }
                if (isset(self::$subsystems[$subtype])) {
                    error_log("Invalid subtype '$subtype'' detected in '$ownerdir', duplicates core subsystem.");
                    continue;
                }
                if ($CFG->admin !== 'admin' and strpos($dir, 'admin/') === 0) {
                    $dir = preg_replace('|^admin/|', "$CFG->admin/", $dir);
                }
                if (!is_dir("$CFG->dirroot/$dir")) {
                    error_log("Invalid subtype directory '$dir' detected in '$ownerdir'.");
                    continue;
                }
                $types[$subtype] = "$CFG->dirroot/$dir";
            }
        }
        return $types;
    }

    /**
     * Returns list of plugins of given type in given directory.
     * @param string $plugintype
     * @param string $fulldir
     * @return array
     */
    protected static function fetch_plugins($plugintype, $fulldir) {
        global $CFG;

        $fulldirs = (array)$fulldir;
        if ($plugintype === 'theme') {
            if (realpath($fulldir) !== realpath($CFG->dirroot.'/theme')) {
                // Include themes in standard location too.
                array_unshift($fulldirs, $CFG->dirroot.'/theme');
            }
        }

        $result = array();

        foreach ($fulldirs as $fulldir) {
            if (!is_dir($fulldir)) {
                continue;
            }
            $items = new \DirectoryIterator($fulldir);
            foreach ($items as $item) {
                if ($item->isDot() or !$item->isDir()) {
                    continue;
                }
                $pluginname = $item->getFilename();
                if ($plugintype === 'auth' and $pluginname === 'db') {
                    // Special exception for this wrong plugin name.
                } else if (isset(self::$ignoreddirs[$pluginname])) {
                    continue;
                }
                if (!self::is_valid_plugin_name($plugintype, $pluginname)) {
                    // Always ignore plugins with problematic names here.
                    continue;
                }
                $result[$pluginname] = $fulldir.'/'.$pluginname;
                unset($item);
            }
            unset($items);
        }

        ksort($result);
        return $result;
    }

    /**
     * Find all classes that can be autoloaded including frankenstyle namespaces.
     */
    protected static function fill_classmap_cache() {
        global $CFG;

        self::$classmap = array();

        self::load_classes('core', "$CFG->dirroot/lib/classes");

        foreach (self::$subsystems as $subsystem => $fulldir) {
            if (!$fulldir) {
                continue;
            }
            self::load_classes('core_'.$subsystem, "$fulldir/classes");
        }

        foreach (self::$plugins as $plugintype => $plugins) {
            foreach ($plugins as $pluginname => $fulldir) {
                self::load_classes($plugintype.'_'.$pluginname, "$fulldir/classes");
            }
        }

        // PHPUnit stuff must not be used in normal production code.
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            self::load_classes('core_phpunit', "$CFG->dirroot/lib/phpunit/classes");
        }

        ksort(self::$classmap);
    }

    /**
     * Fills up the cache defining what plugins have certain files.
     *
     * @see self::get_plugin_list_with_file
     * @return void
     */
    protected static function fill_filemap_cache() {
        global $CFG;

        self::$filemap = array();

        foreach (self::$filestomap as $file) {
            if (!isset(self::$filemap[$file])) {
                self::$filemap[$file] = array();
            }
            foreach (self::$plugins as $plugintype => $plugins) {
                if (!isset(self::$filemap[$file][$plugintype])) {
                    self::$filemap[$file][$plugintype] = array();
                }
                foreach ($plugins as $pluginname => $fulldir) {
                    if (file_exists("$fulldir/$file")) {
                        self::$filemap[$file][$plugintype][$pluginname] = "$fulldir/$file";
                    }
                }
            }
        }
    }

    /**
     * Find classes in directory and recurse to subdirs.
     * @param string $component
     * @param string $fulldir
     * @param string $namespace
     */
    protected static function load_classes($component, $fulldir, $namespace = '') {
        if (!is_dir($fulldir)) {
            return;
        }

        if (!is_readable($fulldir)) {
            // TODO: MDL-51711 We should generate some diagnostic debugging information in this case
            // because its pretty likely to lead to a missing class error further down the line.
            // But our early setup code can't handle errors this early at the moment.
            return;
        }

        $items = new \DirectoryIterator($fulldir);
        foreach ($items as $item) {
            if ($item->isDot()) {
                continue;
            }
            if ($item->isDir()) {
                $dirname = $item->getFilename();
                self::load_classes($component, "$fulldir/$dirname", $namespace.'\\'.$dirname);
                continue;
            }

            $filename = $item->getFilename();
            $classname = preg_replace('/\.php$/', '', $filename);

            if ($filename === $classname) {
                // Not a php file.
                continue;
            }
            if ($namespace === '') {
                // Legacy long frankenstyle class name.
                self::$classmap[$component.'_'.$classname] = "$fulldir/$filename";
            }
            // New namespaced classes.
            self::$classmap[$component.$namespace.'\\'.$classname] = "$fulldir/$filename";
        }
        unset($item);
        unset($items);
    }


    /**
     * List all core subsystems and their location
     *
     * This is a whitelist of components that are part of the core and their
     * language strings are defined in /lang/en/<<subsystem>>.php. If a given
     * plugin is not listed here and it does not have proper plugintype prefix,
     * then it is considered as course activity module.
     *
     * The location is absolute file path to dir. NULL means there is no special
     * directory for this subsystem. If the location is set, the subsystem's
     * renderer.php is expected to be there.
     *
     * @return array of (string)name => (string|null)full dir location
     */
    public static function get_core_subsystems() {
        self::init();
        return self::$subsystems;
    }

    /**
     * Get list of available plugin types together with their location.
     *
     * @return array as (string)plugintype => (string)fulldir
     */
    public static function get_plugin_types() {
        self::init();
        return self::$plugintypes;
    }

    /**
     * Get list of plugins of given type.
     *
     * @param string $plugintype
     * @return array as (string)pluginname => (string)fulldir
     */
    public static function get_plugin_list($plugintype) {
        self::init();

        if (!isset(self::$plugins[$plugintype])) {
            return array();
        }
        return self::$plugins[$plugintype];
    }

    /**
     * Get a list of all the plugins of a given type that define a certain class
     * in a certain file. The plugin component names and class names are returned.
     *
     * @param string $plugintype the type of plugin, e.g. 'mod' or 'report'.
     * @param string $class the part of the name of the class after the
     *      frankenstyle prefix. e.g 'thing' if you are looking for classes with
     *      names like report_courselist_thing. If you are looking for classes with
     *      the same name as the plugin name (e.g. qtype_multichoice) then pass ''.
     *      Frankenstyle namespaces are also supported.
     * @param string $file the name of file within the plugin that defines the class.
     * @return array with frankenstyle plugin names as keys (e.g. 'report_courselist', 'mod_forum')
     *      and the class names as values (e.g. 'report_courselist_thing', 'qtype_multichoice').
     */
    public static function get_plugin_list_with_class($plugintype, $class, $file = null) {
        global $CFG; // Necessary in case it is referenced by included PHP scripts.

        if ($class) {
            $suffix = '_' . $class;
        } else {
            $suffix = '';
        }

        $pluginclasses = array();
        $plugins = self::get_plugin_list($plugintype);
        foreach ($plugins as $plugin => $fulldir) {
            // Try class in frankenstyle namespace.
            if ($class) {
                $classname = '\\' . $plugintype . '_' . $plugin . '\\' . $class;
                if (class_exists($classname, true)) {
                    $pluginclasses[$plugintype . '_' . $plugin] = $classname;
                    continue;
                }
            }

            // Try autoloading of class with frankenstyle prefix.
            $classname = $plugintype . '_' . $plugin . $suffix;
            if (class_exists($classname, true)) {
                $pluginclasses[$plugintype . '_' . $plugin] = $classname;
                continue;
            }

            // Fall back to old file location and class name.
            if ($file and file_exists("$fulldir/$file")) {
                include_once("$fulldir/$file");
                if (class_exists($classname, false)) {
                    $pluginclasses[$plugintype . '_' . $plugin] = $classname;
                    continue;
                }
            }
        }

        return $pluginclasses;
    }

    /**
     * Returns classes from given namespace across all Totara plugins and core.
     *
     * @param string $namespace plugin namespace, this cannot include the name of the plugin, for example 'rb\display'
     * @param string $instanceof full name of class or interface that returned classes should be extending/implementing
     * @param string $component restrict results to one plugin only, for example 'totara_reportbuilder'
     * @param bool $excludeabstract exclude abstract classes
     * @return string[] list of full class names in given namespace
     */
    public static function get_namespace_classes($namespace, $instanceof = null, $component = null, $excludeabstract = true) {
        $cached_used = self::init();

        if (!during_initial_install()) {
            $hash_source = $namespace . $instanceof . $component . (int)$excludeabstract;
            $key = md5($hash_source);

            $cache = \cache::make('core', 'namespace_classes');
            if ($cached_used && $classes = $cache->get($key)) {
                return $classes;
            }
        }

        $interface = null;
        if ($instanceof) {
            $instanceof = ltrim($instanceof, '\\'); // Normalise the class/interface name.
            if (class_exists($instanceof, true)) {
                $interface = false;
            } else if (interface_exists($instanceof, true)) {
                $interface = true;
            } else {
                debugging('Invalid $instanceof parameter, it must be a name of class or interface: ' . $instanceof, DEBUG_DEVELOPER);
                return array();
            }
        }

        if ($component) {
            $match = '/^' . preg_quote($component) .'\\\\' . preg_quote($namespace) . '\\\\[^\\\\]+$/';
            $quickmatch = $component . '\\' . $namespace . '\\';
        } else {
            $match = '/^[^\\\\]+\\\\' . preg_quote($namespace) . '\\\\[^\\\\]+$/';
            $quickmatch = '\\' . $namespace . '\\';
        }

        $classes = array();
        foreach (self::$classmap as $class => $unused) {
            if (strpos($class, $quickmatch) === false) {
                // There are very many classes in Totara, this should be faster than regrex
                // because only a small portion of classes is returned here.
                continue;
            }
            if (!preg_match($match, $class)) {
                continue;
            }
            if (!class_exists($class, true)) {
                // Most likely an interface.
                continue;
            }
            $rc = new ReflectionClass($class);
            if ($excludeabstract and $rc->isAbstract()) {
                // This is intended to exclude base classes and helpers.
                continue;
            }
            if ($instanceof) {
                if ($instanceof === $class) {
                    // Exact match is fine, base classes are filtered out by $excludeabstract.
                } else if ($interface) {
                    if (!$rc->implementsInterface($instanceof)) {
                        continue;
                    }
                } else {
                    if (!$rc->isSubclassOf($instanceof)) {
                        continue;
                    }
                }
            }
            $classes[] = $class;
        }

        if (!during_initial_install()) {
            $cache->set($key, $classes);
        }

        return $classes;
    }

    /**
     * Finds all the classes matching the given namespace, and returns them organised by frankenstyle component.
     *
     * Additionally, instanceof can be used to further reduce the returned classes to just those of a given type.
     *
     * This method should be used instead of calling get_namespace_classes iteratively for either all, or a selection of
     * plugins and components.
     * @see get_namespace_classes
     *
     * @since Totara 16.0, 15.2, 14.7 (Dec 2021)
     * @param string $namespace class namespace, this cannot include the name of the plugin, for example 'rb\display'
     * @param string $instanceof full name of class or interface that returned classes should be extending/implementing
     * @param bool $excludeabstract exclude abstract classes
     * @return string[][] Key is the frankenstyle name, and value is an array of classes matching the given namespace
     */
    public static function get_namespace_classes_grouped_by_component($namespace, $instanceof = null, $excludeabstract = true) {
        $classes = core_component::get_namespace_classes(
            $namespace,
            $instanceof,
            null,
            $excludeabstract
        );
        $return = [];
        foreach ($classes as $class) {
            // The regular expression here is taken from the clean_param handling for PARAM_COMPONENT.
            if (!preg_match('/^([a-z]+(_[a-z][a-z0-9_]*)?[a-z0-9]+)\\\\/', $class, $matches)) {
                debugging('Class name does not conform to component namespace, ' . $class, DEBUG_DEVELOPER);
            }
            $component = $matches[1];
            if (!isset($return[$component])) {
                $return[$component] = [$class];
            } else {
                $return[$component][] = $class;
            }
        }
        return $return;
    }

    /**
     * Get a list of all the plugins of a given type that contain a particular file.
     *
     * @param string $plugintype the type of plugin, e.g. 'mod' or 'report'.
     * @param string $file the name of file that must be present in the plugin.
     *                     (e.g. 'view.php', 'db/install.xml').
     * @param bool $include if true (default false), the file will be include_once-ed if found.
     * @return array with plugin name as keys (e.g. 'forum', 'courselist') and the path
     *               to the file relative to dirroot as value (e.g. "$CFG->dirroot/mod/forum/view.php").
     */
    public static function get_plugin_list_with_file($plugintype, $file, $include = false) {
        global $CFG; // Necessary in case it is referenced by included PHP scripts.
        $pluginfiles = array();

        if (isset(self::$filemap[$file])) {
            // If the file was supposed to be mapped, then it should have been set in the array.
            if (isset(self::$filemap[$file][$plugintype])) {
                $pluginfiles = self::$filemap[$file][$plugintype];
            }
        } else {
            // Old-style search for non-cached files.
            $plugins = self::get_plugin_list($plugintype);
            foreach ($plugins as $plugin => $fulldir) {
                $path = $fulldir . '/' . $file;
                if (file_exists($path)) {
                    $pluginfiles[$plugin] = $path;
                }
            }
        }

        if ($include) {
            foreach ($pluginfiles as $path) {
                include_once($path);
            }
        }

        return $pluginfiles;
    }

    /**
     * Returns all classes in a component matching the provided namespace.
     *
     * It checks that the class exists.
     *
     * e.g. get_component_classes_in_namespace('mod_forum', 'event')
     *
     * @param string|null $component A valid moodle component (frankenstyle) or null if searching all components
     * @param string $namespace Namespace from the component name or empty string if all $component classes.
     * @return array array The full class name as key and the class path as value, empty array if $component is `null` and $namespace is empty.
     */
    public static function get_component_classes_in_namespace($component = null, $namespace = '') {

        // debugging('get_component_classes_in_namespace has been deprecated, please call core_component::get_namespace_classes instead.', DEBUG_DEVELOPER);
        // Totara: To convert you would do this:
        //
        //     \core_component::get_component_classes_in_namespace('core_course', 'search');
        //            =
        //     \core_component::get_namespace_classes('search', null, 'core_course');
        //
        // Please note that the return formats are different.

        $classes = array();

        // Only look for components if a component name is set or a namespace is set.
        if (isset($component) || !empty($namespace)) {

            // If a component parameter value is set we only want to look in that component.
            // Otherwise we want to check all components.
            $component = (isset($component)) ? self::normalize_componentname($component) : '\w+';
            if ($namespace) {

                // We will add them later.
                $namespace = trim($namespace, '\\');

                // We need add double backslashes as it is how classes are stored into self::$classmap.
                $namespace = implode('\\\\', explode('\\', $namespace));
                $namespace = $namespace . '\\\\';
            }
            $regex = '|^' . $component . '\\\\' . $namespace . '|';
            $it = new RegexIterator(new ArrayIterator(self::$classmap), $regex, RegexIterator::GET_MATCH, RegexIterator::USE_KEY);

            // We want to be sure that they exist.
            foreach ($it as $classname => $classpath) {
                if (class_exists($classname)) {
                    $classes[$classname] = $classpath;
                }
            }
        }

        return $classes;
    }

    /**
     * Returns the exact absolute path to plugin directory.
     *
     * @param string $plugintype type of plugin
     * @param string $pluginname name of the plugin
     * @return string full path to plugin directory; null if not found
     */
    public static function get_plugin_directory($plugintype, $pluginname) {
        global $CFG;

        if (empty($pluginname)) {
            // Invalid plugin name, sorry.
            return null;
        }

        self::init();

        // Hack for rb_source language files.
        if ($plugintype == 'rb_source') {
            require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');
            foreach (reportbuilder::find_source_dirs() as $dir) {
                if (file_exists($dir.$plugintype.'_'.$pluginname.'.php')) {
                    return $dir;
                }
            }
        }

        if (!isset(self::$plugins[$plugintype][$pluginname])) {
            return null;
        }
        return self::$plugins[$plugintype][$pluginname];
    }

    /**
     * Returns the exact absolute path to plugin directory.
     *
     * @param string $subsystem type of core subsystem
     * @return string full path to subsystem directory; null if not found
     */
    public static function get_subsystem_directory($subsystem) {
        self::init();

        if (!isset(self::$subsystems[$subsystem])) {
            return null;
        }
        return self::$subsystems[$subsystem];
    }

    /**
     * This method validates a plug name. It is much faster than calling clean_param.
     *
     * @param string $plugintype type of plugin
     * @param string $pluginname a string that might be a plugin name.
     * @return bool if this string is a valid plugin name.
     */
    public static function is_valid_plugin_name($plugintype, $pluginname) {
        // Totara: $pluginname is expected to be a string
        $pluginname = $pluginname ?? '';

        if ($plugintype === 'mod') {
            // Modules must not have the same name as core subsystems.
            if (!isset(self::$subsystems)) {
                // Watch out, this is called from init!
                self::init();
            }
            if (isset(self::$subsystems[$pluginname])) {
                return false;
            }
            // Modules MUST NOT have any underscores,
            // component normalisation would break very badly otherwise!
            return (bool)preg_match('/^[a-z][a-z0-9]*$/', $pluginname);

        } else {
            return (bool)preg_match('/^[a-z](?:[a-z0-9_](?!__))*[a-z0-9]+$/', $pluginname);
        }
    }

    /**
     * Normalize the component name.
     *
     * Note: this does not verify the validity of the plugin or component.
     *
     * @param string $component
     * @return string
     */
    public static function normalize_componentname($componentname) {
        list($plugintype, $pluginname) = self::normalize_component($componentname);
        if ($plugintype === 'core' && is_null($pluginname)) {
            return $plugintype;
        }
        return $plugintype . '_' . $pluginname;
    }

    /**
     * Normalize the component name using the "frankenstyle" rules.
     *
     * Note: this does not verify the validity of plugin or type names.
     *
     * @param string $component
     * @return array two-items list of [(string)type, (string|null)name]
     */
    public static function normalize_component($component) {
        if (!isset(self::$normalised_component_map[$component])) {
            if (strpos($component, '_') === false) {
                self::init();
                if (array_key_exists($component, self::$subsystems)) {
                    $type   = 'core';
                    $plugin = $component;
                } else {
                    // Everything else without underscore is a module.
                    $type   = 'mod';
                    $plugin = $component;
                }
            } else if (strpos($component, 'rb_source_') === 0) {
                $type = 'rb_source';
                $plugin = substr($component, 10);
            } else {
                list($type, $plugin) = explode('_', $component, 2);
                if ($type === 'moodle') {
                    $type = 'core';
                }
                // Any unknown type must be a subplugin.
            }

            self::$normalised_component_map[$component] = [$type, $plugin];
        }
        return self::$normalised_component_map[$component];
    }

    /**
     * Return exact absolute path to a plugin directory.
     *
     * @param string $component name such as 'moodle', 'mod_forum'
     * @return string full path to component directory; NULL if not found
     */
    public static function get_component_directory($component) {
        global $CFG;

        list($type, $plugin) = self::normalize_component($component);

        if ($type === 'core') {
            if ($plugin === null) {
                return $path = $CFG->libdir;
            }
            return self::get_subsystem_directory($plugin);
        }

        return self::get_plugin_directory($type, $plugin);
    }

    /**
     * Returns list of plugin types that allow subplugins.
     * @return array as (string)plugintype => (string)fulldir
     */
    public static function get_plugin_types_with_subplugins() {
        self::init();

        $return = array();
        foreach (self::$supportsubplugins as $type) {
            $return[$type] = self::$plugintypes[$type];
        }
        return $return;
    }

    /**
     * Returns parent of this subplugin type.
     *
     * @param string $type
     * @return string parent component or null
     */
    public static function get_subtype_parent($type) {
        self::init();

        if (isset(self::$parents[$type])) {
            return self::$parents[$type];
        }

        return null;
    }

    /**
     * Return all subplugins of this component.
     * @param string $component.
     * @return array $subtype=>array($component, ..), null if no subtypes defined
     */
    public static function get_subplugins($component) {
        self::init();

        if (isset(self::$subplugins[$component])) {
            return self::$subplugins[$component];
        }

        return null;
    }

    /**
     * Returns hash of all versions including core and all plugins.
     *
     * This is relatively slow and not fully cached, use with care!
     *
     * @return string sha1 hash
     */
    public static function get_all_versions_hash() {
        global $CFG;

        self::init();

        $versions = array();

        // Main version first.
        $versions['core'] = self::fetch_core_version();
        $versions['totarabuild'] = self::fetch_totarabuild();

        // The problem here is tha the component cache might be stable,
        // we want this to work also on frontpage without resetting the component cache.
        $usecache = false;
        if (CACHE_DISABLE_ALL or (defined('IGNORE_COMPONENT_CACHE') and IGNORE_COMPONENT_CACHE)) {
            $usecache = true;
        }

        // Now all plugins.
        $plugintypes = core_component::get_plugin_types();
        foreach ($plugintypes as $type => $typedir) {
            if ($usecache) {
                $plugs = core_component::get_plugin_list($type);
            } else {
                $plugs = self::fetch_plugins($type, $typedir);
            }
            foreach ($plugs as $plug => $fullplug) {
                $plugin = core_component::load_version_file($fullplug.'/version.php');
                $versions[$type.'_'.$plug] = $plugin->version;
            }
        }

        return sha1(serialize($versions));
    }

    /**
     * Load data from the provided version file.
     *
     * @param string $filepath
     * @return array
     */
    private static function load_version_file(string $filepath) {
        $plugin = new stdClass();
        $plugin->version = null;
        $module = $plugin;
        include($filepath);
        return $plugin;
    }

    /**
     * Invalidate opcode cache for given file, this is intended for
     * php files that are stored in dataroot.
     *
     * Note: we need it here because this class must be self-contained.
     *
     * @param string $file
     */
    public static function invalidate_opcode_php_cache($file) {
        if (function_exists('opcache_invalidate')) {
            if (!file_exists($file)) {
                return;
            }
            opcache_invalidate($file, true);
        }
    }

    /**
     * Return true if subsystemname is core subsystem.
     *
     * @param string $subsystemname name of the subsystem.
     * @return bool true if core subsystem.
     */
    public static function is_core_subsystem($subsystemname) {
        return isset(self::$subsystems[$subsystemname]);
    }

    /**
     * Records all class renames that have been made to facilitate autoloading.
     */
    protected static function fill_classmap_renames_cache() {
        global $CFG;

        self::$classmaprenames = array();

        self::load_renamed_classes("$CFG->dirroot/lib/");

        foreach (self::$subsystems as $subsystem => $fulldir) {
            self::load_renamed_classes($fulldir);
        }

        foreach (self::$plugins as $plugintype => $plugins) {
            foreach ($plugins as $pluginname => $fulldir) {
                self::load_renamed_classes($fulldir);
            }
        }
    }

    /**
     * Loads the db/renamedclasses.php file from the given directory.
     *
     * The renamedclasses.php should contain a key => value array ($renamedclasses) where the key is old class name,
     * and the value is the new class name.
     * It is only included when we are populating the component cache. After that is not needed.
     *
     * @param string $fulldir
     */
    protected static function load_renamed_classes($fulldir) {
        $file = $fulldir . '/db/renamedclasses.php';
        if (is_readable($file)) {
            $renamedclasses = null;
            require($file);
            if (is_array($renamedclasses)) {
                foreach ($renamedclasses as $oldclass => $newclass) {
                    self::$classmaprenames[(string)$oldclass] = (string)$newclass;
                }
            }
        }
    }

    /**
     * Returns a list of frankenstyle component names and their paths, for all components (plugins and subsystems).
     *
     * E.g.
     *  [
     *      'mod' => [
     *          'mod_forum' => FORUM_PLUGIN_PATH,
     *          ...
     *      ],
     *      ...
     *      'core' => [
     *          'core_comment' => COMMENT_SUBSYSTEM_PATH,
     *          ...
     *      ]
     * ]
     *
     * @return array an associative array of components and their corresponding paths.
     */
    public static function get_component_list() : array {
        $components = [];
        // Get all plugins.
        foreach (self::get_plugin_types() as $plugintype => $typedir) {
            $components[$plugintype] = [];
            foreach (self::get_plugin_list($plugintype) as $pluginname => $plugindir) {
                $components[$plugintype][$plugintype . '_' . $pluginname] = $plugindir;
            }
        }
        // Get all subsystems.
        foreach (self::get_core_subsystems() as $subsystemname => $subsystempath) {
            $components['core']['core_' . $subsystemname] = $subsystempath;
        }
        return $components;
    }
}
