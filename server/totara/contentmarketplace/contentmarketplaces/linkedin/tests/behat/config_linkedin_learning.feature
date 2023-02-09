@totara @totara_contentmarketplace @contentmarketplace_linkedin @javascript
Feature: Configure linkedin settings
  Scenario: Configure linkedin learning client id and secret
    Given I set up the "linkedin" content marketplace plugin
    When I log in as "admin"
    And I navigate to "Plugins > Content marketplace > LinkedIn Learning settings" in site administration
    And I should see "Client ID"
    And I should see "Client secret"
    And I set the field "Client ID" to "clientid"
    And I set the field "Client secret" to "clientsecret"
    When I click on "Save changes" "button"
    Then I should see "Changes saved"
    And the field "Client ID" matches value "clientid"
    And the field "Client secret" matches value "clientsecret"

  Scenario: System user assigned on site manager can manage plugin
    Given I set up the "linkedin" content marketplace plugin
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | user      | one      | user1@example.com |
    And the following "system role assigns" exist:
      | user     | role    |
      | user1    | manager |
    When I log in as "user1"
    And I navigate to "Plugins > Content marketplace > Manage content marketplaces" in site administration
    Then I should see "LinkedIn Learning settings"