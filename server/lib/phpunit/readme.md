PHPUnit testing support in Totara
==================================


Documentation
-------------
* [PHPUnit integration](https://help.totaralearning.com/display/TDDM/Unit+testing+in+Totara+13)
* [PHPUnit online documentation](http://www.phpunit.de/manual/current/en/)
* [Composer dependency manager](http://getcomposer.org/)


Configure your server
---------------------
You need to create a new dataroot directory and specify a separate database prefix for the test environment,
see test/phpunit/config.example.php for more information.


Initialise the test environment
-------------------------------
Before first execution and after every upgrade the PHPUnit test environment needs to be initialised,
this command downloads composer and builds the phpunit.xml configuration files.

* execute `php test/phpunit/phpunit.php init`


Execute tests
--------------
* change to test directory `cd test/phpunit` then,
* running all tests `./vendor/bin/phpunit`
* running a test suite `./vendor/bin/phpunit --testsuite="mod_facetoface_testsuite"`
* running a single testcase `./vendor/bin/phpunit ../../server/user/tests/userdata_username_test.php`
* running a single test `./vendor/bin/phpunit --filter=test_export ../../server/user/tests/userdata_username_test.php`
* running a group of tests `./vendor/bin/phpunit --group totara_userdata`
* it is also possible to create custom configuration files in xml format and use `./vendor/bin/phpunit -c mytestsuites.xml`


How to add more tests?
----------------------
1. create `tests/` directory in your add-on
2. add test file, for example `local/mytest/tests/my_test.php` file with `local_my_testcase` class that extends `\core_phpunit\testcase`
3. add some test_*() methods
4. execute `php test/phpunit/phpunit.php init` to get the plugin tests included in main phpunit.xml configuration file
5. execute your new test case `php test/phpunit/phpunit.php run local/mytest/tests/my_test.php`


Windows support
---------------
* use `\` instead of `/` in paths in examples above
