Description of XHProf 2.3.5 library/viewer import into Totara.

This project is a fork of https://github.com/longxinH/xhprof with our own additions to it.

Our changes:  Look for "moodle" in code (commit #3 - always mimic from current moodle.git master):
 * xhprof_html/index.php  ----|
 * xhprof_html/callgraph.php -|=> Changed to check for access and use our own DB iXHProfRuns implementation (xhprof_totara_run)
 * xhprof_html/typeahead.php -|
 * xhprof_html/css/xhprof.css: Minor tweaks to report styles
 * xhprof_lib/utils/callgraph_utils.php: Modified to use $CFG->pathtodot
 * xhprof_lib/utils/callgraph_utils.php: Modified xhprof_generate_image_by_dot to use the command library

20101122 - MDL-24600 - Eloy Lafuente (stronk7): Original import of 0.9.2 release
20110318 - MDL-26891 - Eloy Lafuente (stronk7): Implemented earlier profiling runs
20130621 - MDL-39733 - Eloy Lafuente (stronk7): Export & import of profiling runs
20160721 - MDL-55292 - Russell Smith (mr-russ): Add support for tideways profiler collection for PHP7
20170731 - TL-12380 - Brendan Cox: Modified xhprof_generate_image_by_dot() to use the new command library.
20171002 - MDL-60313 - Marina Glancy (marinaglancy): Upgrade to 0.9.4 release; patched for PHP7.2
20211109 - TL-28228 - moved to composer and upgraded to 2.3.4
20220119 - TL-33463 - Upgraded to 2.3.5 and restored to original state (minimise changes)
