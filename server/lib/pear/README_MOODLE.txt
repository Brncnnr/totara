MOODLE-SPECIFIC PEAR MODIFICATIONS
==================================

XML/Parser
=================
1/ changed ereg_ to preg_
* http://cvs.moodle.org/moodle/lib/pear/XML/Parser.php.diff?r1=1.1&r2=1.2


Quickforms
==========
Full of our custom hacks, no way to upgrade to latest upstream.
Most probably we will stop using this library in the future.

MDL-20876 - replaced split() with explode() or preg_split() where appropriate
MDL-40267 - Moodle core_text strlen functions used for range rule rule to be utf8 safe.
MDL-46467 - $mform->hardfreeze causes labels to loose their for HTML attribute
MDL-52081 - made all constructors PHP7 compatible
MDL-52826 - Remove onsubmit events pointing to the global validation functions and script
            tag moved after the HTML
MDL-50484 - _getPersistantData() returns id with _persistant prefixed to element id.
MDL-55123 - corrected call to non-static functions in HTML_QuickForm to be PHP7.1-compliant
TL-14971 - replaced deprecated create_function() that was abused to do eval


Pear
====

* TL-32263 - upgraded to 1.10.13


Other changes:
 * TL-23374 lib: fix pear compatibility with PHP 7.4
 * TL-34502 lib: PHP 8.1 compatibility fixes
 * TL-34543 lib: PHP 8.1 compatibility fixes
