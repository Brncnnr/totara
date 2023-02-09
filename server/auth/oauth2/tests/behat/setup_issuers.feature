@core @totara @auth @oauth2 @auth_oauth2 @javascript
Feature: Setup the oauth2 plugin and issuers so that a user can login using a single sign-on service.

  Background:
    When I log in as "admin"
    And I navigate to "Plugins > Authentication > Manage authentication" in site administration
    And I click on "Enable" "link" in the "OAuth 2" "table_row"
    And I navigate to "Server > OAuth 2 > OAuth 2 consumer details" in site administration

  Scenario Outline: Setup services
    When I click on "Create new <type> service" "button"
    And I should see "Detailed instructions on setting up the"
    And I should not see "Show default Microsoft branding"
    When I click on "Help with Name" "link"
    Then I should see "Name of the identity issuer. May be displayed on login page."
    When I set the following fields to these values:
      | Name               | <type> service 1 |
      | Client ID          | clientid         |
      | Client secret      | clientsecret     |
      | Show on login page | 1                |
    And I click on "Save changes" "button"
    Then I should see "Changes saved"
    And I should see "<type> service 1"
    When I click on "Edit" "link" in the "<type> service 1" "table_row"
    Then I should see "Edit identity issuer: <type> service 1"
    And I should see "Detailed instructions on setting up the <type> service 1 OAuth 2 provider"
    And I should not see "Show default Microsoft branding"
    When I set the following fields to these values:
      | Name | <type> service 2  |
    And I click on "Save changes" "button"
    Then I should see "Changes saved"
    And I should not see "<type> service 1"
    And I should see "<type> service 2"
    When I log out
    And I use magic for persistent login to open the login page
    Then I should see "<type> service 2" in the ".potentialidplist" "css_element"

    Examples:
      | type      |
      | Google    |
      | Facebook  |
      | custom    |

  Scenario: Setup a Microsoft service, with the 'Show default Microsoft branding' setting enabled and disabled.
    When I click on "Create new Microsoft service" "button"
    And I click on "Help with Name" "link"
    Then I should see "Name of the identity issuer. This will be displayed on the log in page unless the default Microsoft branding is enabled."
    When I set the following fields to these values:
      | Name                            | Microsoft service 1 |
      | Client ID                       | clientid            |
      | Client secret                   | clientsecret        |
      | Show default Microsoft branding | 1                   |
    And I click on "Save changes" "button"
    And I log out
    And I use magic for persistent login to open the login page
    Then I should see image with alt text "Microsoft service 1"
    When I log in as "admin"
    And I navigate to "Server > OAuth 2 > OAuth 2 consumer details" in site administration
    And I click on "Edit" "link" in the "Microsoft service 1" "table_row"
    And I click on "Help with Name" "link"
    Then I should see "Name of the identity issuer. This will be displayed on the log in page unless the default Microsoft branding is enabled."
    When I set the following fields to these values:
      | Name                            | Microsoft service 2 |
      | Show default Microsoft branding | 0                   |
    And I click on "Save changes" "button"
    Then I should see "Changes saved"
    When I log out
    And I use magic for persistent login to open the login page
    Then I should see "Microsoft service 2" in the ".potentialidplist" "css_element"
