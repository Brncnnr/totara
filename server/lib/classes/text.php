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
 * Defines string apis
 *
 * @package    core
 * @copyright  (C) 2001-3001 Eloy Lafuente (stronk7) {@link http://contiento.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * defines string api's for manipulating strings
 *
 * This class is used to manipulate strings under Moodle 1.6 an later. As
 * utf-8 text become mandatory a pool of safe functions under this encoding
 * become necessary. The name of the methods is exactly the
 * same than their PHP originals.
 *
 * @package   core
 * @category  string
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_text {

    /**
     * @var string[] Array of strings representing Unicode non-characters
     */
    protected static $noncharacters;

    /**
     * Totara: list of encoding mappings, copied from Typo3
     *
     * @var string[]
     */
    protected static $synonyms = [
        'us' => 'ascii',
        'us-ascii' => 'ascii',
        'cp819' => 'iso-8859-1',
        'ibm819' => 'iso-8859-1',
        'iso-ir-100' => 'iso-8859-1',
        'iso-ir-101' => 'iso-8859-2',
        'iso-ir-109' => 'iso-8859-3',
        'iso-ir-110' => 'iso-8859-4',
        'iso-ir-144' => 'iso-8859-5',
        'iso-ir-127' => 'iso-8859-6',
        'iso-ir-126' => 'iso-8859-7',
        'iso-ir-138' => 'iso-8859-8',
        'iso-ir-148' => 'iso-8859-9',
        'iso-ir-157' => 'iso-8859-10',
        'iso-ir-179' => 'iso-8859-13',
        'iso-ir-199' => 'iso-8859-14',
        'iso-ir-203' => 'iso-8859-15',
        'csisolatin1' => 'iso-8859-1',
        'csisolatin2' => 'iso-8859-2',
        'csisolatin3' => 'iso-8859-3',
        'csisolatin5' => 'iso-8859-9',
        'csisolatin8' => 'iso-8859-14',
        'csisolatin9' => 'iso-8859-15',
        'csisolatingreek' => 'iso-8859-7',
        'iso-celtic' => 'iso-8859-14',
        'latin1' => 'iso-8859-1',
        'latin2' => 'iso-8859-2',
        'latin3' => 'iso-8859-3',
        'latin5' => 'iso-8859-9',
        'latin6' => 'iso-8859-10',
        'latin8' => 'iso-8859-14',
        'latin9' => 'iso-8859-15',
        'l1' => 'iso-8859-1',
        'l2' => 'iso-8859-2',
        'l3' => 'iso-8859-3',
        'l5' => 'iso-8859-9',
        'l6' => 'iso-8859-10',
        'l8' => 'iso-8859-14',
        'l9' => 'iso-8859-15',
        'cyrillic' => 'iso-8859-5',
        'arabic' => 'iso-8859-6',
        'tis-620' => 'iso-8859-11',
        'win874' => 'windows-874',
        'win1250' => 'windows-1250',
        'win1251' => 'windows-1251',
        'win1252' => 'windows-1252',
        'win1253' => 'windows-1253',
        'win1254' => 'windows-1254',
        'win1255' => 'windows-1255',
        'win1256' => 'windows-1256',
        'win1257' => 'windows-1257',
        'win1258' => 'windows-1258',
        'cp1250' => 'windows-1250',
        'cp1251' => 'windows-1251',
        'cp1252' => 'windows-1252',
        'ms-ee' => 'windows-1250',
        'ms-ansi' => 'windows-1252',
        'ms-greek' => 'windows-1253',
        'ms-turk' => 'windows-1254',
        'winbaltrim' => 'windows-1257',
        'koi-8ru' => 'koi-8r',
        'koi8r' => 'koi-8r',
        'cp878' => 'koi-8r',
        'mac' => 'macroman',
        'macintosh' => 'macroman',
        'euc-cn' => 'gb2312',
        'x-euc-cn' => 'gb2312',
        'euccn' => 'gb2312',
        'cp936' => 'gb2312',
        'big-5' => 'big5',
        'cp950' => 'big5',
        'eucjp' => 'euc-jp',
        'sjis' => 'shift_jis',
        'shift-jis' => 'shift_jis',
        'cp932' => 'shift_jis',
        'cp949' => 'euc-kr',
        'utf7' => 'utf-7',
        'utf8' => 'utf-8',
        'utf16' => 'utf-16',
        'utf32' => 'utf-32',
        'ucs2' => 'ucs-2',
        'ucs4' => 'ucs-4',
    ];

    /**
     * Reset internal textlib caches.
     * @deprecated since Totara 13
     */
    public static function reset_caches() {
        // Totara: keep this method for compatibility with Moodle,
        //         debugging message is not necessary here and there is no need to remove usage.
    }

    /**
     * Standardise charset name
     *
     * Please note it does not mean the returned charset is actually supported.
     *
     * @static
     * @param string $charset raw charset name
     * @return string normalised lowercase charset name, utf-8 if no charset specified
     */
    public static function parse_charset($charset) {
        if ($charset === 'utf8' or $charset === 'utf-8' or $charset === 'UTF8' or $charset === 'UTF-8') {
            return 'utf-8';
        }

        $charset = trim(strtolower($charset));
        if ($charset === '') {
            // Fallback to utf-8.
            return 'utf-8';
        }

        if (isset(self::$synonyms[$charset])) {
            return self::$synonyms[$charset];
        }

        if (preg_match('/^(cp|win|windows)-?(12[0-9]{2})$/', $charset, $matches)) {
            return 'windows-'.$matches[2];
        }

        return $charset;
    }

    /**
     * Converts the text between different encodings. It uses iconv extension with //IGNORE parameter.
     *
     * @param string $text
     * @param string $fromCS source encoding
     * @param string $toCS result encoding
     * @return string|bool converted string or false on error
     */
    public static function convert($text, $fromCS, $toCS='utf-8') {
        $text = (string)$text; // we can work only with strings
        if ($text === '') {
            return '';
        }

        $fromCS = self::parse_charset($fromCS);
        $toCS   = self::parse_charset($toCS);

        if ($fromCS === 'utf-8') {
            $text = fix_utf8($text);
            if ($toCS === 'utf-8') {
                return $text;
            }
        }

        if ($toCS === 'ascii') {
            // Try to normalize the conversion a bit.
            $text = self::specialtoascii($text, $fromCS);
        }

        // Totara: rely on iconv to do the conversion and fall back to mb_string if it fails.
        $result = iconv($fromCS, $toCS.'//IGNORE', $text); // Do not hide any errors here!
        if ($result !== false) {
            return $result;
        }

        return mb_convert_encoding($text, $toCS, $fromCS);
    }

    /**
     * Multibyte safe substr() function, uses mbstring or iconv.
     *
     * @param string $text string to truncate
     * @param int $start negative value means from end
     * @param int $len maximum length of characters beginning from start
     * @param string $charset encoding of the text
     * @return string portion of string specified by the $start and $len
     */
    public static function substr($text, $start, $len=null, $charset='utf-8') {
        $charset = self::parse_charset($charset);

        if ($charset === 'utf-8') {
            if ($len === null) {
                // just in case some weird code messed this up.
                mb_internal_encoding('UTF-8');
                return mb_substr($text, $start);
            }
            return mb_substr($text, $start, $len, 'UTF-8');
        }

        if ($len === null) {
            $len = iconv_strlen($text, $charset);
        }
        return iconv_substr($text, $start, $len, $charset);
    }

    /**
     * Truncates a string to no more than a certain number of bytes in a multi-byte safe manner.
     * UTF-8 only!
     *
     * Many of the other charsets we test for (like ISO-2022-JP and EUC-JP) are not supported
     * and will give invalid results, so we are supporting UTF-8 only.
     *
     * @param string $string String to truncate
     * @param int $bytes Maximum length of bytes in the result
     * @return string Portion of string specified by $bytes
     * @since Moodle 3.1
     */
    public static function str_max_bytes($string, $bytes) {
        return mb_strcut($string, 0, $bytes, 'UTF-8');
    }

    /**
     * Finds the last occurrence of a character in a string within another.
     * UTF-8 ONLY safe mb_strrchr().
     *
     * @param string $haystack The string from which to get the last occurrence of needle.
     * @param string $needle The string to find in haystack.
     * @param boolean $part If true, returns the portion before needle, else return the portion after (including needle).
     * @return string|false False when not found.
     * @since Moodle 2.4.6, 2.5.2, 2.6
     */
    public static function strrchr($haystack, $needle, $part = false) {
        return mb_strrchr($haystack, $needle, $part, 'UTF-8');
    }

    /**
     * Multibyte safe strlen() function, uses mbstring or iconv.
     *
     * @param string $text input string
     * @param string $charset encoding of the text
     * @return int number of characters
     */
    public static function strlen($text, $charset='utf-8') {
        // Totara: Null has no length, and cannot be passed into strlen functions
        if ($text === null) {
            return 0;
        }

        $charset = self::parse_charset($charset);

        if ($charset === 'utf-8') {
            return mb_strlen($text, 'UTF-8');
        }

        return iconv_strlen($text, $charset);
    }

    /**
     * Multibyte safe strtolower() function, uses mbstring.
     *
     * @param string $text input string
     * @param string $charset encoding of the text (may not work for all encodings)
     * @return string lower case text
     */
    public static function strtolower($text, $charset='utf-8') {
        $charset = self::parse_charset($charset);

        if ($charset === 'utf-8') {
            // Totara: Prevent passing null as input parameter in PHP 8.1
            return mb_strtolower($text ?? '', 'UTF-8');
        }

        $text = self::convert($text, $charset);
        // Totara: Prevent passing null as input parameter in PHP 8.1
        $result = mb_strtolower($text ?? '', 'UTF-8');
        return self::convert($result, 'UTF-8', $charset);
    }

    /**
     * Multibyte safe strtoupper() function, uses mbstring.
     *
     * @param string $text input string
     * @param string $charset encoding of the text (may not work for all encodings)
     * @return string upper case text
     */
    public static function strtoupper($text, $charset='utf-8') {
        $charset = self::parse_charset($charset);

        if ($charset === 'utf-8') {
            return mb_strtoupper($text, 'UTF-8');
        }

        $text = self::convert($text, $charset);
        $result = mb_strtoupper($text, 'UTF-8');
        return self::convert($result, 'UTF-8', $charset);
    }

    /**
     * Find the position of the first occurrence of a substring in a string.
     * UTF-8 ONLY safe strpos(), uses mbstring, falls back to iconv.
     *
     * @param string $haystack the string to search in
     * @param string $needle one or more charachters to search for
     * @param int $offset offset from begining of string
     * @return int the numeric position of the first occurrence of needle in haystack.
     */
    public static function strpos($haystack, $needle, $offset=0) {
        return mb_strpos($haystack, $needle, $offset, 'UTF-8');
    }

    /**
     * Find the position of the last occurrence of a substring in a string
     * UTF-8 ONLY safe strrpos(), uses mbstring, falls back to iconv.
     *
     * @param string $haystack the string to search in
     * @param string $needle one or more charachters to search for
     * @return int the numeric position of the last occurrence of needle in haystack
     */
    public static function strrpos($haystack, $needle) {
        // Totara: Pass the real default value instead of null
        return mb_strrpos($haystack, $needle, 0, 'UTF-8');
    }

    /**
     * Reverse UTF-8 multibytes character sets (used for RTL languages)
     * (We only do this because there is no mb_strrev or iconv_strrev)
     *
     * @param string $str the multibyte string to reverse
     * @return string the reversed multi byte string
     */
    public static function strrev($str) {
        preg_match_all('/./us', $str, $ar);
        return join('', array_reverse($ar[0]));
    }

    /**
     * Try to convert upper unicode characters to plain ascii,
     * the returned string may contain unconverted unicode characters.
     *
     * @param string $text input string
     * @param string $charset encoding of the text
     * @return string converted ascii string
     */
    public static function specialtoascii($text, $charset='utf-8') {
        $charset = self::parse_charset($charset);
        if ($charset !== 'utf-8') {
            $text = self::convert($text, $charset, 'utf-8');
        }

        // Everybody should have intl installed!
        if (class_exists('Transliterator')) {
            $transliterator = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;', Transliterator::FORWARD);
            $result = $transliterator->transliterate($text);
            if ($result !== false) {
                if ($charset === 'utf-8') {
                    return $result;
                }
                return self::convert($result, 'UTF-8', $charset);
            }
        }

        if ('glibc' === ICONV_IMPL) {
            $result = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        } else {
            $result = iconv('UTF-8', 'ASCII//IGNORE//TRANSLIT', $text);
        }
        if ($result === false) {
            $result = $text;
        }

        if ($charset === 'utf-8') {
            return $result;
        }
        return self::convert($result, 'UTF-8', $charset);
    }

    /**
     * Generate a correct base64 encoded header to be used in MIME mail messages.
     * This function seems to be 100% compliant with RFC1342. Credits go to:
     * paravoid (http://www.php.net/manual/en/function.mb-encode-mimeheader.php#60283).
     *
     * @param string $text input string
     * @param string $charset encoding of the text
     * @return string base64 encoded header
     */
    public static function encode_mimeheader($text, $charset='utf-8') {
        if (empty($text)) {
            return (string)$text;
        }
        // Normalize charset
        $charset = self::parse_charset($charset);
        // If the text is pure ASCII, we don't need to encode it
        if (self::convert($text, $charset, 'ascii') == $text) {
            return $text;
        }
        // Although RFC says that line feed should be \r\n, it seems that
        // some mailers double convert \r, so we are going to use \n alone
        $linefeed="\n";
        // Define start and end of every chunk
        $start = "=?$charset?B?";
        $end = "?=";
        // Accumulate results
        $encoded = '';
        // Max line length is 75 (including start and end)
        $length = 75 - strlen($start) - strlen($end);
        // Multi-byte ratio
        $multilength = self::strlen($text, $charset);
        // Detect if strlen and friends supported
        if ($multilength === false) {
            if ($charset == 'GB18030' or $charset == 'gb18030') {
                while (strlen($text)) {
                    // try to encode first 22 chars - we expect most chars are two bytes long
                    if (preg_match('/^(([\x00-\x7f])|([\x81-\xfe][\x40-\x7e])|([\x81-\xfe][\x80-\xfe])|([\x81-\xfe][\x30-\x39]..)){1,22}/m', $text, $matches)) {
                        $chunk = $matches[0];
                        $encchunk = base64_encode($chunk);
                        if (strlen($encchunk) > $length) {
                            // find first 11 chars - each char in 4 bytes - worst case scenario
                            preg_match('/^(([\x00-\x7f])|([\x81-\xfe][\x40-\x7e])|([\x81-\xfe][\x80-\xfe])|([\x81-\xfe][\x30-\x39]..)){1,11}/m', $text, $matches);
                            $chunk = $matches[0];
                            $encchunk = base64_encode($chunk);
                        }
                        $text = substr($text, strlen($chunk));
                        $encoded .= ' '.$start.$encchunk.$end.$linefeed;
                    } else {
                        break;
                    }
                }
                $encoded = trim($encoded);
                return $encoded;
            } else {
                return false;
            }
        }
        $ratio = $multilength / strlen($text);
        // Base64 ratio
        $magic = $avglength = floor(3 * $length * $ratio / 4);
        // basic infinite loop protection
        $maxiterations = strlen($text)*2;
        $iteration = 0;
        // Iterate over the string in magic chunks
        for ($i=0; $i <= $multilength; $i+=$magic) {
            if ($iteration++ > $maxiterations) {
                return false; // probably infinite loop
            }
            $magic = $avglength;
            $offset = 0;
            // Ensure the chunk fits in length, reducing magic if necessary
            do {
                $magic -= $offset;
                $chunk = self::substr($text, $i, $magic, $charset);
                $chunk = base64_encode($chunk);
                $offset++;
            } while (strlen($chunk) > $length);
            // This chunk doesn't break any multi-byte char. Use it.
            if ($chunk)
                $encoded .= ' '.$start.$chunk.$end.$linefeed;
        }
        // Strip the first space and the last linefeed
        $encoded = substr($encoded, 1, -strlen($linefeed));

        return $encoded;
    }

    /**
     * Converts all the named and numeric entities &#nnnn; or &#xnnn; to UTF-8
     *
     * @param string $str input string
     * @param boolean $htmlent convert also html entities (defaults to true)
     * @return string encoded UTF-8 string
     */
    public static function entities_to_utf8($str, $htmlent=true) {
        $str = (string)$str;

        if (!$htmlent) {
            $callback1 = function($matches) {return core_text::code2utf8(hexdec($matches[1]));};
            $callback2 = function($matches) {return core_text::code2utf8($matches[1]);};

            $result = (string)$str;
            $result = preg_replace_callback('/&#x([0-9a-f]+);/i', $callback1, $result);
            $result = preg_replace_callback('/&#([0-9]+);/', $callback2, $result);

            return fix_utf8($result);
        }

        // Totara: use built-in function for better performance.
        return html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Converts all Unicode chars > 127 to numeric entities &#nnnn; or &#xnnn;.
     *
     * @param string $str input string
     * @param boolean $dec output decadic only number entities
     * @param boolean $nonnum remove all non-numeric entities
     * @return string converted string
     */
    public static function utf8_to_entities($str, $dec=false, $nonnum=false) {
        if ($nonnum) {
            $str = self::entities_to_utf8($str, true);
        }

        $chars = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($chars as &$char) {
            $ord = mb_ord($char, 'UTF-8');
            if ($ord > 127) {
                if ($dec) {
                    $char = '&#' . $ord . ';';
                } else {
                    $char = '&#x' . dechex($ord) . ';';
                }
            }
        }
        $result = implode('', $chars);

        if ($dec) {
            $callback = function ($matches) {
                return '&#' . (hexdec($matches[1])) . ';';
            };
            $result = preg_replace_callback('/&#x([0-9a-f]+);/i', $callback, $result);
        }

        return $result;
    }

    /**
     * Removes the BOM from unicode string {@link http://unicode.org/faq/utf_bom.html}
     *
     * @param string $str input string
     * @return string
     */
    public static function trim_utf8_bom($str) {
        $bom = "\xef\xbb\xbf";
        if (strpos($str, $bom) === 0) {
            return substr($str, strlen($bom));
        }
        return $str;
    }

    /**
     * There are a number of Unicode non-characters including the byte-order mark (which may appear
     * multiple times in a string) and also other ranges. These can cause problems for some
     * processing.
     *
     * This function removes the characters using string replace, so that the rest of the string
     * remains unchanged.
     *
     * @param string $value Input string
     * @return string Cleaned string value
     * @since Moodle 3.3.6
     */
    public static function remove_unicode_non_characters($value) {
        // Set up list of all Unicode non-characters for fast replacing.
        if (!self::$noncharacters) {
            self::$noncharacters = [];
            // This list of characters is based on the Unicode standard. It includes the last two
            // characters of each code planes 0-16 inclusive...
            for ($plane = 0; $plane <= 16; $plane++) {
                $base = ($plane === 0 ? '' : dechex($plane));
                self::$noncharacters[] = html_entity_decode('&#x' . $base . 'fffe;');
                self::$noncharacters[] = html_entity_decode('&#x' . $base . 'ffff;');
            }
            // ...And the character range U+FDD0 to U+FDEF.
            for ($char = 0xfdd0; $char <= 0xfdef; $char++) {
                self::$noncharacters[] = html_entity_decode('&#x' . dechex($char) . ';');
            }
        }

        // Do character replacement.
        return str_replace(self::$noncharacters, '', $value);
    }

    /**
     * Returns encoding options for select boxes, utf-8 and platform encoding first
     *
     * @return array encodings
     */
    public static function get_encodings() {
        $encodings = array();
        $encodings['UTF-8'] = 'UTF-8';
        $winenc = strtoupper(get_string('localewincharset', 'langconfig'));
        if ($winenc != '') {
            $encodings[$winenc] = $winenc;
        }
        $nixenc = strtoupper(get_string('oldcharset', 'langconfig'));
        $encodings[$nixenc] = $nixenc;

        foreach (self::$synonyms as $enc) {
            $enc = strtoupper($enc);
            $encodings[$enc] = $enc;
        }
        return $encodings;
    }

    /**
     * Returns the utf8 string corresponding to the unicode value
     * (from php.net, courtesy - romans@void.lv)
     *
     * @param  int    $num one unicode value
     * @return string the UTF-8 char corresponding to the unicode value
     */
    public static function code2utf8($num) {
        if ($num < 128) {
            return chr($num);
        }
        if ($num < 2048) {
            return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
        }
        if ($num < 65536) {
            return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
        }
        if ($num < 2097152) {
            return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
        }
        return '';
    }

    /**
     * Returns the code of the given UTF-8 character
     *
     * @param  string $utf8char one UTF-8 character
     * @return int    the code of the given character
     */
    public static function utf8ord($utf8char) {
        if ($utf8char == '') {
            return 0;
        }
        $ord0 = ord($utf8char[0]);
        if ($ord0 >= 0 && $ord0 <= 127) {
            return $ord0;
        }
        $ord1 = ord($utf8char[1]);
        if ($ord0 >= 192 && $ord0 <= 223) {
            return ($ord0 - 192) * 64 + ($ord1 - 128);
        }
        $ord2 = ord($utf8char[2]);
        if ($ord0 >= 224 && $ord0 <= 239) {
            return ($ord0 - 224) * 4096 + ($ord1 - 128) * 64 + ($ord2 - 128);
        }
        $ord3 = ord($utf8char[3]);
        if ($ord0 >= 240 && $ord0 <= 247) {
            return ($ord0 - 240) * 262144 + ($ord1 - 128 )* 4096 + ($ord2 - 128) * 64 + ($ord3 - 128);
        }
        return false;
    }

    /**
     * Makes first letter of each word capital - words must be separated by spaces.
     * Use with care, this function does not work properly in many locales!!!
     *
     * @param string $text input string
     * @return string
     */
    public static function strtotitle($text) {
        return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Converts a float to a locale independent string.
     *
     * Casting floats to strings can produce different results depending on the current locale.
     * This can lead to broken queries. Therefore we want to make sure we always cast in the same way
     *
     * See for more background: https://wiki.php.net/rfc/locale_independent_float_to_string
     *
     * @param float $float_value
     * @return string
     */
    public static function float2str(float $float_value): string {
        $locale = localeconv();

        $float_value = str_replace(
            [
                $locale['thousands_sep'],
                $locale['mon_thousands_sep'],
                $locale['decimal_point'],
                $locale['mon_decimal_point'],
            ],
            [
                '',
                '',
                '.',
                '.',
            ],
            $float_value
        );

        return $float_value;
    }
}
