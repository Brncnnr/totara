@totara @totara_contentmarketplace @contentmarketplace_linkedin @vuejs @javascript
Feature: Content marketplace linkedin with multi languages filter
  Background:
    Given I set up the "linkedin" content marketplace plugin

  Scenario: View catalog import with different languages available.
    Given the following "learning objects" exist in "contentmarketplace_linkedin" plugin:
      | urn | title    | level    | asset_type | locale_language | locale_country |
      | A   | Course A | BEGINNER | COURSE     | en              | US             |
      | B   | Course B | BEGINNER | COURSE     | de              | DE             |
      | C   | Course C | BEGINNER | COURSE     | ja              | JP             |
    And the following "classifications" exist in "contentmarketplace_linkedin" plugin:
      | urn        | name  | type    | locale_language | locale_country |
      | category:1 | J2EE  | LIBRARY | en              | US             |
      | category:2 | JDBC  | SUBJECT | en              | US             |
      | category:3 | Java  | LIBRARY | de              | DE             |
      | category:4 | JSLib | SUBJECT | de              | DE             |
      | category:5 | JSP   | LIBRARY | ja              | JP             |
      | category:6 | Bean  | SUBJECT | ja              | JP             |
    And the following "classification relationships" exist in "contentmarketplace_linkedin" plugin:
      | parent_urn | child_urn  |
      | category:1 | category:2 |
      | category:3 | category:4 |
      | category:5 | category:6 |
    And the following "learning object classifications" exist in "contentmarketplace_linkedin" plugin:
      | learning_object_urn | classification_urn |
      | A                   | category:2         |
      | B                   | category:4         |
      | C                   | category:6         |
    And I am on a totara site
    And I log in as "admin"
    And I navigate to the catalog import page for the "linkedin" content marketplace

    # English language
    And I should see "English"
    And I should see "Course A"
    And I should not see "Course B"
    And I should not see "Course C"
    And I should see "J2EE"
    And I should not see "Java"
    And I should not see "JSP"
    When I click on "J2EE" "button"
    Then I should see "JDBC"
    And I should not see "JSLib"
    And I should not see "Bean"
    When I reload the page
    Then I should see "Course A"
    And I should not see "Course B"
    And I should not see "Course C"

    # German language
    When I set the field "Language" to "German"
    Then I should not see "Course A"
    And I should see "Course B"
    And I should not see "Course C"
    And I should not see "J2EE"
    And I should see "Java"
    And I should not see "JSP"
    When I click on "Java" "button"
    Then I should not see "JDBC"
    And I should see "JSLib"
    And I should not see "Bean"
    When I reload the page
    Then I should not see "Course A"
    And I should see "Course B"
    And I should not see "Course C"

    # Japanese language
    When I set the field "Language" to "Japanese"
    Then I should not see "Course A"
    And I should not see "Course B"
    And I should see "Course C"
    And I should not see "J2EE"
    And I should not see "Java"
    And I should see "JSP"
    When I click on "JSP" "button"
    Then I should not see "JDBC"
    And I should not see "JSLib"
    And I should see "Bean"
    When I reload the page
    Then I should not see "Course A"
    And I should not see "Course B"
    And I should see "Course C"

  Scenario: View catalog import with one language available.
    Given the following "learning objects" exist in "contentmarketplace_linkedin" plugin:
      | urn | title    | level    | asset_type | locale_language | locale_country |
      | A   | Course A | BEGINNER | COURSE     | en              | US             |
    And the following "classifications" exist in "contentmarketplace_linkedin" plugin:
      | urn        | name  | type    | locale_language | locale_country |
      | category:1 | J2EE  | LIBRARY | en              | US             |
      | category:2 | JDBC  | SUBJECT | en              | US             |
    And the following "classification relationships" exist in "contentmarketplace_linkedin" plugin:
      | parent_urn | child_urn  |
      | category:1 | category:2 |
    And the following "learning object classifications" exist in "contentmarketplace_linkedin" plugin:
      | learning_object_urn | classification_urn |
      | A                   | category:2         |

    And I am on a totara site
    And I log in as "admin"
    When I navigate to the catalog import page for the "linkedin" content marketplace
    Then I should not see "Language"
    And I should not see "English"
    And I should see "Course A"