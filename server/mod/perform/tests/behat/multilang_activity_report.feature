@totara @perform @mod_perform @javascript @vuejs
Feature: Test Performance activity support multi language

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
    | activity_name    | description      | activity_type |
    | <span lang="de" class="multilang">German Activity</span><span lang="en" class="multilang">English Activity</span> | My Test Activity | feedback      |

  Scenario: I can see performance activity name in English and German
    Given remote langimport tests are enabled
    Given I log in as "admin"
    Given I navigate to "Language packs" node in "Site administration > Localisation"
    Given language pack installation succeeds
    And I set the field "Available language packs" to "de"
    And I press "Install selected language pack(s)"

    And I navigate to "Manage filters" node in "Site administration > Plugins > Filters"
    And I set the field with xpath "//table[@id='filterssetting']//form[@id='activemultilang']//select[@name='newstate']" to "1"
    And I set the field with xpath "//table[@id='filterssetting']//form[@id='applytomultilang']//select[@name='stringstoo']" to "1"

    And I navigate to the manage perform activities page
    And I click on "Participation reporting" "link"
    And I should see "English Activity"
    And I should not see "German Activity"
    And I follow "Deutsch" in the user menu
    And I should not see "English Activity"
    And I should see "German Activity"
