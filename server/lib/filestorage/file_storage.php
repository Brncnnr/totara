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
 * Core file storage class definition.
 *
 * @package   core_files
 * @copyright 2008 Petr Skoda {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/filestorage/stored_file.php");

/**
 * File storage class used for low level access to stored files.
 *
 * Only owner of file area may use this class to access own files,
 * for example only code in mod/assignment/* may access assignment
 * attachments. When some other part of moodle needs to access
 * files of modules it has to use file_browser class instead or there
 * has to be some callback API.
 *
 * @package   core_files
 * @category  files
 * @copyright 2008 Petr Skoda {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.0
 */
class file_storage {
    /** @var string Directory with file contents */
    private $filedir;
    /** @var string Contents of deleted files not needed any more */
    private $trashdir;
    /** @var string tempdir */
    private $tempdir;
    /** @var int Permissions for new directories */
    private $dirpermissions;
    /** @var int Permissions for new files */
    private $filepermissions;
    /** @var array List of formats supported by unoconv */
    private $unoconvformats;

    // Unoconv constants.
    /** No errors */
    const UNOCONVPATH_OK = 'ok';
    /** Not set */
    const UNOCONVPATH_EMPTY = 'empty';
    /** Does not exist */
    const UNOCONVPATH_DOESNOTEXIST = 'doesnotexist';
    /** Is a dir */
    const UNOCONVPATH_ISDIR = 'isdir';
    /** Not executable */
    const UNOCONVPATH_NOTEXECUTABLE = 'notexecutable';
    /** Test file missing */
    const UNOCONVPATH_NOTESTFILE = 'notestfile';
    /** Version not supported */
    const UNOCONVPATH_VERSIONNOTSUPPORTED = 'versionnotsupported';
    /** Any other error */
    const UNOCONVPATH_ERROR = 'error';


    /**
     * Constructor - do not use directly use {@link get_file_storage()} call instead.
     *
     * @param string $filedir full path to pool directory
     * @param string $trashdir temporary storage of deleted area
     * @param string $tempdir temporary storage of various files
     * @param int $dirpermissions new directory permissions
     * @param int $filepermissions new file permissions
     */
    public function __construct($filedir, $trashdir, $tempdir, $dirpermissions, $filepermissions) {
        global $CFG;

        $this->filedir         = $filedir;
        $this->trashdir        = $trashdir;
        $this->tempdir         = $tempdir;
        $this->dirpermissions  = $dirpermissions;
        $this->filepermissions = $filepermissions;

        // make sure the file pool directory exists
        if (!is_dir($this->filedir)) {
            if (!mkdir($this->filedir, $this->dirpermissions, true)) {
                throw new file_exception('storedfilecannotcreatefiledirs'); // permission trouble
            }
            // place warning file in file pool root
            if (!file_exists($this->filedir.'/warning.txt')) {
                file_put_contents($this->filedir.'/warning.txt',
                                  'This directory contains the content of uploaded files and is controlled by Moodle code. Do not manually move, change or rename any of the files and subdirectories here.');
                chmod($this->filedir.'/warning.txt', $CFG->filepermissions);
            }
        }
        // make sure the file pool directory exists
        if (!is_dir($this->trashdir)) {
            if (!mkdir($this->trashdir, $this->dirpermissions, true)) {
                throw new file_exception('storedfilecannotcreatefiledirs'); // permission trouble
            }
        }
    }

    /**
     * Calculates sha1 hash of unique full path name information.
     *
     * This hash is a unique file identifier - it is used to improve
     * performance and overcome db index size limits.
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param string $filename file name
     * @return string sha1 hash
     */
    public static function get_pathname_hash($contextid, $component, $filearea, $itemid, $filepath, $filename) {
        return sha1("/$contextid/$component/$filearea/$itemid".$filepath.$filename);
    }

    /**
     * Does this file exist?
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param string $filename file name
     * @return bool
     */
    public function file_exists($contextid, $component, $filearea, $itemid, $filepath, $filename) {
        $filepath = clean_param($filepath, PARAM_PATH);
        $filename = clean_param($filename, PARAM_FILE);

        if ($filename === '') {
            $filename = '.';
        }

        $pathnamehash = $this->get_pathname_hash($contextid, $component, $filearea, $itemid, $filepath, $filename);
        return $this->file_exists_by_hash($pathnamehash);
    }

    /**
     * Whether or not the file exist
     *
     * @param string $pathnamehash path name hash
     * @return bool
     */
    public function file_exists_by_hash($pathnamehash) {
        global $DB;

        return $DB->record_exists('files', array('pathnamehash'=>$pathnamehash));
    }

    /**
     * Create instance of file class from database record.
     *
     * @param stdClass $filerecord record from the files table left join files_reference table
     * @return stored_file instance of file abstraction class
     */
    public function get_file_instance(stdClass $filerecord) {
        $storedfile = new stored_file($this, $filerecord, $this->filedir);
        return $storedfile;
    }

    /**
     * Get converted document.
     *
     * Get an alternate version of the specified document, if it is possible to convert.
     *
     * @param stored_file $file the file we want to preview
     * @param string $format The desired format - e.g. 'pdf'. Formats are specified by file extension.
     * @param boolean $forcerefresh If true, the file will be converted every time (not cached).
     * @return stored_file|bool false if unable to create the conversion, stored file otherwise
     */
    public function get_converted_document(stored_file $file, $format, $forcerefresh = false) {

        // Totara: it is not secure to use unoconv on servers!
        return false;

        $context = context_system::instance();
        $path = '/' . $format . '/';
        $conversion = $this->get_file($context->id, 'core', 'documentconversion', 0, $path, $file->get_contenthash());

        if (!$conversion || $forcerefresh) {
            $conversion = $this->create_converted_document($file, $format, $forcerefresh);
            if (!$conversion) {
                return false;
            }
        }

        return $conversion;
    }

    /**
     * Verify the format is supported.
     *
     * @param string $format The desired format - e.g. 'pdf'. Formats are specified by file extension.
     * @return bool - True if the format is supported for input.
     */
    protected function is_format_supported_by_unoconv($format) {
        global $CFG;

        // Totara: it is not secure to use unoconv on servers!
        return false;

        if (!isset($this->unoconvformats)) {
            // Ask unoconv for it's list of supported document formats.
            $cmd = escapeshellcmd(trim($CFG->pathtounoconv)) . ' --show';
            $pipes = array();
            $pipesspec = array(2 => array('pipe', 'w'));
            $proc = proc_open($cmd, $pipesspec, $pipes);
            $programoutput = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            proc_close($proc);
            $matches = array();
            preg_match_all('/\[\.(.*)\]/', $programoutput, $matches);

            $this->unoconvformats = $matches[1];
            $this->unoconvformats = array_unique($this->unoconvformats);
        }

        $sanitized = trim(core_text::strtolower($format));
        return in_array($sanitized, $this->unoconvformats);
    }

    /**
     * Check if the installed version of unoconv is supported.
     *
     * @return bool true if the present version is supported, false otherwise.
     */
    public static function can_convert_documents() {
        global $CFG;

        // Totara: it is not secure to use unoconv on servers!
        return false;

        $currentversion = 0;
        $supportedversion = 0.7;
        $unoconvbin = \escapeshellarg($CFG->pathtounoconv);
        $command = "$unoconvbin --version";
        exec($command, $output);
        // If the command execution returned some output, then get the unoconv version.
        if ($output) {
            foreach ($output as $response) {
                if (preg_match('/unoconv (\\d+\\.\\d+)/', $response, $matches)) {
                    $currentversion = (float)$matches[1];
                }
            }
            if ($currentversion < $supportedversion) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Regenerate the test pdf and send it direct to the browser.
     */
    public static function send_test_pdf() {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        // Totara: it is not secure to use unoconv on servers!
        exit(1);

        $filerecord = array(
            'contextid' => \context_system::instance()->id,
            'component' => 'test',
            'filearea' => 'assignfeedback_editpdf',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'unoconv_test.docx'
        );

        // Get the fixture doc file content and generate and stored_file object.
        $fs = get_file_storage();
        $fixturefile = $CFG->libdir . '/tests/fixtures/unoconv-source.docx';
        $fixturedata = file_get_contents($fixturefile);
        $testdocx = $fs->get_file($filerecord['contextid'], $filerecord['component'], $filerecord['filearea'],
                $filerecord['itemid'], $filerecord['filepath'], $filerecord['filename']);
        if (!$testdocx) {
            $testdocx = $fs->create_file_from_string($filerecord, $fixturedata);

        }

        // Convert the doc file to pdf and send it direct to the browser.
        $result = $fs->get_converted_document($testdocx, 'pdf', true);
        readfile_accel($result, 'application/pdf', true);
    }

    /**
     * Check if unoconv configured path is correct and working.
     *
     * @return \stdClass an object with the test status and the UNOCONVPATH_ constant message.
     */
    public static function test_unoconv_path() {
        global $CFG;
        $unoconvpath = $CFG->pathtounoconv;

        $ret = new \stdClass();
        $ret->status = self::UNOCONVPATH_OK;
        $ret->message = null;

        if (empty($unoconvpath)) {
            $ret->status = self::UNOCONVPATH_EMPTY;
            return $ret;
        }
        if (!file_exists($unoconvpath)) {
            $ret->status = self::UNOCONVPATH_DOESNOTEXIST;
            return $ret;
        }
        if (is_dir($unoconvpath)) {
            $ret->status = self::UNOCONVPATH_ISDIR;
            return $ret;
        }
        if (!file_is_executable($unoconvpath)) {
            $ret->status = self::UNOCONVPATH_NOTEXECUTABLE;
            return $ret;
        }
        if (!\file_storage::can_convert_documents()) {
            $ret->status = self::UNOCONVPATH_VERSIONNOTSUPPORTED;
            return $ret;
        }

        return $ret;
    }

    /**
     * Perform a file format conversion on the specified document.
     *
     * @param stored_file $file the file we want to preview
     * @param string $format The desired format - e.g. 'pdf'. Formats are specified by file extension.
     * @return stored_file|bool false if unable to create the conversion, stored file otherwise
     */
    protected function create_converted_document(stored_file $file, $format, $forcerefresh = false) {
        global $CFG;

        // Totara: it is not secure to use unoconv on servers!
        return false;

        if (empty($CFG->pathtounoconv) || !file_is_executable(trim($CFG->pathtounoconv))) {
            // No conversions are possible, sorry.
            return false;
        }

        $fileextension = core_text::strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        if (!self::is_format_supported_by_unoconv($fileextension)) {
            return false;
        }

        if (!self::is_format_supported_by_unoconv($format)) {
            return false;
        }

        // Copy the file to the tmp dir.
        $uniqdir = "core_file/conversions/" . uniqid($file->get_id() . "-", true);
        $tmp = make_temp_directory($uniqdir);
        $ext = pathinfo($file->get_filename(), PATHINFO_EXTENSION);
        // Safety.
        $localfilename = $file->get_id() . '.' . $ext;

        $filename = $tmp . '/' . $localfilename;
        try {
            // This function can either return false, or throw an exception so we need to handle both.
            if ($file->copy_content_to($filename) === false) {
                throw new file_exception('storedfileproblem', 'Could not copy file contents to temp file.');
            }
        } catch (file_exception $fe) {
            remove_dir($tmp);
            throw $fe;
        }

        $newtmpfile = pathinfo($filename, PATHINFO_FILENAME) . '.' . $format;

        // Safety.
        $newtmpfile = $tmp . '/' . clean_param($newtmpfile, PARAM_FILE);

        $cmd = escapeshellcmd(trim($CFG->pathtounoconv)) . ' ' .
               escapeshellarg('-f') . ' ' .
               escapeshellarg($format) . ' ' .
               escapeshellarg('-o') . ' ' .
               escapeshellarg($newtmpfile) . ' ' .
               escapeshellarg($filename);

        $output = null;
        $currentdir = getcwd();
        chdir($tmp);
        $result = exec($cmd, $output);
        chdir($currentdir);
        touch($newtmpfile);
        if (filesize($newtmpfile) === 0) {
            remove_dir($tmp);
            // Cleanup.
            return false;
        }

        $context = context_system::instance();
        $path = '/' . $format . '/';
        $record = array(
            'contextid' => $context->id,
            'component' => 'core',
            'filearea'  => 'documentconversion',
            'itemid'    => 0,
            'filepath'  => $path,
            'filename'  => $file->get_contenthash(),
        );

        if ($forcerefresh) {
            $existing = $this->get_file($context->id, 'core', 'documentconversion', 0, $path, $file->get_contenthash());
            if ($existing) {
                $existing->delete();
            }
        }

        $convertedfile = $this->create_file_from_pathname($record, $newtmpfile);
        // Cleanup.
        remove_dir($tmp);
        return $convertedfile;
    }

    /**
     * Returns an image file that represent the given stored file as a preview
     *
     * At the moment, only GIF, JPEG and PNG files are supported to have previews. In the
     * future, the support for other mimetypes can be added, too (eg. generate an image
     * preview of PDF, text documents etc).
     *
     * @param stored_file $file the file we want to preview
     * @param string $mode preview mode, eg. 'thumb'
     * @return stored_file|bool false if unable to create the preview, stored file otherwise
     *
     * @deprecated since Totara 13.0
     */
    public function get_file_preview(stored_file $file, $mode) {
        debugging(
            "Function '" . __FUNCTION__ . "' has been deprecated, please use ",
            "\core\image\preview_helper::get_file_preview instead",
            DEBUG_DEVELOPER
        );

        $helper = \core\image\preview_helper::instance();
        $stored_file = $helper->get_file_preview($file, $mode);

        if ($stored_file === null) {
            return false;
        }

        return $stored_file;
    }

    /**
     * Return an available file name.
     *
     * This will return the next available file name in the area, adding/incrementing a suffix
     * of the file, ie: file.txt > file (1).txt > file (2).txt > etc...
     *
     * If the file name passed is available without modification, it is returned as is.
     *
     * @param int $contextid context ID.
     * @param string $component component.
     * @param string $filearea file area.
     * @param int $itemid area item ID.
     * @param string $filepath the file path.
     * @param string $filename the file name.
     * @return string available file name.
     * @throws coding_exception if the file name is invalid.
     * @since Moodle 2.5
     */
    public function get_unused_filename($contextid, $component, $filearea, $itemid, $filepath, $filename) {
        global $DB;

        // Do not accept '.' or an empty file name (zero is acceptable).
        if ($filename == '.' || (empty($filename) && !is_numeric($filename))) {
            throw new coding_exception('Invalid file name passed', $filename);
        }

        // The file does not exist, we return the same file name.
        if (!$this->file_exists($contextid, $component, $filearea, $itemid, $filepath, $filename)) {
            return $filename;
        }

        // Trying to locate a file name using the used pattern. We remove the used pattern from the file name first.
        $pathinfo = pathinfo($filename);
        $basename = $pathinfo['filename'];
        $matches = array();
        if (preg_match('~^(.+) \(([0-9]+)\)$~', $basename, $matches)) {
            $basename = $matches[1];
        }

        $filenamelike = $DB->sql_like_escape($basename) . ' (%)';
        if (isset($pathinfo['extension'])) {
            $filenamelike .= '.' . $DB->sql_like_escape($pathinfo['extension']);
        }

        $filenamelikesql = $DB->sql_like('f.filename', ':filenamelike');
        $filenamelen = $DB->sql_length('f.filename');
        $sql = "SELECT filename
                FROM {files} f
                WHERE
                    f.contextid = :contextid AND
                    f.component = :component AND
                    f.filearea = :filearea AND
                    f.itemid = :itemid AND
                    f.filepath = :filepath AND
                    $filenamelikesql
                ORDER BY
                    $filenamelen DESC,
                    f.filename DESC";
        $params = array('contextid' => $contextid, 'component' => $component, 'filearea' => $filearea, 'itemid' => $itemid,
                'filepath' => $filepath, 'filenamelike' => $filenamelike);
        $results = $DB->get_fieldset_sql($sql, $params, IGNORE_MULTIPLE);

        // Loop over the results to make sure we are working on a valid file name. Because 'file (1).txt' and 'file (copy).txt'
        // would both be returned, but only the one only containing digits should be used.
        $number = 1;
        foreach ($results as $result) {
            $resultbasename = pathinfo($result, PATHINFO_FILENAME);
            $matches = array();
            if (preg_match('~^(.+) \(([0-9]+)\)$~', $resultbasename, $matches)) {
                $number = $matches[2] + 1;
                break;
            }
        }

        // Constructing the new filename.
        $newfilename = $basename . ' (' . $number . ')';
        if (isset($pathinfo['extension'])) {
            $newfilename .= '.' . $pathinfo['extension'];
        }

        return $newfilename;
    }

    /**
     * Return an available directory name.
     *
     * This will return the next available directory name in the area, adding/incrementing a suffix
     * of the last portion of path, ie: /path/ > /path (1)/ > /path (2)/ > etc...
     *
     * If the file path passed is available without modification, it is returned as is.
     *
     * @param int $contextid context ID.
     * @param string $component component.
     * @param string $filearea file area.
     * @param int $itemid area item ID.
     * @param string $suggestedpath the suggested file path.
     * @return string available file path
     * @since Moodle 2.5
     */
    public function get_unused_dirname($contextid, $component, $filearea, $itemid, $suggestedpath) {
        global $DB;

        // Ensure suggestedpath has trailing '/'
        $suggestedpath = rtrim($suggestedpath, '/'). '/';

        // The directory does not exist, we return the same file path.
        if (!$this->file_exists($contextid, $component, $filearea, $itemid, $suggestedpath, '.')) {
            return $suggestedpath;
        }

        // Trying to locate a file path using the used pattern. We remove the used pattern from the path first.
        if (preg_match('~^(/.+) \(([0-9]+)\)/$~', $suggestedpath, $matches)) {
            $suggestedpath = $matches[1]. '/';
        }

        $filepathlike = $DB->sql_like_escape(rtrim($suggestedpath, '/')) . ' (%)/';

        $filepathlikesql = $DB->sql_like('f.filepath', ':filepathlike');
        $filepathlen = $DB->sql_length('f.filepath');
        $sql = "SELECT filepath
                FROM {files} f
                WHERE
                    f.contextid = :contextid AND
                    f.component = :component AND
                    f.filearea = :filearea AND
                    f.itemid = :itemid AND
                    f.filename = :filename AND
                    $filepathlikesql
                ORDER BY
                    $filepathlen DESC,
                    f.filepath DESC";
        $params = array('contextid' => $contextid, 'component' => $component, 'filearea' => $filearea, 'itemid' => $itemid,
                'filename' => '.', 'filepathlike' => $filepathlike);
        $results = $DB->get_fieldset_sql($sql, $params, IGNORE_MULTIPLE);

        // Loop over the results to make sure we are working on a valid file path. Because '/path (1)/' and '/path (copy)/'
        // would both be returned, but only the one only containing digits should be used.
        $number = 1;
        foreach ($results as $result) {
            if (preg_match('~ \(([0-9]+)\)/$~', $result, $matches)) {
                $number = (int)($matches[1]) + 1;
                break;
            }
        }

        return rtrim($suggestedpath, '/'). ' (' . $number . ')/';
    }

    /**
     * Generates a preview image for the stored file
     *
     * @param stored_file $file the file we want to preview
     * @param string $mode preview mode, eg. 'thumb'
     * @return stored_file|bool the newly created preview file or false
     *
     * @deprecated since Totara 13.0
     */
    protected function create_file_preview(stored_file $file, $mode) {
        debugging(
            "Function '" . __FUNCTION__ . "' has been deprecated, please use " .
            "\core\image\preview_helper::create_file_preview instead",
            DEBUG_DEVELOPER
        );

        $helper = \core\image\preview_helper::instance();
        $stored_file = $helper->create_file_preview($file, $mode);

        if ($stored_file === null) {
            return false;
        }

        return $stored_file;
    }

    /**
     * Generates a preview for the stored image file
     *
     * @param stored_file $file the image we want to preview
     * @param string $mode preview mode, eg. 'thumb'
     * @return string|bool false if a problem occurs, the thumbnail image data otherwise
     *
     * @deprecated since Totara 13.0
     */
    protected function create_imagefile_preview(stored_file $file, $mode) {
        debugging(
            "Function '" . __FUNCTION__ . "' has been deprecated, please use " .
            "'\core\image\preview_helper::get_preview_content' instead",
            DEBUG_DEVELOPER
        );

        $helper = \core\image\preview_helper::instance();
        $content = $helper->get_preview_content($file, $mode);

        if ($content === null) {
            return false;
        }

        return $content;
    }

    /**
     * Fetch file using local file id.
     *
     * Please do not rely on file ids, it is usually easier to use
     * pathname hashes instead.
     *
     * @param int $fileid file ID
     * @return stored_file|bool stored_file instance if exists, false if not
     */
    public function get_file_by_id($fileid) {
        global $DB;

        $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                  FROM {files} f
             LEFT JOIN {files_reference} r
                       ON f.referencefileid = r.id
                 WHERE f.id = ?";
        if ($filerecord = $DB->get_record_sql($sql, array($fileid))) {
            return $this->get_file_instance($filerecord);
        } else {
            return false;
        }
    }

    /**
     * Fetch file using local file full pathname hash
     *
     * @param string $pathnamehash path name hash
     * @return stored_file|bool stored_file instance if exists, false if not
     */
    public function get_file_by_hash($pathnamehash) {
        global $DB;

        $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                  FROM {files} f
             LEFT JOIN {files_reference} r
                       ON f.referencefileid = r.id
                 WHERE f.pathnamehash = ?";
        if ($filerecord = $DB->get_record_sql($sql, array($pathnamehash))) {
            return $this->get_file_instance($filerecord);
        } else {
            return false;
        }
    }

    /**
     * Fetch locally stored file.
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param string $filename file name
     * @return stored_file|bool stored_file instance if exists, false if not
     */
    public function get_file($contextid, $component, $filearea, $itemid, $filepath, $filename) {
        $filepath = clean_param($filepath, PARAM_PATH);
        $filename = clean_param($filename, PARAM_FILE);

        if ($filename === '') {
            $filename = '.';
        }

        $pathnamehash = $this->get_pathname_hash($contextid, $component, $filearea, $itemid, $filepath, $filename);
        return $this->get_file_by_hash($pathnamehash);
    }

    /**
     * Are there any files (or directories)
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param bool|int $itemid item id or false if all items
     * @param bool $ignoredirs whether or not ignore directories
     * @return bool empty
     */
    public function is_area_empty($contextid, $component, $filearea, $itemid = false, $ignoredirs = true) {
        global $DB;

        $params = array('contextid'=>$contextid, 'component'=>$component, 'filearea'=>$filearea);
        $where = "contextid = :contextid AND component = :component AND filearea = :filearea";

        if ($itemid !== false) {
            $params['itemid'] = $itemid;
            $where .= " AND itemid = :itemid";
        }

        if ($ignoredirs) {
            $sql = "SELECT 'x'
                      FROM {files}
                     WHERE $where AND filename <> '.'";
        } else {
            $sql = "SELECT 'x'
                      FROM {files}
                     WHERE $where AND (filename <> '.' OR filepath <> '/')";
        }

        return !$DB->record_exists_sql($sql, $params);
    }

    /**
     * Returns all files belonging to given repository
     *
     * @param int $repositoryid
     * @param string $sort A fragment of SQL to use for sorting
     */
    public function get_external_files($repositoryid, $sort = '') {
        global $DB;
        $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                  FROM {files} f
             LEFT JOIN {files_reference} r
                       ON f.referencefileid = r.id
                 WHERE r.repositoryid = ?";
        if (!empty($sort)) {
            $sql .= " ORDER BY {$sort}";
        }

        $result = array();
        $filerecords = $DB->get_records_sql($sql, array($repositoryid));
        foreach ($filerecords as $filerecord) {
            $result[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
        }
        return $result;
    }

    /**
     * Returns all area files (optionally limited by itemid)
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param mixed $filearea file area/s, you cannot specify multiple fileareas as well as an itemid
     * @param int $itemid item ID or all files if not specified
     * @param string $sort A fragment of SQL to use for sorting
     * @param bool $includedirs whether or not include directories
     * @param int $updatedsince return files updated since this time
     * @return stored_file[] array of stored_files indexed by pathanmehash
     */
    public function get_area_files($contextid, $component, $filearea, $itemid = false, $sort = "itemid, filepath, filename",
                                    $includedirs = true, $updatedsince = 0, $userid = false) {
        global $DB;

        list($areasql, $conditions) = $DB->get_in_or_equal($filearea, SQL_PARAMS_NAMED);
        $conditions['contextid'] = $contextid;
        $conditions['component'] = $component;

        if ($itemid !== false && is_array($filearea)) {
            throw new coding_exception('You cannot specify multiple fileareas as well as an itemid.');
        } else if ($itemid !== false) {
            $itemidsql = ' AND f.itemid = :itemid ';
            $conditions['itemid'] = $itemid;
        } else {
            $itemidsql = '';
        }

        $updatedsincesql = '';
        if (!empty($updatedsince)) {
            $conditions['time'] = $updatedsince;
            $updatedsincesql = 'AND f.timemodified > :time';
        }

        $useridsql = '';
        if ($userid !== false) {
            $useridsql = ' AND f.userid = :userid ';
            $conditions['userid'] = $userid;
        }

        $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                  FROM {files} f
             LEFT JOIN {files_reference} r
                       ON f.referencefileid = r.id
                 WHERE f.contextid = :contextid
                       AND f.component = :component
                       AND f.filearea $areasql
                       $updatedsincesql
                       $itemidsql
                       $useridsql";
        if (!empty($sort)) {
            $sql .= " ORDER BY {$sort}";
        }

        $result = array();
        $filerecords = $DB->get_records_sql($sql, $conditions);
        foreach ($filerecords as $filerecord) {
            if (!$includedirs and $filerecord->filename === '.') {
                continue;
            }
            $result[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
        }
        return $result;
    }

    /**
     * Returns array based tree structure of area files
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @return array each dir represented by dirname, subdirs, files and dirfile array elements
     */
    public function get_area_tree($contextid, $component, $filearea, $itemid) {
        $result = array('dirname'=>'', 'dirfile'=>null, 'subdirs'=>array(), 'files'=>array());
        $files = $this->get_area_files($contextid, $component, $filearea, $itemid, '', true);
        // first create directory structure
        foreach ($files as $hash=>$dir) {
            if (!$dir->is_directory()) {
                continue;
            }
            unset($files[$hash]);
            if ($dir->get_filepath() === '/') {
                $result['dirfile'] = $dir;
                continue;
            }
            $parts = explode('/', trim($dir->get_filepath(),'/'));
            $pointer =& $result;
            foreach ($parts as $part) {
                if ($part === '') {
                    continue;
                }
                if (!isset($pointer['subdirs'][$part])) {
                    $pointer['subdirs'][$part] = array('dirname'=>$part, 'dirfile'=>null, 'subdirs'=>array(), 'files'=>array());
                }
                $pointer =& $pointer['subdirs'][$part];
            }
            $pointer['dirfile'] = $dir;
            unset($pointer);
        }
        foreach ($files as $hash=>$file) {
            $parts = explode('/', trim($file->get_filepath(),'/'));
            $pointer =& $result;
            foreach ($parts as $part) {
                if ($part === '') {
                    continue;
                }
                $pointer =& $pointer['subdirs'][$part];
            }
            $pointer['files'][$file->get_filename()] = $file;
            unset($pointer);
        }
        $result = $this->sort_area_tree($result);
        return $result;
    }

    /**
     * Sorts the result of {@link file_storage::get_area_tree()}.
     *
     * @param array $tree Array of results provided by {@link file_storage::get_area_tree()}
     * @return array of sorted results
     */
    protected function sort_area_tree($tree) {
        foreach ($tree as $key => &$value) {
            if ($key == 'subdirs') {
                core_collator::ksort($value, core_collator::SORT_NATURAL);
                foreach ($value as $subdirname => &$subtree) {
                    $subtree = $this->sort_area_tree($subtree);
                }
            } else if ($key == 'files') {
                core_collator::ksort($value, core_collator::SORT_NATURAL);
            }
        }
        return $tree;
    }

    /**
     * Returns all files and optionally directories
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param int $filepath directory path
     * @param bool $recursive include all subdirectories
     * @param bool $includedirs include files and directories
     * @param string $sort A fragment of SQL to use for sorting
     * @return stored_file[] array of stored_files indexed by pathanmehash
     */
    public function get_directory_files($contextid, $component, $filearea, $itemid, $filepath, $recursive = false, $includedirs = true, $sort = "filepath, filename") {
        global $DB;

        if (!$directory = $this->get_file($contextid, $component, $filearea, $itemid, $filepath, '.')) {
            return array();
        }

        $orderby = (!empty($sort)) ? " ORDER BY {$sort}" : '';

        if ($recursive) {

            $dirs = $includedirs ? "" : "AND filename <> '.'";
            $length = core_text::strlen($filepath);

            $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                      FROM {files} f
                 LEFT JOIN {files_reference} r
                           ON f.referencefileid = r.id
                     WHERE f.contextid = :contextid AND f.component = :component AND f.filearea = :filearea AND f.itemid = :itemid
                           AND ".$DB->sql_substr("f.filepath", 1, $length)." = :filepath
                           AND f.id <> :dirid
                           $dirs
                           $orderby";
            $params = array('contextid'=>$contextid, 'component'=>$component, 'filearea'=>$filearea, 'itemid'=>$itemid, 'filepath'=>$filepath, 'dirid'=>$directory->get_id());

            $files = array();
            $dirs  = array();
            $filerecords = $DB->get_records_sql($sql, $params);
            foreach ($filerecords as $filerecord) {
                if ($filerecord->filename == '.') {
                    $dirs[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
                } else {
                    $files[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
                }
            }
            $result = array_merge($dirs, $files);

        } else {
            $result = array();
            $params = array('contextid'=>$contextid, 'component'=>$component, 'filearea'=>$filearea, 'itemid'=>$itemid, 'filepath'=>$filepath, 'dirid'=>$directory->get_id());

            $length = core_text::strlen($filepath);

            if ($includedirs) {
                $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                          FROM {files} f
                     LEFT JOIN {files_reference} r
                               ON f.referencefileid = r.id
                         WHERE f.contextid = :contextid AND f.component = :component AND f.filearea = :filearea
                               AND f.itemid = :itemid AND f.filename = '.'
                               AND ".$DB->sql_substr("f.filepath", 1, $length)." = :filepath
                               AND f.id <> :dirid
                               $orderby";
                $reqlevel = substr_count($filepath, '/') + 1;
                $filerecords = $DB->get_records_sql($sql, $params);
                foreach ($filerecords as $filerecord) {
                    if (substr_count($filerecord->filepath, '/') !== $reqlevel) {
                        continue;
                    }
                    $result[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
                }
            }

            $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                      FROM {files} f
                 LEFT JOIN {files_reference} r
                           ON f.referencefileid = r.id
                     WHERE f.contextid = :contextid AND f.component = :component AND f.filearea = :filearea AND f.itemid = :itemid
                           AND f.filepath = :filepath AND f.filename <> '.'
                           $orderby";

            $filerecords = $DB->get_records_sql($sql, $params);
            foreach ($filerecords as $filerecord) {
                $result[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
            }
        }

        return $result;
    }

    /**
     * Delete all area files (optionally limited by itemid).
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area or all areas in context if not specified
     * @param int $itemid item ID or all files if not specified
     * @return bool success
     */
    public function delete_area_files($contextid, $component = false, $filearea = false, $itemid = false) {
        global $DB;

        $conditions = array('contextid'=>$contextid);
        if ($component !== false) {
            $conditions['component'] = $component;
        }
        if ($filearea !== false) {
            $conditions['filearea'] = $filearea;
        }
        if ($itemid !== false) {
            $conditions['itemid'] = $itemid;
        }

        $filerecords = $DB->get_records('files', $conditions);
        foreach ($filerecords as $filerecord) {
            $this->get_file_instance($filerecord)->delete();
        }

        return true; // BC only
    }

    /**
     * Delete all the files from certain areas where itemid is limited by an
     * arbitrary bit of SQL.
     *
     * @param int $contextid the id of the context the files belong to. Must be given.
     * @param string $component the owning component. Must be given.
     * @param string $filearea the file area name. Must be given.
     * @param string $itemidstest an SQL fragment that the itemid must match. Used
     *      in the query like WHERE itemid $itemidstest. Must used named parameters,
     *      and may not used named parameters called contextid, component or filearea.
     * @param array $params any query params used by $itemidstest.
     */
    public function delete_area_files_select($contextid, $component,
            $filearea, $itemidstest, array $params = null) {
        global $DB;

        $where = "contextid = :contextid
                AND component = :component
                AND filearea = :filearea
                AND itemid $itemidstest";
        $params['contextid'] = $contextid;
        $params['component'] = $component;
        $params['filearea'] = $filearea;

        $filerecords = $DB->get_recordset_select('files', $where, $params);
        foreach ($filerecords as $filerecord) {
            $this->get_file_instance($filerecord)->delete();
        }
        $filerecords->close();
    }

    /**
     * Delete all files associated with the given component.
     *
     * @param string $component the component owning the file
     */
    public function delete_component_files($component) {
        global $DB;

        $filerecords = $DB->get_recordset('files', array('component' => $component));
        foreach ($filerecords as $filerecord) {
            $this->get_file_instance($filerecord)->delete();
        }
        $filerecords->close();
    }

    /**
     * Move all the files in a file area from one context to another.
     *
     * @param int $oldcontextid the context the files are being moved from.
     * @param int $newcontextid the context the files are being moved to.
     * @param string $component the plugin that these files belong to.
     * @param string $filearea the name of the file area.
     * @param int $itemid file item ID
     * @return int the number of files moved, for information.
     */
    public function move_area_files_to_new_context($oldcontextid, $newcontextid, $component, $filearea, $itemid = false) {
        // Note, this code is based on some code that Petr wrote in
        // forum_move_attachments in mod/forum/lib.php. I moved it here because
        // I needed it in the question code too.
        $count = 0;

        $oldfiles = $this->get_area_files($oldcontextid, $component, $filearea, $itemid, 'id', false);
        foreach ($oldfiles as $oldfile) {
            $filerecord = new stdClass();
            $filerecord->contextid = $newcontextid;
            $this->create_file_from_storedfile($filerecord, $oldfile);
            $count += 1;
        }

        if ($count) {
            $this->delete_area_files($oldcontextid, $component, $filearea, $itemid);
        }

        return $count;
    }

    /**
     * Recursively creates directory.
     *
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param int $userid the user ID
     * @return bool success
     */
    public function create_directory($contextid, $component, $filearea, $itemid, $filepath, $userid = null) {
        global $DB;

        // validate all parameters, we do not want any rubbish stored in database, right?
        if (!is_number($contextid) or $contextid < 1) {
            throw new file_exception('storedfileproblem', 'Invalid contextid');
        }

        $component = clean_param($component, PARAM_COMPONENT);
        if (empty($component)) {
            throw new file_exception('storedfileproblem', 'Invalid component');
        }

        $filearea = clean_param($filearea, PARAM_AREA);
        if (empty($filearea)) {
            throw new file_exception('storedfileproblem', 'Invalid filearea');
        }

        if (!is_number($itemid) or $itemid < 0) {
            throw new file_exception('storedfileproblem', 'Invalid itemid');
        }

        $filepath = clean_param($filepath, PARAM_PATH);
        if (strpos($filepath, '/') !== 0 or strrpos($filepath, '/') !== strlen($filepath)-1) {
            // path must start and end with '/'
            throw new file_exception('storedfileproblem', 'Invalid file path');
        }

        $pathnamehash = $this->get_pathname_hash($contextid, $component, $filearea, $itemid, $filepath, '.');

        if ($dir_info = $this->get_file_by_hash($pathnamehash)) {
            return $dir_info;
        }

        static $contenthash = null;
        if (!$contenthash) {
            $this->add_string_to_pool('');
            $contenthash = sha1('');
        }

        $now = time();

        $dir_record = new stdClass();
        $dir_record->contextid = $contextid;
        $dir_record->component = $component;
        $dir_record->filearea  = $filearea;
        $dir_record->itemid    = $itemid;
        $dir_record->filepath  = $filepath;
        $dir_record->filename  = '.';
        $dir_record->contenthash  = $contenthash;
        $dir_record->filesize  = 0;

        $dir_record->timecreated  = $now;
        $dir_record->timemodified = $now;
        $dir_record->mimetype     = null;
        $dir_record->userid       = $userid;

        $dir_record->pathnamehash = $pathnamehash;

        $DB->insert_record('files', $dir_record);
        $dir_info = $this->get_file_by_hash($pathnamehash);

        if ($filepath !== '/') {
            //recurse to parent dirs
            $filepath = trim($filepath, '/');
            $filepath = explode('/', $filepath);
            array_pop($filepath);
            $filepath = implode('/', $filepath);
            $filepath = ($filepath === '') ? '/' : "/$filepath/";
            $this->create_directory($contextid, $component, $filearea, $itemid, $filepath, $userid);
        }

        return $dir_info;
    }

    /**
     * Add new file record to database and handle callbacks.
     *
     * @param stdClass $newrecord
     */
    protected function create_file($newrecord) {
        global $DB;
        $newrecord->id = $DB->insert_record('files', $newrecord);

        if ($newrecord->filename !== '.') {
            // Callback for file created.
            if ($pluginsfunction = get_plugins_with_function('after_file_created')) {
                foreach ($pluginsfunction as $plugintype => $plugins) {
                    foreach ($plugins as $pluginfunction) {
                        $pluginfunction($newrecord);
                    }
                }
            }
        }
    }

    /**
     * Add new local file based on existing local file.
     *
     * @param stdClass|array $filerecord object or array describing changes
     * @param stored_file|int $fileorid id or stored_file instance of the existing local file
     * @return stored_file instance of newly created file
     */
    public function create_file_from_storedfile($filerecord, $fileorid) {
        global $DB;

        if ($fileorid instanceof stored_file) {
            $fid = $fileorid->get_id();
        } else {
            $fid = $fileorid;
        }

        $filerecord = (array)$filerecord; // We support arrays too, do not modify the submitted record!

        unset($filerecord['id']);
        unset($filerecord['filesize']);
        unset($filerecord['contenthash']);
        unset($filerecord['pathnamehash']);

        $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                  FROM {files} f
             LEFT JOIN {files_reference} r
                       ON f.referencefileid = r.id
                 WHERE f.id = ?";

        if (!$newrecord = $DB->get_record_sql($sql, array($fid))) {
            throw new file_exception('storedfileproblem', 'File does not exist');
        }

        unset($newrecord->id);

        foreach ($filerecord as $key => $value) {
            // validate all parameters, we do not want any rubbish stored in database, right?
            if ($key == 'contextid' and (!is_number($value) or $value < 1)) {
                throw new file_exception('storedfileproblem', 'Invalid contextid');
            }

            if ($key == 'component') {
                $value = clean_param($value, PARAM_COMPONENT);
                if (empty($value)) {
                    throw new file_exception('storedfileproblem', 'Invalid component');
                }
            }

            if ($key == 'filearea') {
                $value = clean_param($value, PARAM_AREA);
                if (empty($value)) {
                    throw new file_exception('storedfileproblem', 'Invalid filearea');
                }
            }

            if ($key == 'itemid' and (!is_number($value) or $value < 0)) {
                throw new file_exception('storedfileproblem', 'Invalid itemid');
            }


            if ($key == 'filepath') {
                $value = clean_param($value, PARAM_PATH);
                if (strpos($value, '/') !== 0 or strrpos($value, '/') !== strlen($value)-1) {
                    // path must start and end with '/'
                    throw new file_exception('storedfileproblem', 'Invalid file path');
                }
            }

            if ($key == 'filename') {
                $value = clean_param($value, PARAM_FILE);
                if ($value === '') {
                    // path must start and end with '/'
                    throw new file_exception('storedfileproblem', 'Invalid file name');
                }
            }

            if ($key === 'timecreated' or $key === 'timemodified') {
                if (!is_number($value)) {
                    throw new file_exception('storedfileproblem', 'Invalid file '.$key);
                }
                if ($value < 0) {
                    //NOTE: unfortunately I make a mistake when creating the "files" table, we can not have negative numbers there, on the other hand no file should be older than 1970, right? (skodak)
                    $value = 0;
                }
            }

            if ($key == 'referencefileid' or $key == 'referencelastsync') {
                $value = clean_param($value, PARAM_INT);
            }

            $newrecord->$key = $value;
        }

        $newrecord->pathnamehash = $this->get_pathname_hash($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid, $newrecord->filepath, $newrecord->filename);

        if ($newrecord->filename === '.') {
            // special case - only this function supports directories ;-)
            $directory = $this->create_directory($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid, $newrecord->filepath, $newrecord->userid);
            // update the existing directory with the new data
            $newrecord->id = $directory->get_id();
            $DB->update_record('files', $newrecord);
            return $this->get_file_instance($newrecord);
        }

        // note: referencefileid is copied from the original file so that
        // creating a new file from an existing alias creates new alias implicitly.
        // here we just check the database consistency.
        if (!empty($newrecord->repositoryid)) {
            if ($newrecord->referencefileid != $this->get_referencefileid($newrecord->repositoryid, $newrecord->reference, MUST_EXIST)) {
                throw new file_reference_exception($newrecord->repositoryid, $newrecord->reference, $newrecord->referencefileid);
            }
        }

        try {
            $this->create_file($newrecord);
        } catch (dml_exception $e) {
            throw new stored_file_creation_exception($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid,
                                                     $newrecord->filepath, $newrecord->filename, $e->debuginfo);
        }


        $this->create_directory($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid, $newrecord->filepath, $newrecord->userid);

        return $this->get_file_instance($newrecord);
    }

    /**
     * Add new local file.
     *
     * @param stdClass|array $filerecord object or array describing file
     * @param string $url the URL to the file
     * @param array $options {@link download_file_content()} options
     * @param bool $usetempfile use temporary file for download, may prevent out of memory problems
     * @return stored_file
     */
    public function create_file_from_url($filerecord, $url, array $options = null, $usetempfile = false) {

        $filerecord = (array)$filerecord;  // Do not modify the submitted record, this cast unlinks objects.
        $filerecord = (object)$filerecord; // We support arrays too.

        $headers        = isset($options['headers'])        ? $options['headers'] : null;
        $postdata       = isset($options['postdata'])       ? $options['postdata'] : null;
        $fullresponse   = isset($options['fullresponse'])   ? $options['fullresponse'] : false;
        $timeout        = isset($options['timeout'])        ? $options['timeout'] : 300;
        $connecttimeout = isset($options['connecttimeout']) ? $options['connecttimeout'] : 20;
        $skipcertverify = isset($options['skipcertverify']) ? $options['skipcertverify'] : false;
        $calctimeout    = isset($options['calctimeout'])    ? $options['calctimeout'] : false;

        if (!isset($filerecord->filename)) {
            $parts = explode('/', $url);
            $filename = array_pop($parts);
            $filerecord->filename = clean_param($filename, PARAM_FILE);
        }
        $source = !empty($filerecord->source) ? $filerecord->source : $url;
        $filerecord->source = clean_param($source, PARAM_URL);

        if ($usetempfile) {
            check_dir_exists($this->tempdir);
            $tmpfile = tempnam($this->tempdir, 'newfromurl');
            $content = download_file_content($url, $headers, $postdata, $fullresponse, $timeout, $connecttimeout, $skipcertverify, $tmpfile, $calctimeout);
            if ($content === false) {
                throw new file_exception('storedfileproblem', 'Can not fetch file form URL');
            }
            try {
                $newfile = $this->create_file_from_pathname($filerecord, $tmpfile);
                @unlink($tmpfile);
                return $newfile;
            } catch (Exception $e) {
                @unlink($tmpfile);
                throw $e;
            }

        } else {
            $content = download_file_content($url, $headers, $postdata, $fullresponse, $timeout, $connecttimeout, $skipcertverify, NULL, $calctimeout);
            if ($content === false) {
                throw new file_exception('storedfileproblem', 'Can not fetch file form URL');
            }
            return $this->create_file_from_string($filerecord, $content);
        }
    }

    /**
     * Add new local file.
     *
     * @param stdClass|array $filerecord object or array describing file
     * @param string $pathname path to file or content of file
     * @return stored_file
     */
    public function create_file_from_pathname($filerecord, $pathname) {
        global $DB;

        $filerecord = (array)$filerecord;  // Do not modify the submitted record, this cast unlinks objects.
        $filerecord = (object)$filerecord; // We support arrays too.

        // validate all parameters, we do not want any rubbish stored in database, right?
        if (!is_number($filerecord->contextid) or $filerecord->contextid < 1) {
            throw new file_exception('storedfileproblem', 'Invalid contextid');
        }

        $filerecord->component = clean_param($filerecord->component, PARAM_COMPONENT);
        if (empty($filerecord->component)) {
            throw new file_exception('storedfileproblem', 'Invalid component');
        }

        $filerecord->filearea = clean_param($filerecord->filearea, PARAM_AREA);
        if (empty($filerecord->filearea)) {
            throw new file_exception('storedfileproblem', 'Invalid filearea');
        }

        if (!is_number($filerecord->itemid) or $filerecord->itemid < 0) {
            throw new file_exception('storedfileproblem', 'Invalid itemid');
        }

        if (!empty($filerecord->sortorder)) {
            if (!is_number($filerecord->sortorder) or $filerecord->sortorder < 0) {
                $filerecord->sortorder = 0;
            }
        } else {
            $filerecord->sortorder = 0;
        }

        $filerecord->filepath = clean_param($filerecord->filepath, PARAM_PATH);
        if (strpos($filerecord->filepath, '/') !== 0 or strrpos($filerecord->filepath, '/') !== strlen($filerecord->filepath)-1) {
            // path must start and end with '/'
            throw new file_exception('storedfileproblem', 'Invalid file path');
        }

        $filerecord->filename = clean_param($filerecord->filename, PARAM_FILE);
        if ($filerecord->filename === '') {
            // filename must not be empty
            throw new file_exception('storedfileproblem', 'Invalid file name');
        }

        $now = time();
        if (isset($filerecord->timecreated)) {
            if (!is_number($filerecord->timecreated)) {
                throw new file_exception('storedfileproblem', 'Invalid file timecreated');
            }
            if ($filerecord->timecreated < 0) {
                //NOTE: unfortunately I make a mistake when creating the "files" table, we can not have negative numbers there, on the other hand no file should be older than 1970, right? (skodak)
                $filerecord->timecreated = 0;
            }
        } else {
            $filerecord->timecreated = $now;
        }

        if (isset($filerecord->timemodified)) {
            if (!is_number($filerecord->timemodified)) {
                throw new file_exception('storedfileproblem', 'Invalid file timemodified');
            }
            if ($filerecord->timemodified < 0) {
                //NOTE: unfortunately I make a mistake when creating the "files" table, we can not have negative numbers there, on the other hand no file should be older than 1970, right? (skodak)
                $filerecord->timemodified = 0;
            }
        } else {
            $filerecord->timemodified = $now;
        }

        $newrecord = new stdClass();

        $newrecord->contextid = $filerecord->contextid;
        $newrecord->component = $filerecord->component;
        $newrecord->filearea  = $filerecord->filearea;
        $newrecord->itemid    = $filerecord->itemid;
        $newrecord->filepath  = $filerecord->filepath;
        $newrecord->filename  = $filerecord->filename;

        $newrecord->timecreated  = $filerecord->timecreated;
        $newrecord->timemodified = $filerecord->timemodified;
        $newrecord->mimetype     = empty($filerecord->mimetype) ? $this->mimetype($pathname, $filerecord->filename) : $filerecord->mimetype;
        $newrecord->userid       = empty($filerecord->userid) ? null : $filerecord->userid;
        $newrecord->source       = empty($filerecord->source) ? null : $filerecord->source;
        $newrecord->author       = empty($filerecord->author) ? null : $filerecord->author;
        $newrecord->license      = empty($filerecord->license) ? null : $filerecord->license;
        $newrecord->status       = empty($filerecord->status) ? 0 : $filerecord->status;
        $newrecord->sortorder    = $filerecord->sortorder;

        list($newrecord->contenthash, $newrecord->filesize, $newfile) = $this->add_file_to_pool($pathname);

        $newrecord->pathnamehash = $this->get_pathname_hash($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid, $newrecord->filepath, $newrecord->filename);

        try {
            $this->create_file($newrecord);
        } catch (dml_exception $e) {
            if ($newfile) {
                $this->deleted_file_cleanup($newrecord->contenthash);
            }
            throw new stored_file_creation_exception($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid,
                                                    $newrecord->filepath, $newrecord->filename, $e->debuginfo);
        }

        $this->create_directory($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid, $newrecord->filepath, $newrecord->userid);

        return $this->get_file_instance($newrecord);
    }

    /**
     * Add new local file.
     *
     * @param stdClass|array $filerecord object or array describing file
     * @param string $content content of file
     * @return stored_file
     */
    public function create_file_from_string($filerecord, $content) {
        global $DB;

        $filerecord = (array)$filerecord;  // Do not modify the submitted record, this cast unlinks objects.
        $filerecord = (object)$filerecord; // We support arrays too.

        // validate all parameters, we do not want any rubbish stored in database, right?
        if (!is_number($filerecord->contextid) or $filerecord->contextid < 1) {
            throw new file_exception('storedfileproblem', 'Invalid contextid');
        }

        $filerecord->component = clean_param($filerecord->component, PARAM_COMPONENT);
        if (empty($filerecord->component)) {
            throw new file_exception('storedfileproblem', 'Invalid component');
        }

        $filerecord->filearea = clean_param($filerecord->filearea, PARAM_AREA);
        if (empty($filerecord->filearea)) {
            throw new file_exception('storedfileproblem', 'Invalid filearea');
        }

        if (!is_number($filerecord->itemid) or $filerecord->itemid < 0) {
            throw new file_exception('storedfileproblem', 'Invalid itemid');
        }

        if (!empty($filerecord->sortorder)) {
            if (!is_number($filerecord->sortorder) or $filerecord->sortorder < 0) {
                $filerecord->sortorder = 0;
            }
        } else {
            $filerecord->sortorder = 0;
        }

        $filerecord->filepath = clean_param($filerecord->filepath, PARAM_PATH);
        if (strpos($filerecord->filepath, '/') !== 0 or strrpos($filerecord->filepath, '/') !== strlen($filerecord->filepath)-1) {
            // path must start and end with '/'
            throw new file_exception('storedfileproblem', 'Invalid file path');
        }

        $filerecord->filename = clean_param($filerecord->filename, PARAM_FILE);
        if ($filerecord->filename === '') {
            // path must start and end with '/'
            throw new file_exception('storedfileproblem', 'Invalid file name');
        }

        $now = time();
        if (isset($filerecord->timecreated)) {
            if (!is_number($filerecord->timecreated)) {
                throw new file_exception('storedfileproblem', 'Invalid file timecreated');
            }
            if ($filerecord->timecreated < 0) {
                //NOTE: unfortunately I make a mistake when creating the "files" table, we can not have negative numbers there, on the other hand no file should be older than 1970, right? (skodak)
                $filerecord->timecreated = 0;
            }
        } else {
            $filerecord->timecreated = $now;
        }

        if (isset($filerecord->timemodified)) {
            if (!is_number($filerecord->timemodified)) {
                throw new file_exception('storedfileproblem', 'Invalid file timemodified');
            }
            if ($filerecord->timemodified < 0) {
                //NOTE: unfortunately I make a mistake when creating the "files" table, we can not have negative numbers there, on the other hand no file should be older than 1970, right? (skodak)
                $filerecord->timemodified = 0;
            }
        } else {
            $filerecord->timemodified = $now;
        }

        $newrecord = new stdClass();

        $newrecord->contextid = $filerecord->contextid;
        $newrecord->component = $filerecord->component;
        $newrecord->filearea  = $filerecord->filearea;
        $newrecord->itemid    = $filerecord->itemid;
        $newrecord->filepath  = $filerecord->filepath;
        $newrecord->filename  = $filerecord->filename;

        $newrecord->timecreated  = $filerecord->timecreated;
        $newrecord->timemodified = $filerecord->timemodified;
        $newrecord->userid       = empty($filerecord->userid) ? null : $filerecord->userid;
        $newrecord->source       = empty($filerecord->source) ? null : $filerecord->source;
        $newrecord->author       = empty($filerecord->author) ? null : $filerecord->author;
        $newrecord->license      = empty($filerecord->license) ? null : $filerecord->license;
        $newrecord->status       = empty($filerecord->status) ? 0 : $filerecord->status;
        $newrecord->sortorder    = $filerecord->sortorder;

        list($newrecord->contenthash, $newrecord->filesize, $newfile) = $this->add_string_to_pool($content);
        $filepathname = $this->path_from_hash($newrecord->contenthash) . '/' . $newrecord->contenthash;
        // get mimetype by magic bytes
        $newrecord->mimetype = empty($filerecord->mimetype) ? $this->mimetype($filepathname, $filerecord->filename) : $filerecord->mimetype;

        $newrecord->pathnamehash = $this->get_pathname_hash($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid, $newrecord->filepath, $newrecord->filename);

        try {
            $this->create_file($newrecord);
        } catch (dml_exception $e) {
            if ($newfile) {
                $this->deleted_file_cleanup($newrecord->contenthash);
            }
            throw new stored_file_creation_exception($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid,
                                                    $newrecord->filepath, $newrecord->filename, $e->debuginfo);
        }

        $this->create_directory($newrecord->contextid, $newrecord->component, $newrecord->filearea, $newrecord->itemid, $newrecord->filepath, $newrecord->userid);

        return $this->get_file_instance($newrecord);
    }

    /**
     * Create a new alias/shortcut file from file reference information
     *
     * @param stdClass|array $filerecord object or array describing the new file
     * @param int $repositoryid the id of the repository that provides the original file
     * @param string $reference the information required by the repository to locate the original file
     * @param array $options options for creating the new file
     * @return stored_file
     */
    public function create_file_from_reference($filerecord, $repositoryid, $reference, $options = array()) {
        global $DB;

        $filerecord = (array)$filerecord;  // Do not modify the submitted record, this cast unlinks objects.
        $filerecord = (object)$filerecord; // We support arrays too.

        // validate all parameters, we do not want any rubbish stored in database, right?
        if (!is_number($filerecord->contextid) or $filerecord->contextid < 1) {
            throw new file_exception('storedfileproblem', 'Invalid contextid');
        }

        $filerecord->component = clean_param($filerecord->component, PARAM_COMPONENT);
        if (empty($filerecord->component)) {
            throw new file_exception('storedfileproblem', 'Invalid component');
        }

        $filerecord->filearea = clean_param($filerecord->filearea, PARAM_AREA);
        if (empty($filerecord->filearea)) {
            throw new file_exception('storedfileproblem', 'Invalid filearea');
        }

        if (!is_number($filerecord->itemid) or $filerecord->itemid < 0) {
            throw new file_exception('storedfileproblem', 'Invalid itemid');
        }

        if (!empty($filerecord->sortorder)) {
            if (!is_number($filerecord->sortorder) or $filerecord->sortorder < 0) {
                $filerecord->sortorder = 0;
            }
        } else {
            $filerecord->sortorder = 0;
        }

        $filerecord->mimetype          = empty($filerecord->mimetype) ? $this->mimetype($filerecord->filename) : $filerecord->mimetype;
        $filerecord->userid            = empty($filerecord->userid) ? null : $filerecord->userid;
        $filerecord->source            = empty($filerecord->source) ? null : $filerecord->source;
        $filerecord->author            = empty($filerecord->author) ? null : $filerecord->author;
        $filerecord->license           = empty($filerecord->license) ? null : $filerecord->license;
        $filerecord->status            = empty($filerecord->status) ? 0 : $filerecord->status;
        $filerecord->filepath          = clean_param($filerecord->filepath, PARAM_PATH);
        if (strpos($filerecord->filepath, '/') !== 0 or strrpos($filerecord->filepath, '/') !== strlen($filerecord->filepath)-1) {
            // Path must start and end with '/'.
            throw new file_exception('storedfileproblem', 'Invalid file path');
        }

        $filerecord->filename = clean_param($filerecord->filename, PARAM_FILE);
        if ($filerecord->filename === '') {
            // Path must start and end with '/'.
            throw new file_exception('storedfileproblem', 'Invalid file name');
        }

        $now = time();
        if (isset($filerecord->timecreated)) {
            if (!is_number($filerecord->timecreated)) {
                throw new file_exception('storedfileproblem', 'Invalid file timecreated');
            }
            if ($filerecord->timecreated < 0) {
                // NOTE: unfortunately I make a mistake when creating the "files" table, we can not have negative numbers there, on the other hand no file should be older than 1970, right? (skodak)
                $filerecord->timecreated = 0;
            }
        } else {
            $filerecord->timecreated = $now;
        }

        if (isset($filerecord->timemodified)) {
            if (!is_number($filerecord->timemodified)) {
                throw new file_exception('storedfileproblem', 'Invalid file timemodified');
            }
            if ($filerecord->timemodified < 0) {
                // NOTE: unfortunately I make a mistake when creating the "files" table, we can not have negative numbers there, on the other hand no file should be older than 1970, right? (skodak)
                $filerecord->timemodified = 0;
            }
        } else {
            $filerecord->timemodified = $now;
        }

        $transaction = $DB->start_delegated_transaction();

        try {
            $filerecord->referencefileid = $this->get_or_create_referencefileid($repositoryid, $reference);
        } catch (Exception $e) {
            throw new file_reference_exception($repositoryid, $reference, null, null, $e->getMessage());
        }

        if (isset($filerecord->contenthash) && !$this->content_exists($filerecord->contenthash)) {
            $this->try_content_recovery($filerecord->contenthash);
        }

        if (isset($filerecord->contenthash) && $this->content_exists($filerecord->contenthash)) {
            // there was specified the contenthash for a file already stored in moodle filepool
            if (empty($filerecord->filesize)) {
                $filepathname = $this->path_from_hash($filerecord->contenthash) . '/' . $filerecord->contenthash;
                $filerecord->filesize = filesize($filepathname);
            } else {
                $filerecord->filesize = clean_param($filerecord->filesize, PARAM_INT);
            }
        } else {
            // atempt to get the result of last synchronisation for this reference
            $lastcontent = $DB->get_record('files', array('referencefileid' => $filerecord->referencefileid),
                    'id, contenthash, filesize', IGNORE_MULTIPLE);
            if ($lastcontent) {
                $filerecord->contenthash = $lastcontent->contenthash;
                $filerecord->filesize = $lastcontent->filesize;
            } else {
                // External file doesn't have content in moodle.
                // So we create an empty file for it.
                list($filerecord->contenthash, $filerecord->filesize, $newfile) = $this->add_string_to_pool(null);
            }
        }

        $filerecord->pathnamehash = $this->get_pathname_hash($filerecord->contextid, $filerecord->component, $filerecord->filearea, $filerecord->itemid, $filerecord->filepath, $filerecord->filename);

        try {
            $filerecord->id = $DB->insert_record('files', $filerecord);
        } catch (dml_exception $e) {
            if (!empty($newfile)) {
                $this->deleted_file_cleanup($filerecord->contenthash);
            }
            throw new stored_file_creation_exception($filerecord->contextid, $filerecord->component, $filerecord->filearea, $filerecord->itemid,
                                                    $filerecord->filepath, $filerecord->filename, $e->debuginfo);
        }

        $this->create_directory($filerecord->contextid, $filerecord->component, $filerecord->filearea, $filerecord->itemid, $filerecord->filepath, $filerecord->userid);

        $transaction->allow_commit();

        // this will retrieve all reference information from DB as well
        return $this->get_file_by_id($filerecord->id);
    }

    /**
     * Creates new image file from existing.
     *
     * @param stdClass|array $filerecord object or array describing new file
     * @param int|stored_file $fid file id or stored file object
     * @param int $newwidth in pixels
     * @param int $newheight in pixels
     * @param bool $keepaspectratio whether or not keep aspect ratio
     * @param int $quality depending on image type 0-100 for jpeg, 0-9 (0 means no compression) for png
     * @return stored_file
     */
    public function convert_image($filerecord, $fid, $newwidth = null, $newheight = null, $keepaspectratio = true, $quality = null) {
        if (!function_exists('imagecreatefromstring')) {
            //Most likely the GD php extension isn't installed
            //image conversion cannot succeed
            throw new file_exception('storedfileproblem', 'imagecreatefromstring() doesnt exist. The PHP extension "GD" must be installed for image conversion.');
        }

        if ($fid instanceof stored_file) {
            $fid = $fid->get_id();
        }

        $filerecord = (array)$filerecord; // We support arrays too, do not modify the submitted record!

        if (!$file = $this->get_file_by_id($fid)) { // Make sure file really exists and we we correct data.
            throw new file_exception('storedfileproblem', 'File does not exist');
        }

        if (!$imageinfo = $file->get_imageinfo()) {
            throw new file_exception('storedfileproblem', 'File is not an image');
        }

        if (!isset($filerecord['filename'])) {
            $filerecord['filename'] = $file->get_filename();
        }

        if (!isset($filerecord['mimetype'])) {
            $filerecord['mimetype'] = $imageinfo['mimetype'];
        }

        $width    = $imageinfo['width'];
        $height   = $imageinfo['height'];

        if ($keepaspectratio) {
            if (0 >= $newwidth and 0 >= $newheight) {
                // no sizes specified
                $newwidth  = $width;
                $newheight = $height;

            } else if (0 < $newwidth and 0 < $newheight) {
                $xheight = ($newwidth*($height/$width));
                if ($xheight < $newheight) {
                    $newheight = (int)$xheight;
                } else {
                    $newwidth = (int)($newheight*($width/$height));
                }

            } else if (0 < $newwidth) {
                $newheight = (int)($newwidth*($height/$width));

            } else { //0 < $newheight
                $newwidth = (int)($newheight*($width/$height));
            }

        } else {
            if (0 >= $newwidth) {
                $newwidth = $width;
            }
            if (0 >= $newheight) {
                $newheight = $height;
            }
        }

        // The original image.
        $img = imagecreatefromstring($file->get_content());

        // A new true color image where we will copy our original image.
        $newimg = imagecreatetruecolor($newwidth, $newheight);

        // Determine if the file supports transparency.
        $hasalpha = $filerecord['mimetype'] == 'image/png' || $filerecord['mimetype'] == 'image/gif';

        // Maintain transparency.
        if ($hasalpha) {
            imagealphablending($newimg, true);

            // Get the current transparent index for the original image.
            $colour = imagecolortransparent($img);
            if ($colour == -1) {
                // Set a transparent colour index if there's none.
                $colour = imagecolorallocatealpha($newimg, 255, 255, 255, 127);
                // Save full alpha channel.
                imagesavealpha($newimg, true);
            }
            imagecolortransparent($newimg, $colour);
            imagefill($newimg, 0, 0, $colour);
        }

        // Process the image to be output.
        if ($height != $newheight or $width != $newwidth) {
            // Resample if the dimensions differ from the original.
            if (!imagecopyresampled($newimg, $img, 0, 0, 0, 0, $newwidth, $newheight, $width, $height)) {
                // weird
                throw new file_exception('storedfileproblem', 'Can not resize image');
            }
            imagedestroy($img);
            $img = $newimg;

        } else if ($hasalpha) {
            // Just copy to the new image with the alpha channel.
            if (!imagecopy($newimg, $img, 0, 0, 0, 0, $width, $height)) {
                // Weird.
                throw new file_exception('storedfileproblem', 'Can not copy image');
            }
            imagedestroy($img);
            $img = $newimg;

        } else {
            // No particular processing needed for the original image.
            imagedestroy($newimg);
        }

        ob_start();
        switch ($filerecord['mimetype']) {
            case 'image/gif':
                imagegif($img);
                break;

            case 'image/jpeg':
                if (is_null($quality)) {
                    imagejpeg($img);
                } else {
                    imagejpeg($img, NULL, $quality);
                }
                break;

            case 'image/png':
                $quality = (int)$quality;

                // Woah nelly! Because PNG quality is in the range 0 - 9 compared to JPEG quality,
                // the latter of which can go to 100, we need to make sure that quality here is
                // in a safe range or PHP WILL CRASH AND DIE. You have been warned.
                $quality = $quality > 9 ? (int)(max(1.0, (float)$quality / 100.0) * 9.0) : $quality;
                imagepng($img, NULL, $quality, -1);
                break;

            default:
                throw new file_exception('storedfileproblem', 'Unsupported mime type');
        }

        $content = ob_get_contents();
        ob_end_clean();
        imagedestroy($img);

        if (!$content) {
            throw new file_exception('storedfileproblem', 'Can not convert image');
        }

        return $this->create_file_from_string($filerecord, $content);
    }

    /**
     * Add file content to sha1 pool.
     *
     * @param string $pathname path to file
     * @param string $contenthash sha1 hash of content if known (performance only)
     * @return array (contenthash, filesize, newfile)
     */
    public function add_file_to_pool($pathname, $contenthash = NULL) {
        global $CFG;

        if (!is_readable($pathname)) {
            throw new file_exception('storedfilecannotread', '', $pathname);
        }

        $filesize = filesize($pathname);
        if ($filesize === false) {
            throw new file_exception('storedfilecannotread', '', $pathname);
        }

        if (is_null($contenthash)) {
            $contenthash = sha1_file($pathname);
        } else if ($CFG->debugdeveloper) {
            $filehash = sha1_file($pathname);
            if ($filehash === false) {
                throw new file_exception('storedfilecannotread', '', $pathname);
            }
            if ($filehash !== $contenthash) {
                // Hopefully this never happens, if yes we need to fix calling code.
                debugging("Invalid contenthash submitted for file $pathname", DEBUG_DEVELOPER);
                $contenthash = $filehash;
            }
        }
        if ($contenthash === false) {
            throw new file_exception('storedfilecannotread', '', $pathname);
        }

        if ($filesize > 0 and $contenthash === sha1('')) {
            // Did the file change or is sha1_file() borked for this file?
            clearstatcache();
            $contenthash = sha1_file($pathname);
            $filesize = filesize($pathname);

            if ($contenthash === false or $filesize === false) {
                throw new file_exception('storedfilecannotread', '', $pathname);
            }
            if ($filesize > 0 and $contenthash === sha1('')) {
                // This is very weird...
                throw new file_exception('storedfilecannotread', '', $pathname);
            }
        }

        $hashpath = $this->path_from_hash($contenthash);
        $hashfile = "$hashpath/$contenthash";

        $newfile = true;

        if (file_exists($hashfile)) {
            if (filesize($hashfile) === $filesize) {
                return array($contenthash, $filesize, false);
            }
            if (sha1_file($hashfile) === $contenthash) {
                // Jackpot! We have a sha1 collision.
                mkdir("$this->filedir/jackpot/", $this->dirpermissions, true);
                copy($pathname, "$this->filedir/jackpot/{$contenthash}_1");
                copy($hashfile, "$this->filedir/jackpot/{$contenthash}_2");
                throw new file_pool_content_exception($contenthash);
            }
            debugging("Replacing invalid content file $contenthash");
            unlink($hashfile);
            $newfile = false;
        }

        if (!is_dir($hashpath)) {
            if (!mkdir($hashpath, $this->dirpermissions, true)) {
                // Permission trouble.
                throw new file_exception('storedfilecannotcreatefiledirs');
            }
        }

        // Let's try to prevent some race conditions.

        $prev = ignore_user_abort(true);
        @unlink($hashfile.'.tmp');
        if (!copy($pathname, $hashfile.'.tmp')) {
            // Borked permissions or out of disk space.
            @unlink($hashfile.'.tmp');
            ignore_user_abort($prev);
            throw new file_exception('storedfilecannotcreatefile');
        }
        if (sha1_file($hashfile.'.tmp') !== $contenthash) {
            // Highly unlikely edge case, but this can happen on an NFS volume with no space remaining.
            @unlink($hashfile.'.tmp');
            ignore_user_abort($prev);
            throw new file_exception('storedfilecannotcreatefile');
        }
        rename($hashfile.'.tmp', $hashfile);
        chmod($hashfile, $this->filepermissions); // Fix permissions if needed.
        @unlink($hashfile.'.tmp'); // Just in case anything fails in a weird way.

        // Totara: support for cloud storage and backup plugins
        (new \totara_core\hook\filedir_content_file_added($hashfile, $contenthash))->execute();

        ignore_user_abort($prev);

        return array($contenthash, $filesize, $newfile);
    }

    /**
     * Add string content to sha1 pool.
     *
     * @param string $content file content - binary string
     * @return array (contenthash, filesize, newfile)
     */
    public function add_string_to_pool($content) {
        global $CFG;

        // Totara: $content is expected to be a string
        $content = $content ?? '';

        $contenthash = sha1($content);
        $filesize = strlen($content); // binary length

        $hashpath = $this->path_from_hash($contenthash);
        $hashfile = "$hashpath/$contenthash";

        $newfile = true;

        if (file_exists($hashfile)) {
            if (filesize($hashfile) === $filesize) {
                return array($contenthash, $filesize, false);
            }
            if (sha1_file($hashfile) === $contenthash) {
                // Jackpot! We have a sha1 collision.
                mkdir("$this->filedir/jackpot/", $this->dirpermissions, true);
                copy($hashfile, "$this->filedir/jackpot/{$contenthash}_1");
                file_put_contents("$this->filedir/jackpot/{$contenthash}_2", $content);
                throw new file_pool_content_exception($contenthash);
            }
            debugging("Replacing invalid content file $contenthash");
            unlink($hashfile);
            $newfile = false;
        }

        if (!is_dir($hashpath)) {
            if (!mkdir($hashpath, $this->dirpermissions, true)) {
                // Permission trouble.
                throw new file_exception('storedfilecannotcreatefiledirs');
            }
        }

        // Hopefully this works around most potential race conditions.

        $prev = ignore_user_abort(true);

        if (!empty($CFG->preventfilelocking)) {
            $newsize = file_put_contents($hashfile.'.tmp', $content);
        } else {
            $newsize = file_put_contents($hashfile.'.tmp', $content, LOCK_EX);
        }

        if ($newsize === false) {
            // Borked permissions most likely.
            ignore_user_abort($prev);
            throw new file_exception('storedfilecannotcreatefile');
        }
        if (filesize($hashfile.'.tmp') !== $filesize) {
            // Out of disk space?
            unlink($hashfile.'.tmp');
            ignore_user_abort($prev);
            throw new file_exception('storedfilecannotcreatefile');
        }
        rename($hashfile.'.tmp', $hashfile);
        chmod($hashfile, $this->filepermissions); // Fix permissions if needed.
        @unlink($hashfile.'.tmp'); // Just in case anything fails in a weird way.

        // Totara: support for cloud storage and backup plugins
        (new \totara_core\hook\filedir_content_file_added($hashfile, $contenthash))->execute();

        ignore_user_abort($prev);

        return array($contenthash, $filesize, $newfile);
    }

    /**
     * Serve file content using X-Sendfile header.
     * Please make sure that all headers are already sent
     * and the all access control checks passed.
     *
     * @param string $contenthash sah1 hash of the file content to be served
     * @return bool success
     */
    public function xsendfile($contenthash) {
        global $CFG;
        require_once("$CFG->libdir/xsendfilelib.php");

        $hashpath = $this->path_from_hash($contenthash);
        return xsendfile("$hashpath/$contenthash");
    }

    /**
     * Does the content file exist in $CFG->filedir?
     *
     * @internal to be used from file API only
     *
     * @param string $contenthash
     * @param bool $clearstatcache
     * @return bool
     */
    public function content_exists(string $contenthash, bool $clearstatcache = false): bool {
        $dir = $this->path_from_hash($contenthash);
        $filepath = $dir . '/' . $contenthash;
        if ($clearstatcache) {
            clearstatcache(true, $filepath);
        }
        return file_exists($filepath);
    }

    /**
     * Validate content file hash.
     *
     * @internal
     *
     * @param string $contenthash
     * @param bool $deleteinvalid delete invalid content files
     * @return bool
     */
    public function validate_content(string $contenthash, bool $deleteinvalid = false): bool {
        $dir = $this->path_from_hash($contenthash);
        $filepath = $dir . '/' . $contenthash;
        if (!file_exists($filepath)) {
            return false;
        }
        $filehash = sha1_file($filepath);
        $valid = ($filehash === $contenthash);
        if ($deleteinvalid && !$valid) {
            unlink($filepath);
        }
        return $valid;
    }

    /**
     * Returns length of content file.
     *
     * @param string $contenthash
     * @return int|false stream resource or false on error
     */
    public function get_content_length(string $contenthash) {
        $dir = $this->path_from_hash($contenthash);
        $filepath = $dir . '/' . $contenthash;
        return filesize($filepath);
    }

    /**
     * Returns stream for reading of content file.
     *
     * @param string $contenthash
     * @return resource|false stream resource or false on error
     */
    public function get_content_stream(string $contenthash) {
        $dir = $this->path_from_hash($contenthash);
        $filepath = $dir . '/' . $contenthash;
        return fopen($filepath, 'r');
    }

    /**
     * Return path to file with given hash.
     *
     * NOTE: must not be public, files in pool must not be modified
     *
     * @param string $contenthash content hash
     * @return string expected file location
     */
    protected function path_from_hash($contenthash) {
        $l1 = $contenthash[0].$contenthash[1];
        $l2 = $contenthash[2].$contenthash[3];
        return "$this->filedir/$l1/$l2";
    }

    /**
     * Return path to file with given hash.
     *
     * NOTE: must not be public, files in pool must not be modified
     *
     * @param string $contenthash content hash
     * @return string expected file location
     */
    protected function trash_path_from_hash($contenthash) {
        $l1 = $contenthash[0].$contenthash[1];
        $l2 = $contenthash[2].$contenthash[3];
        return "$this->trashdir/$l1/$l2";
    }

    /**
     * Tries to recover missing content of file.
     *
     * @internal to be used from file API only
     *
     * @param stored_file|string $file stored_file instance or content hash
     * @return bool success
     */
    public function try_content_recovery($file): bool {
        if ($file instanceof stored_file) {
            $contenthash = $file->get_contenthash();
        } else if (preg_match('/^([a-f0-9]{40})$/D', $file)) {
            $contenthash = $file;
        } else {
            debugging('Invalid content file parameter', DEBUG_DEVELOPER);
            return false;
        }
        if ($this->content_exists($contenthash, true)) {
            // Strange, no need to recover anything.
            return true;
        }

        $contentdir = $this->path_from_hash($contenthash);
        $contentfile = $contentdir . '/' . $contenthash;

        if ($contenthash === sha1('')) {
            if (!is_dir($contentdir)) {
                mkdir($contentdir, $this->dirpermissions, true);
            }
            file_put_contents($contentfile, '');
            @chmod($contentfile, $this->filepermissions); // Fix permissions if needed.
            return true;
        }

        $prev = ignore_user_abort(true);

        $this->try_trashdir_content_recovery($contenthash);
        if (!file_exists($contentfile)) {
            if (!is_dir($contentdir)) {
                if (!mkdir($contentdir, $this->dirpermissions, true)) {
                    ignore_user_abort($prev);
                    return false;
                }
            }
            (new \totara_core\hook\filedir_content_file_restore($this, $contenthash))->execute();
            if (!file_exists($contentfile)) {
                ignore_user_abort($prev);
                return false;
            }
        }

        @chmod($contentfile, $this->filepermissions); // Fix permissions if needed.
        (new \totara_core\hook\filedir_content_file_added($contentfile, $contenthash))->execute();

        ignore_user_abort($prev);
        return true;
    }

    /**
     * Tries to recover missing content of file from trash.
     *
     * @param stored_file|string $file stored_file instance or content hash
     * @return bool success
     */
    protected function try_trashdir_content_recovery($file): bool {
        if ($file instanceof stored_file) {
            $contenthash = $file->get_contenthash();
        } else {
            $contenthash = $file;
        }
        $trashfile = $this->trash_path_from_hash($contenthash).'/'.$contenthash;
        if (!is_readable($trashfile)) {
            if (!is_readable($this->trashdir.'/'.$contenthash)) {
                return false;
            }
            // nice, at least alternative trash file in trash root exists
            $trashfile = $this->trashdir.'/'.$contenthash;
        }
        if (sha1_file($trashfile) !== $contenthash) {
            // Invalid trash file.
            return false;
        }
        $contentdir  = $this->path_from_hash($contenthash);
        $contentfile = $contentdir.'/'.$contenthash;
        if (file_exists($contentfile)) {
            //strange, no need to recover anything
            return true;
        }
        if (!is_dir($contentdir)) {
            if (!mkdir($contentdir, $this->dirpermissions, true)) {
                return false;
            }
        }
        return rename($trashfile, $contentfile);
    }

    /**
     * Marks pool file as candidate for deleting.
     *
     * DO NOT call directly - reserved for core!!
     *
     * @param string $contenthash
     */
    public function deleted_file_cleanup($contenthash) {
        global $DB;

        if ($contenthash === sha1('')) {
            // No need to delete empty content file with sha1('') content hash.
            return;
        }

        //Note: this section is critical - in theory file could be reused at the same
        //      time, if this happens we can still recover the file from trash
        if ($DB->record_exists('files', array('contenthash'=>$contenthash))) {
            // file content is still used
            return;
        }

        $prev = ignore_user_abort(true);

        //move content file to trash
        $contentfile = $this->path_from_hash($contenthash).'/'.$contenthash;
        $trashpath = $this->trash_path_from_hash($contenthash);
        $trashfile = $trashpath.'/'.$contenthash;
        if (!file_exists($contentfile)) {
            // Already deleted locally, but it can be still in external filedir clones.
        } else if (file_exists($trashfile)) {
            // we already have this content in trash, no need to move it there
            unlink($contentfile);
        } else {
            if (!is_dir($trashpath)) {
                mkdir($trashpath, $this->dirpermissions, true);
            }
            rename($contentfile, $trashfile);
        }

        // Fix permissions, only if needed.
        if (file_exists($trashfile)) {
            $currentperms = octdec(substr(decoct(fileperms($trashfile)), -4));
            if ((int)$this->filepermissions !== $currentperms) {
                chmod($trashfile, $this->filepermissions);
            }
        }

        // Totara: support for cloud storage and backup plugins
        $hook = new \totara_core\hook\filedir_content_file_deleted($contenthash, $trashfile);
        $hook->execute();
        if ($hook->is_restorable()) {
            // Some plugin says it can recover it if needed later, so delete the file to free disk space.
            @unlink($trashfile);
        }

        ignore_user_abort($prev);
    }

    /**
     * When user referring to a moodle file, we build the reference field
     *
     * @param array $params
     * @return string
     */
    public static function pack_reference($params) {
        $params = (array)$params;
        $reference = array();
        $reference['contextid'] = is_null($params['contextid']) ? null : clean_param($params['contextid'], PARAM_INT);
        $reference['component'] = is_null($params['component']) ? null : clean_param($params['component'], PARAM_COMPONENT);
        $reference['itemid']    = is_null($params['itemid'])    ? null : clean_param($params['itemid'],    PARAM_INT);
        $reference['filearea']  = is_null($params['filearea'])  ? null : clean_param($params['filearea'],  PARAM_AREA);
        $reference['filepath']  = is_null($params['filepath'])  ? null : clean_param($params['filepath'],  PARAM_PATH);
        $reference['filename']  = is_null($params['filename'])  ? null : clean_param($params['filename'],  PARAM_FILE);
        return base64_encode(serialize($reference));
    }

    /**
     * Unpack reference field
     *
     * @param string $str
     * @param bool $cleanparams if set to true, array elements will be passed through {@link clean_param()}
     * @throws file_reference_exception if the $str does not have the expected format
     * @return array
     */
    public static function unpack_reference($str, $cleanparams = false) {
        $decoded = base64_decode($str, true);
        if ($decoded === false) {
            throw new file_reference_exception(null, $str, null, null, 'Invalid base64 format');
        }
        $params = @unserialize($decoded); // hide E_NOTICE
        if ($params === false) {
            throw new file_reference_exception(null, $decoded, null, null, 'Not an unserializeable value');
        }
        if (is_array($params) && $cleanparams) {
            $params = array(
                'component' => is_null($params['component']) ? ''   : clean_param($params['component'], PARAM_COMPONENT),
                'filearea'  => is_null($params['filearea'])  ? ''   : clean_param($params['filearea'], PARAM_AREA),
                'itemid'    => is_null($params['itemid'])    ? 0    : clean_param($params['itemid'], PARAM_INT),
                'filename'  => is_null($params['filename'])  ? null : clean_param($params['filename'], PARAM_FILE),
                'filepath'  => is_null($params['filepath'])  ? null : clean_param($params['filepath'], PARAM_PATH),
                'contextid' => is_null($params['contextid']) ? null : clean_param($params['contextid'], PARAM_INT)
            );
        }
        return $params;
    }

    /**
     * Search through the server files.
     *
     * The query parameter will be used in conjuction with the SQL directive
     * LIKE, so include '%' in it if you need to. This search will always ignore
     * user files and directories. Note that the search is case insensitive.
     *
     * This query can quickly become inefficient so use it sparignly.
     *
     * @param  string  $query The string used with SQL LIKE.
     * @param  integer $from  The offset to start the search at.
     * @param  integer $limit The maximum number of results.
     * @param  boolean $count When true this methods returns the number of results availabe,
     *                        disregarding the parameters $from and $limit.
     * @return int|array      Integer when count, otherwise array of stored_file objects.
     */
    public function search_server_files($query, $from = 0, $limit = 20, $count = false) {
        global $DB;
        $params = array(
            'contextlevel' => CONTEXT_USER,
            'directory' => '.',
            'query' => $query
        );

        if ($count) {
            $select = 'COUNT(1)';
        } else {
            $select = self::instance_sql_fields('f', 'r');
        }
        $like = $DB->sql_like('f.filename', ':query', false);

        $sql = "SELECT $select
                  FROM {files} f
             LEFT JOIN {files_reference} r
                    ON f.referencefileid = r.id
                  JOIN {context} c
                    ON f.contextid = c.id
                 WHERE c.contextlevel <> :contextlevel
                   AND f.filename <> :directory
                   AND " . $like . "";

        if ($count) {
            return $DB->count_records_sql($sql, $params);
        }

        $sql .= " ORDER BY f.filename";

        $result = array();
        $filerecords = $DB->get_recordset_sql($sql, $params, $from, $limit);
        foreach ($filerecords as $filerecord) {
            $result[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
        }
        $filerecords->close();

        return $result;
    }

    /**
     * Returns all aliases that refer to some stored_file via the given reference
     *
     * All repositories that provide access to a stored_file are expected to use
     * {@link self::pack_reference()}. This method can't be used if the given reference
     * does not use this format or if you are looking for references to an external file
     * (for example it can't be used to search for all aliases that refer to a given
     * Dropbox or Box.net file).
     *
     * Aliases in user draft areas are excluded from the returned list.
     *
     * @param string $reference identification of the referenced file
     * @return array of stored_file indexed by its pathnamehash
     */
    public function search_references($reference) {
        global $DB;

        if (is_null($reference)) {
            throw new coding_exception('NULL is not a valid reference to an external file');
        }

        // Give {@link self::unpack_reference()} a chance to throw exception if the
        // reference is not in a valid format.
        self::unpack_reference($reference);

        $referencehash = sha1($reference);

        $sql = "SELECT ".self::instance_sql_fields('f', 'r')."
                  FROM {files} f
                  JOIN {files_reference} r ON f.referencefileid = r.id
                  JOIN {repository_instances} ri ON r.repositoryid = ri.id
                 WHERE r.referencehash = ?
                       AND (f.component <> ? OR f.filearea <> ?)";

        $rs = $DB->get_recordset_sql($sql, array($referencehash, 'user', 'draft'));
        $files = array();
        foreach ($rs as $filerecord) {
            $files[$filerecord->pathnamehash] = $this->get_file_instance($filerecord);
        }

        return $files;
    }

    /**
     * Returns the number of aliases that refer to some stored_file via the given reference
     *
     * All repositories that provide access to a stored_file are expected to use
     * {@link self::pack_reference()}. This method can't be used if the given reference
     * does not use this format or if you are looking for references to an external file
     * (for example it can't be used to count aliases that refer to a given Dropbox or
     * Box.net file).
     *
     * Aliases in user draft areas are not counted.
     *
     * @param string $reference identification of the referenced file
     * @return int
     */
    public function search_references_count($reference) {
        global $DB;

        if (is_null($reference)) {
            throw new coding_exception('NULL is not a valid reference to an external file');
        }

        // Give {@link self::unpack_reference()} a chance to throw exception if the
        // reference is not in a valid format.
        self::unpack_reference($reference);

        $referencehash = sha1($reference);

        $sql = "SELECT COUNT(f.id)
                  FROM {files} f
                  JOIN {files_reference} r ON f.referencefileid = r.id
                  JOIN {repository_instances} ri ON r.repositoryid = ri.id
                 WHERE r.referencehash = ?
                       AND (f.component <> ? OR f.filearea <> ?)";

        return (int)$DB->count_records_sql($sql, array($referencehash, 'user', 'draft'));
    }

    /**
     * Returns all aliases that link to the given stored_file
     *
     * Aliases in user draft areas are excluded from the returned list.
     *
     * @param stored_file $storedfile
     * @return array of stored_file
     */
    public function get_references_by_storedfile(stored_file $storedfile) {
        global $DB;

        $params = array();
        $params['contextid'] = $storedfile->get_contextid();
        $params['component'] = $storedfile->get_component();
        $params['filearea']  = $storedfile->get_filearea();
        $params['itemid']    = $storedfile->get_itemid();
        $params['filename']  = $storedfile->get_filename();
        $params['filepath']  = $storedfile->get_filepath();

        return $this->search_references(self::pack_reference($params));
    }

    /**
     * Returns the number of aliases that link to the given stored_file
     *
     * Aliases in user draft areas are not counted.
     *
     * @param stored_file $storedfile
     * @return int
     */
    public function get_references_count_by_storedfile(stored_file $storedfile) {
        global $DB;

        $params = array();
        $params['contextid'] = $storedfile->get_contextid();
        $params['component'] = $storedfile->get_component();
        $params['filearea']  = $storedfile->get_filearea();
        $params['itemid']    = $storedfile->get_itemid();
        $params['filename']  = $storedfile->get_filename();
        $params['filepath']  = $storedfile->get_filepath();

        return $this->search_references_count(self::pack_reference($params));
    }

    /**
     * Updates all files that are referencing this file with the new contenthash
     * and filesize
     *
     * @param stored_file $storedfile
     */
    public function update_references_to_storedfile(stored_file $storedfile) {
        global $CFG, $DB;
        $params = array();
        $params['contextid'] = $storedfile->get_contextid();
        $params['component'] = $storedfile->get_component();
        $params['filearea']  = $storedfile->get_filearea();
        $params['itemid']    = $storedfile->get_itemid();
        $params['filename']  = $storedfile->get_filename();
        $params['filepath']  = $storedfile->get_filepath();
        $reference = self::pack_reference($params);
        $referencehash = sha1($reference);

        $sql = "SELECT repositoryid, id FROM {files_reference}
                 WHERE referencehash = ?";
        $rs = $DB->get_recordset_sql($sql, array($referencehash));

        $now = time();
        foreach ($rs as $record) {
            $this->update_references($record->id, $now, null,
                    $storedfile->get_contenthash(), $storedfile->get_filesize(), 0, $storedfile->get_timemodified());
        }
        $rs->close();
    }

    /**
     * Convert file alias to local file
     *
     * @throws moodle_exception if file could not be downloaded
     *
     * @param stored_file $storedfile a stored_file instances
     * @param int $maxbytes throw an exception if file size is bigger than $maxbytes (0 means no limit)
     * @return stored_file stored_file
     */
    public function import_external_file(stored_file $storedfile, $maxbytes = 0) {
        global $CFG;
        $storedfile->import_external_file_contents($maxbytes);
        $storedfile->delete_reference();
        return $storedfile;
    }

    /**
     * Return mimetype by given file pathname
     *
     * If file has a known extension, we return the mimetype based on extension.
     * Otherwise (when possible) we try to get the mimetype from file contents.
     *
     * @param string $pathname full path to the file
     * @param string $filename correct file name with extension, if omitted will be taken from $path
     * @return string
     */
    public static function mimetype($pathname, $filename = null) {
        if (empty($filename)) {
            $filename = $pathname;
        }
        $type = mimeinfo('type', $filename);
        if ($type === 'document/unknown' && class_exists('finfo') && file_exists($pathname)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $type = mimeinfo_from_type('type', $finfo->file($pathname));
        }
        return $type;
    }

    /**
     * Cron cleanup job.
     */
    public function cron() {
        global $CFG, $DB;
        require_once($CFG->libdir.'/cronlib.php');

        // find out all stale draft areas (older than 4 days) and purge them
        // those are identified by time stamp of the /. root dir
        mtrace('Deleting old draft files... ', '');
        cron_trace_time_and_memory();
        $old = time() - 60*60*24*4;
        $sql = "SELECT *
                  FROM {files}
                 WHERE component = 'user' AND filearea = 'draft' AND filepath = '/' AND filename = '.'
                       AND timecreated < :old";
        $rs = $DB->get_recordset_sql($sql, array('old'=>$old));
        foreach ($rs as $dir) {
            $this->delete_area_files($dir->contextid, $dir->component, $dir->filearea, $dir->itemid);
        }
        $rs->close();
        mtrace('done.');

        // remove orphaned preview files (that is files in the core preview filearea without
        // the existing original file)
        mtrace('Deleting orphaned preview files... ', '');
        cron_trace_time_and_memory();
        $sql = "SELECT p.*
                  FROM {files} p
             LEFT JOIN {files} o ON (p.filename = o.contenthash)
                 WHERE p.contextid = ? AND p.component = 'core' AND p.filearea = 'preview' AND p.itemid = 0
                       AND o.id IS NULL";
        $syscontext = context_system::instance();
        $rs = $DB->get_recordset_sql($sql, array($syscontext->id));
        foreach ($rs as $orphan) {
            $file = $this->get_file_instance($orphan);
            if (!$file->is_directory()) {
                $file->delete();
            }
        }
        $rs->close();
        mtrace('done.');

        // Remove orphaned converted files (that is files in the core documentconversion filearea without
        // the existing original file).
        mtrace('Deleting orphaned document conversion files... ', '');
        cron_trace_time_and_memory();
        $sql = "SELECT p.*
                  FROM {files} p
             LEFT JOIN {files} o ON (p.filename = o.contenthash)
                 WHERE p.contextid = ? AND p.component = 'core' AND p.filearea = 'documentconversion' AND p.itemid = 0
                       AND o.id IS NULL";
        $syscontext = context_system::instance();
        $rs = $DB->get_recordset_sql($sql, array($syscontext->id));
        foreach ($rs as $orphan) {
            $file = $this->get_file_instance($orphan);
            if (!$file->is_directory()) {
                $file->delete();
            }
        }
        $rs->close();
        mtrace('done.');

        // remove trash pool files once a day
        // if you want to disable purging of trash put $CFG->fileslastcleanup=time(); into config.php
        if (empty($CFG->fileslastcleanup) or $CFG->fileslastcleanup < time() - 60*60*24) {
            require_once($CFG->libdir.'/filelib.php');
            // Delete files that are associated with a context that no longer exists.
            mtrace('Cleaning up files from deleted contexts... ', '');
            cron_trace_time_and_memory();
            $sql = "SELECT DISTINCT f.contextid
                    FROM {files} f
                    LEFT OUTER JOIN {context} c ON f.contextid = c.id
                    WHERE c.id IS NULL";
            $rs = $DB->get_recordset_sql($sql);
            if ($rs->valid()) {
                $fs = get_file_storage();
                foreach ($rs as $ctx) {
                    $fs->delete_area_files($ctx->contextid);
                }
            }
            $rs->close();
            mtrace('done.');

            mtrace('Deleting trash files... ', '');
            cron_trace_time_and_memory();
            fulldelete($this->trashdir);
            set_config('fileslastcleanup', time());
            mtrace('done.');
        }
    }

    /**
     * Get the sql formated fields for a file instance to be created from a
     * {files} and {files_refernece} join.
     *
     * @param string $filesprefix the table prefix for the {files} table
     * @param string $filesreferenceprefix the table prefix for the {files_reference} table
     * @return string the sql to go after a SELECT
     */
    private static function instance_sql_fields($filesprefix, $filesreferenceprefix) {
        // Note, these fieldnames MUST NOT overlap between the two tables,
        // else problems like MDL-33172 occur.
        $filefields = array('contenthash', 'pathnamehash', 'contextid', 'component', 'filearea',
            'itemid', 'filepath', 'filename', 'userid', 'filesize', 'mimetype', 'status', 'source',
            'author', 'license', 'timecreated', 'timemodified', 'sortorder', 'referencefileid');

        $referencefields = array('repositoryid' => 'repositoryid',
            'reference' => 'reference',
            'lastsync' => 'referencelastsync');

        // id is specifically named to prevent overlaping between the two tables.
        $fields = array();
        $fields[] = $filesprefix.'.id AS id';
        foreach ($filefields as $field) {
            $fields[] = "{$filesprefix}.{$field}";
        }

        foreach ($referencefields as $field => $alias) {
            $fields[] = "{$filesreferenceprefix}.{$field} AS {$alias}";
        }

        return implode(', ', $fields);
    }

    /**
     * Returns the id of the record in {files_reference} that matches the passed repositoryid and reference
     *
     * If the record already exists, its id is returned. If there is no such record yet,
     * new one is created (using the lastsync provided, too) and its id is returned.
     *
     * @param int $repositoryid
     * @param string $reference
     * @param int $lastsync
     * @param int $lifetime argument not used any more
     * @return int
     */
    private function get_or_create_referencefileid($repositoryid, $reference, $lastsync = null, $lifetime = null) {
        global $DB;

        $id = $this->get_referencefileid($repositoryid, $reference, IGNORE_MISSING);

        if ($id !== false) {
            // bah, that was easy
            return $id;
        }

        // no such record yet, create one
        try {
            $id = $DB->insert_record('files_reference', array(
                'repositoryid'  => $repositoryid,
                'reference'     => $reference,
                'referencehash' => sha1($reference),
                'lastsync'      => $lastsync));
        } catch (dml_exception $e) {
            // if inserting the new record failed, chances are that the race condition has just
            // occured and the unique index did not allow to create the second record with the same
            // repositoryid + reference combo
            $id = $this->get_referencefileid($repositoryid, $reference, MUST_EXIST);
        }

        return $id;
    }

    /**
     * Returns the id of the record in {files_reference} that matches the passed parameters
     *
     * Depending on the required strictness, false can be returned. The behaviour is consistent
     * with standard DML methods.
     *
     * @param int $repositoryid
     * @param string $reference
     * @param int $strictness either {@link IGNORE_MISSING}, {@link IGNORE_MULTIPLE} or {@link MUST_EXIST}
     * @return int|bool
     */
    private function get_referencefileid($repositoryid, $reference, $strictness) {
        global $DB;

        return $DB->get_field('files_reference', 'id',
            array('repositoryid' => $repositoryid, 'referencehash' => sha1($reference)), $strictness);
    }

    /**
     * Updates a reference to the external resource and all files that use it
     *
     * This function is called after synchronisation of an external file and updates the
     * contenthash, filesize and status of all files that reference this external file
     * as well as time last synchronised.
     *
     * @param int $referencefileid
     * @param int $lastsync
     * @param int $lifetime argument not used any more, liefetime is returned by repository
     * @param string $contenthash
     * @param int $filesize
     * @param int $status 0 if ok or 666 if source is missing
     * @param int $timemodified last time modified of the source, if known
     */
    public function update_references($referencefileid, $lastsync, $lifetime, $contenthash, $filesize, $status, $timemodified = null) {
        global $DB;
        $referencefileid = clean_param($referencefileid, PARAM_INT);
        $lastsync = clean_param($lastsync, PARAM_INT);
        validate_param($contenthash, PARAM_TEXT, NULL_NOT_ALLOWED);
        $filesize = clean_param($filesize, PARAM_INT);
        $status = clean_param($status, PARAM_INT);
        $params = array('contenthash' => $contenthash,
                    'filesize' => $filesize,
                    'status' => $status,
                    'referencefileid' => $referencefileid,
                    'timemodified' => $timemodified);
        $DB->execute('UPDATE {files} SET contenthash = :contenthash, filesize = :filesize,
            status = :status ' . ($timemodified ? ', timemodified = :timemodified' : '') . '
            WHERE referencefileid = :referencefileid', $params);
        $data = array('id' => $referencefileid, 'lastsync' => $lastsync);
        $DB->update_record('files_reference', (object)$data);
    }

    /**
     * Prune unreferenced file content files from local filedir.
     *
     * NOTE: this may take a very very long time.
     *
     * @param callable $progress called after every file with contenthash and deleted parameter
     */
    public function prune_unreferenced_files(callable $progress): void {
        global $DB;
        if (!$tophandle = opendir($this->filedir)) {
            return;
        }
        while (($topdir = readdir($tophandle)) !== false) {
            if (!preg_match('/^[0-9a-f][0-9a-f]$/D', $topdir)) {
                continue;
            }
            if (!$innerhandle = opendir($this->filedir . '/' . $topdir)) {
                // Skip unreadable dirs.
                continue;
            }
            while (($innerdir = readdir($innerhandle)) !== false) {
                if (!preg_match('/^[0-9a-f][0-9a-f]$/D', $innerdir)) {
                    continue;
                }
                $dir = $this->filedir . '/' . $topdir . '/' . $innerdir . '/';
                if (!$dirhandle = opendir($dir)) {
                    continue;
                }
                $usedcontenthashses = $DB->get_fieldset_sql("SELECT DISTINCT contenthash FROM {files} WHERE contenthash LIKE ? ORDER BY contenthash", [$topdir . $innerdir . '%']);
                $usedcontenthashses = array_flip($usedcontenthashses);
                while (($file = readdir($dirhandle)) !== false) {
                    if (is_dir($dir . $file)) {
                        // There should not be any dirs here!
                        continue;
                    }
                    if (!preg_match('/^([0-9a-f]){40}$/D', $file)) {
                        // Not a content file, why is it here?
                        continue;
                    }
                    if (isset($usedcontenthashses[$file])) {
                        call_user_func($progress, $file, false);
                        continue;
                    }
                    $this->deleted_file_cleanup($file);
                    if (!file_exists($dir . $file)) {
                        call_user_func($progress, $file, true);
                    } else {
                        call_user_func($progress, $file, false);
                    }
                }
                closedir($dirhandle);
            }
            closedir($innerhandle);
        }
        closedir($tophandle);
    }
}
