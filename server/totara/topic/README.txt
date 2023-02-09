TOPIC MODULES
-----------------

If you want to allow your module/component to make use of this module. There should be an expected class that is
extending `\totara_topic\resolver\resolver`, which it must be in a directory `totara_topic/resolver`,
located at `/your/instance/root/component/classes`.

For example: to get integrate a component - core_course, the resolver should be located
at `/your/instance/root/course/classes/totara_topic/resolver/your_resolver.php`.

Your own resolver will allow you to modify the process, validation of assign the topic into your component.