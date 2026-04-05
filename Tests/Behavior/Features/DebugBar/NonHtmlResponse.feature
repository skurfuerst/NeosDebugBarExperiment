Feature: Debug bar handling of non-HTML responses
  The open handler endpoint itself is explicitly skipped by the middleware
  to avoid recursion. Therefore the phpdebugbar-id header is NOT added
  to open handler responses.

  Scenario: Open handler endpoint is skipped by debug bar middleware
    When I request "/debugbar/open-handler?op=find"
    Then the response status code should be 200
    And the response header "phpdebugbar-id" should not exist
    And the response should not contain "PhpDebugBar.DebugBar"
