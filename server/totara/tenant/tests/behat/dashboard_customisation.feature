@totara @tenant @totara_tenant @totara_dashboard @javascript
Feature: Tenant dashboard customisation by users

  As a tenant participant
  In order to simplify access to my relevant information
  I want to be able to cusotmise my dashboards

  Background:
    Given I am on a totara site
    And tenant support is enabled without tenant isolation
    And the following "tenants" exist:
      | name          | idnumber | dashboardname      |
      | First Tenant  | ten1     | First T Dashboard  |
      | Second Tenant | ten2     | Second T Dashboard |
    And the following "users" exist:
      | username          | firstname | lastname    | tenantmember | tenantparticipant |
      | user1             | First     | Member      | ten1         |                   |
      | participant1      | First     | Participant |              | ten1, ten2        |
    And I log in as "admin"

    And I am on "Dashboard" page
    And I press "Manage dashboards"
    And I click on "My Learning" "link" in the "My Learning" "table_row"
    And I press "Blocks editing on"
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Content                      | My Learning Default Content       |
      | Region                       | Main                              |
    And I press "Save changes"
    And I press "Blocks editing off"

    And I am on "Dashboard" page
    And I press "Manage dashboards"
    And I click on "First T Dashboard" "link" in the "First T Dashboard" "table_row"
    And I press "Blocks editing on"
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Content                      | First T Dashboard Default Content |
      | Region                       | Main                              |
    And I press "Save changes"
    And I press "Blocks editing off"

    And I am on "Dashboard" page
    And I press "Manage dashboards"
    And I click on "Second T Dashboard" "link" in the "Second T Dashboard" "table_row"
    And I press "Blocks editing on"
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Content                      | Second T Dashboard Default Content |
      | Region                       | Main                               |
    And I press "Save changes"
    And I press "Blocks editing off"
    And I log out

  Scenario: Customise dashboards as tenant member without tenant isolation
    Given I log in as "user1"
    And I should see "First T Dashboard Default Content"

    When I am on "Dashboard" page
    And I press "Customise this page"
    And I should see "First T Dashboard Default Content"
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Content                      | First T Dashboard My Content |
    And I press "Save changes"
    And I press "Stop customising this page"
    Then I should see "First T Dashboard My Content"

    When I press "Customise this page"
    And I press "Reset dashboard to default"
    Then I should see "First T Dashboard Default Content"

    When I click on "My Learning" "link"
    And I press "Customise this page"
    And I should see "My Learning Default Content"
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Content                      | My Learning My Content        |
    And I press "Save changes"
    And I press "Stop customising this page"
    Then I should see "My Learning My Content"

    When I press "Customise this page"
    And I press "Reset dashboard to default"
    Then I should see "My Learning Default Content"

  Scenario: Customise dashboards as tenant member with full tenant isolation
    Given tenant support is enabled with full tenant isolation
    And I log in as "user1"
    And I should see "First T Dashboard Default Content"

    When I am on "Dashboard" page
    And I press "Customise this page"
    And I should see "First T Dashboard Default Content"
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Content                      | First T Dashboard My Content |
    And I press "Save changes"
    And I press "Stop customising this page"
    Then I should see "First T Dashboard My Content"

    When I press "Customise this page"
    And I press "Reset dashboard to default"
    Then I should see "First T Dashboard Default Content"

  Scenario: Customise dashboards as tenant participant non-member without tenant isolation
    Given I log in as "participant1"
    And I should see "My Learning Default Content"
    And I should see "Second T Dashboard"
    And I should see "First T Dashboard"

    When I am on "Dashboard" page
    And I press "Customise this page"
    And I should see "My Learning Default Content"
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Content                      | My Learning My Content        |
    And I press "Save changes"
    And I press "Stop customising this page"
    Then I should see "My Learning My Content"

    When I press "Customise this page"
    And I press "Reset dashboard to default"
    Then I should see "My Learning Default Content"

    When I click on "First T Dashboard" "link"
    And I press "Customise this page"
    And I should see "First T Dashboard Default Content"
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Content                      | First T Dashboard My Content |
    And I press "Save changes"
    And I press "Stop customising this page"
    Then I should see "First T Dashboard My Content"

    When I press "Customise this page"
    And I press "Reset dashboard to default"
    Then I should see "First T Dashboard Default Content"

  Scenario: Customise dashboards as tenant participant non-member with full tenant isolation
    Given tenant support is enabled with full tenant isolation
    And I log in as "participant1"
    And I should see "My Learning Default Content"
    And I should see "Second T Dashboard"
    And I should see "First T Dashboard"

    When I am on "Dashboard" page
    And I press "Customise this page"
    And I should see "My Learning Default Content"
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Content                      | My Learning My Content        |
    And I press "Save changes"
    And I press "Stop customising this page"
    Then I should see "My Learning My Content"

    When I press "Customise this page"
    And I press "Reset dashboard to default"
    Then I should see "My Learning Default Content"

    When I click on "First T Dashboard" "link"
    And I press "Customise this page"
    And I should see "First T Dashboard Default Content"
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Content                      | First T Dashboard My Content |
    And I press "Save changes"
    And I press "Stop customising this page"
    Then I should see "First T Dashboard My Content"

    When I press "Customise this page"
    And I press "Reset dashboard to default"
    Then I should see "First T Dashboard Default Content"
