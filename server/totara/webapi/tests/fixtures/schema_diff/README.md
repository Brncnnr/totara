The file previous_release.graphqls is the full dev GraphQL schema from the previous Totara release, to be used with
tests checking for dangerous and breaking changes.

It should be re-generated at release time, using:
`php dev/tools/api_release_build.php`