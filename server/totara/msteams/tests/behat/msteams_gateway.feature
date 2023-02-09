@engage @totara @totara_msteams @javascript
Feature: Customise msteams gateway settings
  As an admin
  I would like to have access to a frontend admin settings interface
  So that I can enable and configure the msteams gateway

  Scenario: admin set up the gateway
    Given I log in as "admin"
    And the following config values are set as admin:
      | msteams_gateway_url         | www.example.com  |
      | msteams_gateway_private_key | somew_random_key |
    And I am on homepage
    And I navigate to "Microsoft Teams integration" node in "Site administration > Microsoft Teams"
    And I should not see "Set up single sign-on"
    And I should not see "Set up the conversational bot"
    And I should not see "Publisher information"
    And I should not see "totara_msteams | manifest_app_id"
    And I should see "totara_msteams | domain_name"
    And I set the field "Domain name" to "123"
    And I press "Save changes"
    Then I should see "The site could not be connected. Please check your domain name again."
    And I set the field "Domain name" to " "
    And I press "Save changes"
    And I should see "Changes saved"
    Then I should not see "The site could not be connected. Please check your domain name again."
    And I set the field "Domain name" to "example.com"
    When I press "Save changes"
    Then I should see "Changes saved"

  Scenario: admin only can see gateway settings without gateway configuration
    Given I log in as "admin"
    And I am on homepage
    And I navigate to "Microsoft Teams integration" node in "Site administration > Microsoft Teams"
    Then I should see "Set up the conversational bot"
    And I should see "Set up single sign-on"
    And I should see "Publisher information"
    And I should see "totara_msteams | manifest_app_id"
    And I should not see "totara_msteams | domain_name"