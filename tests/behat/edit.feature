@qtype @qtype_ddmatch
Feature: Test editing a Drag and drop matching question
  As a teacher
  In order to be able to update my Drag and drop matching questions
  I need to edit them

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | T1        | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype   | name                   | template |
      | Test questions   | ddmatch | Ddmatching for editing | foursubq |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" node in "Course administration"

  @javascript @_switch_window
  Scenario: Edit a Drag and drop matching question
    When I click on "Edit" "link" in the "Ddmatching for editing" "table_row"
    And I set the following fields to these values:
      | Question name | |
    And I press "id_submitbutton"
    Then I should see "You must supply a value here."
    When I set the following fields to these values:
      | Question name | Edited Ddmatching name |
    And I press "id_submitbutton"
    Then I should see "Edited Ddmatching name"
    When I click on "Edit" "link" in the "Edited Ddmatching name" "table_row"
    And I set the following fields to these values:
      | Shuffle    | 0   |
      | Question 2 | dog |
      | Question 4 | fly |
    And I press "id_submitbutton"
    Then I should see "Edited Ddmatching name"
    When I click on "Preview" "link" in the "Edited Ddmatching name" "table_row"
    And I switch to "questionpreview" window
    Then I should see "frog"
    And I should see "dog"
    And I should see "newt"
    And I should see "fly"
