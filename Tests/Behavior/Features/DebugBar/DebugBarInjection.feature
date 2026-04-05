Feature: Debug bar injection into HTML responses
  The debug bar middleware should inject CSS, JS and initialization code
  into HTML responses before the closing </body> tag.

  Scenario: Debug bar assets are injected into the test page
    When I request "/debugbar-test"
    Then the response status code should be 200
    And the response should contain "<h1>Debug Bar Test Page</h1>"
    And the response should contain "phpdebugbar"
    And the response should contain "</body>"

  Scenario: Debug bar injects JavaScript initialization
    When I request "/debugbar-test"
    Then the response status code should be 200
    And the response should contain "PhpDebugBar.DebugBar"

  Scenario: Debug bar injects CSS styles
    When I request "/debugbar-test"
    Then the response status code should be 200
    And the response should contain "phpdebugbar-header"

  Scenario: Debug bar sets open handler URL
    When I request "/debugbar-test"
    Then the response status code should be 200
    And the response should contain "openHandler"

  Scenario: Content-Length header is removed after injection
    When I request "/debugbar-test"
    Then the response status code should be 200
    And the response header "Content-Length" should not exist
