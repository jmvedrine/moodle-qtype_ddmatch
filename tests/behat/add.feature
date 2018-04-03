@qtype @qtype_ddmatch
Feature: Test creating a drag and drop matching question
  As a teacher
  In order to test my students
  I need to be able to create a drag and drop matching question

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" node in "Course administration"

  Scenario: Create a drag and drop matching question
    When I add a "Drag-and-Drop Matching" question filling the form with:
      | Question name                      | ddmatch-001                                    |
      | Question text                      | Match the country with the capital city.       |
      | General feedback                   | England=London, France=Paris and Spain=Madrid. |
      | id_subquestions_0                  | England                                        |
      | id_subanswers_0                    | London                                         |
      | id_subquestions_1                  | France                                         |
      | id_subanswers_1                    | Paris                                          |
      | id_subquestions_2                  | Spain                                          |
      | id_subanswers_2                    | Madrid                                         |
      | For any correct response           | Your answer is correct                         |
      | For any partially correct response | Your answer is partially correct               |
      | For any incorrect response         | Your answer is incorrect                       |
      | Hint 1                             | This is your first hint                        |
      | Hint 2                             | This is your second hint                       |
    Then I should see "ddmatch-001"
