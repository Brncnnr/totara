@engage @totara @totara_msteams @javascript
Feature: Microsoft Teams settings pages will not show if the feature is disabled.

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And I enable the "totara_msteams" advanced feature
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user1@example.com |

  Scenario: msteams111: Disabling the workspace feature will remove/hide the message output option
    Given I log in as "admin"

    # Feature on
    When I open the notification popover
    And I follow "Legacy notification preferences"
    Then I should see "Microsoft Teams"

    When I navigate to "Default message outputs" node in "Site administration > Plugins > Message outputs"
    Then I should see "Microsoft Teams"

    When I toggle open the admin quick access menu
    And I follow "Administration overview"
    Then I should see "Microsoft Teams"
    And I should see "Microsoft Teams integration"
    And I should see "Totara app installation"

    # Feature off
#    When I set the following administration settings values:
#      | enabletotara_msteams | Disable |
#    And I open the notification popover
#    And I follow "Legacy notification preferences"
#    Then I should not see "Microsoft Teams"
#
#    When I navigate to "Default message outputs" node in "Site administration > Plugins > Message outputs"
#    Then I should not see "Microsoft Teams"
#
#    When I toggle open the admin quick access menu
#    And I follow "Administration overview"
#    Then I should not see "Microsoft Teams"
#    And I should not see "Microsoft Teams integration"
#    And I should not see "Totara app installation"
