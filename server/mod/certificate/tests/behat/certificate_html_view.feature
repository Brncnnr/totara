@mod @mod_certificate @totara
Feature: View HTML version of certificate
  In order to view the HTML version of a certificate
  As a user
  I need to create a certificate activity and view the HTML version

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

  @javascript @_switch_window
  Scenario: Add and view HTML version of certificate
    When I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Certificate" to section "1" and I fill the form with:
      | Certificate Name | Test certificate   |
      | Watermark Image  | Fleursdelis        |
      | Signature Image  | RVincent.png       |
      | Delivery         | Open in new window |
    And I follow "Test certificate"
    Then "Get your certificate" "button" should exist
    And "View HTML version" "button" should exist

    When I click on "View HTML version" "button"
    And I switch to "certificatehtml" window
    Then I should see "CERTIFICATE of ACHIEVEMENT"
    And I should see "Course 1"
    And "div[style*='Fleursdelis.png']" "css_element" should exist
    And "div[style*='RVincent.png']" "css_element" should exist

  @javascript @_switch_window
  Scenario: Verify the HTML version button is not displayed for force downloads
    When I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Certificate" to section "1" and I fill the form with:
      | Certificate Name | Test certificate |
      | Watermark Image  | Fleursdelis      |
      | Signature Image  | RVincent.png     |
      | Delivery         | Force download   |
    And I follow "Test certificate"
    Then "Get your certificate" "button" should exist
    And "View HTML version" "button" should not exist
