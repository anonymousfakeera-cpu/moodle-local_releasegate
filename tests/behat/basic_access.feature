@local_releasegate
Feature: Release gate pages enforce authentication and authorisation
  In order to protect scan results and audit data
  Unauthenticated users must be redirected to the login page
  And authenticated users with sufficient capability must reach the pages

  Background:
    Given the following "courses" exist:
      | fullname    | shortname |
      | Test Course | TC1       |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | TC1    | editingteacher |

  @javascript
  Scenario: Unauthenticated user is redirected from the site overview
    When I am on "/local/releasegate/index.php" page
    Then I should see "You need to log in"

  @javascript
  Scenario: Unauthenticated user is redirected from the access review
    When I am on "/local/releasegate/accessreview.php" page
    Then I should see "You need to log in"

  @javascript
  Scenario: Unauthenticated user is redirected from the audit log
    When I am on "/local/releasegate/audit.php" page
    Then I should see "You need to log in"

  @javascript
  Scenario: Site administrator can open the site overview
    Given I log in as "admin"
    When I am on "/local/releasegate/index.php" page
    Then I should see "Release gate"

  @javascript
  Scenario: Site administrator can open the access review
    Given I log in as "admin"
    When I am on "/local/releasegate/accessreview.php" page
    Then I should see "Release gate access review"

  @javascript
  Scenario: Site administrator can open the audit log
    Given I log in as "admin"
    When I am on "/local/releasegate/audit.php" page
    Then I should see "Release gate audit log"

  @javascript
  Scenario: Teacher enrolled in a course can open the course gate page
    Given I log in as "teacher1"
    And I am on "TC1" course homepage
    When I follow "Release gate for this course"
    Then I should see "Test Course"
