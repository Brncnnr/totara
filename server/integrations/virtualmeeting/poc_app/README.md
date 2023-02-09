# Local plugin for development and testing

Totara provides two additional virtualmeeting plugins that are not visible on a production site.
* poc_app - Fake Dev App plugin
* poc_user - Fake Dev User plugin

These plugins are specifically designed for integration development and testing. They are always enabled under PHPUnit or Behat.

It is possible to enable them on a website by adding the following lines to config.php _before site installation_.
This allows developers to try seminar virtualmeeting integration without having a paid account on Microsoft Teams or Zoom.

Switching plugin development mode on a running site is **not supported**. Site reinstallation is required.

```php
// Turn on plugin development mode for virtualmeeting plugins.
$CFG->virtual_meeting_poc_plugin = true;

// Either one of follows are required.
$CFG->debug = (E_ALL | E_STRICT);
$CFG->sitetype = 'development';
```
