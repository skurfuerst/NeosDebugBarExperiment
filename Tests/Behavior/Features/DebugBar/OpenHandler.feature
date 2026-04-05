Feature: Debug bar open handler endpoint
  The open handler endpoint serves stored debug data as JSON
  for the client-side debug bar JavaScript.

  Scenario: Open handler returns JSON response
    When I request "/debugbar/open-handler?op=find"
    Then the response status code should be 200
    And the response Content-Type should contain "application/json"
    And the response body should be valid JSON

  Scenario: Open handler is not wrapped in debug bar HTML
    When I request "/debugbar/open-handler?op=find"
    Then the response should not contain "PhpDebugBar.DebugBar"
    And the response should not contain "</body>"
