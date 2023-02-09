@totara @totara_mobile @javascript
Feature: Confirm site info request functionality

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Enable mobile app | 1  |
    And I click on "Save changes" "button"

  Scenario: Check that API version is included
    When I am using the mobile emulator
    Then I should see "\"version\": " in the "#site_info_response" "css_element"

  Scenario: Check that the siteMaintenance flag works as expected
    And I navigate to "Server > Maintenance mode" in site administration
    And I set the following fields to these values:
      | Maintenance mode | Enable  |
    And I click on "Save changes" "button"
    When I am using the mobile emulator
    Then I should see "\"siteMaintenance\": \"1\"," in the "#site_info_response" "css_element"

  Scenario: Check that the authtype field works as expected
    And I navigate to "Plugins > Mobile > Mobile authentication" in site administration
    And I set the following fields to these values:
      | Type of login | Native  |
    And I click on "Save changes" "button"
    When I am using the mobile emulator
    Then I should see "\"auth\": \"native\"," in the "#site_info_response" "css_element"
    And I am on site homepage
    And I navigate to "Plugins > Mobile > Mobile authentication" in site administration
    And I set the following fields to these values:
      | Type of login | Webview  |
    And I click on "Save changes" "button"
    When I am using the mobile emulator
    Then I should see "\"auth\": \"webview\"," in the "#site_info_response" "css_element"

  @_file_upload
  Scenario: Check that the mobile app logo theme setting works as expected
    # Default setting
    When I am using the mobile emulator
    Then I should see "\"urlLogo\": null," in the "#site_info_response" "css_element"
    # Custom setting - Pending an admin filepicker behat step

  Scenario: Check that the mobile primary colour theme setting works as expected
    # Default setting
    When I am using the mobile emulator
    Then I should see "\"colorPrimary\": \"#69BD45\"," in the "#site_info_response" "css_element"
    # Custom setting - Pending an admin colour selector behat step

  Scenario: Check that the mobile text colour theme setting works as expected
    # Default setting
    When I am using the mobile emulator
    Then I should see "\"colorText\": \"#FFFFFF\"" in the "#site_info_response" "css_element"
    # Custom setting
    When I am on site homepage
    And I navigate to "Plugins > Mobile > Mobile theme" in site administration
    And I set the following fields to these values:
      | Text colour | Black |
    And I click on "Save changes" "button"
    When I am using the mobile emulator
    Then I should see "\"colorText\": \"#000000\"" in the "#site_info_response" "css_element"
