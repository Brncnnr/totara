@engage @totara @totara_msteams @javascript
Feature: Customise Teams integration settings
  As an admin
  I would like to have access to a frontend admin settings interface
  So that I can enable and configure the integration to suit the organisation

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I am on homepage
    And I navigate to "Microsoft Teams integration" node in "Site administration > Microsoft Teams"

  Scenario: msteams101: Check front-end validation on the site admin page
    When I set the field "Short name" to "it's a very long short app name"
    And I press "Save changes"
    Then I should see "Some settings were not changed due to an error"
    And I should see "Maximum of 30 characters"

    And I set the field "Short name" to "Totara"
    When I set the field "Full name" to "this is a really really long full application name that is excruciatingly longer than 100 characters."
    And I press "Save changes"
    Then I should see "Some settings were not changed due to an error"
    And I should see "Maximum of 100 characters"

    And I set the field "Full name" to ""
    When I set the field "Publisher's name" to "this is a really really long name"
    And I press "Save changes"
    Then I should see "Some settings were not changed due to an error"
    And I should see "Maximum of 32 characters"

    And I set the field "Publisher's name" to "Totara"
    When I set the field "Microsoft Partner Network ID" to "31415926535"
    And I press "Save changes"
    Then I should see "Some settings were not changed due to an error"
    And I should see "Maximum of 10 characters"

    And I set the field "Microsoft Partner Network ID" to ""
    When I set the field "Publisher's website" to "kia://ora/"
    And I press "Save changes"
    Then I should see "Some settings were not changed due to an error"
    And I should see "This value is not valid"

    And I set the field "Publisher's website" to ""
    When I set the field "Privacy policy" to "kia://ora/"
    And I press "Save changes"
    Then I should see "Some settings were not changed due to an error"
    And I should see "This value is not valid"

    And I set the field "Privacy policy" to ""
    When I set the field "Terms of use" to "kia://ora/"
    And I press "Save changes"
    Then I should see "Some settings were not changed due to an error"
    And I should see "This value is not valid"

  Scenario: msteams102: Manifest download
    When I set the following fields to these values:
      | Short name          | Mistletoetara                        |
      | Full name           |                                      |
      | Manifest app ID     | 31415926-5358-9793-2384-626433832795 |
      | Package name        | com.totaralearning.microsoft.msteams |
      | Publisher's name    | Totara Learn                         |
      | Publisher's website | https://example.com/totara/          |
      | Privacy policy      | https://example.com/privacy/         |
      | Terms of use        | https://example.com/terms/           |
    And I press "Save changes"
    Then I should see "Changes saved"
    And I should not see "Some settings were not changed due to an error"

    When I navigate to "Totara app installation" node in "Site administration > Microsoft Teams"
    Then I should see "One or more settings are not correctly set"
    And I should see "Failed" in the "Manifest package name" "table_row"
    And I should see "Failed" in the "Allow frame embedding" "table_row"
    And I should see "Skipped" in the "Allow public access to catalogue" "table_row"

    When I follow "Manifest package name"
    And I set the field "Package name" to "com.totaralearning.msteams.for.behat"
    And I press "Save changes"
    And I am on homepage
    And I navigate to "Totara app installation" node in "Site administration > Microsoft Teams"
    Then I should see "One or more settings are not correctly set"
    And I should see "OK" in the "Manifest package name" "table_row"
    And I should see "Failed" in the "Allow frame embedding" "table_row"
    And I should see "Skipped" in the "Allow public access to catalogue" "table_row"

    When I follow "Allow frame embedding"
    And I set the field "Allow frame embedding" to "1"
    And I press "Save changes"
    And I am on homepage
    And I navigate to "Totara app installation" node in "Site administration > Microsoft Teams"
    Then I should see "All settings have been verified, you can download the manifest file"
    And I should see "OK" in the "Manifest package name" "table_row"
    And I should see "OK" in the "Allow frame embedding" "table_row"
    But I should see "Skipped" in the "Allow public access to catalogue" "table_row"

    When I follow "Allow public access to catalogue"
    And I set the field "Allow public access to catalogue" to "1"
    And I press "Save changes"
    And I am on homepage
    And I navigate to "Totara app installation" node in "Site administration > Microsoft Teams"
    Then I should see "All settings have been verified, you can download the manifest file"
    And I should see "OK" in the "Manifest package name" "table_row"
    And I should see "OK" in the "Allow frame embedding" "table_row"
    And I should see "OK" in the "Allow public access to catalogue" "table_row"

    When I click on "Download manifest file" "link_or_button"
    # Then I should successfully download a file
