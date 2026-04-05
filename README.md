# Neos Debug Bar

PHP Debug Bar integration for Neos CMS / Flow Framework.

Adds the [PHP Debug Bar](https://github.com/maximebf/php-debugbar) to HTML responses via a PSR-15 middleware, providing out-of-the-box collectors for memory usage, request data, timing, exceptions, and more.

## Installation

```bash
composer require sandstorm/neos-debug-bar
```

The debug bar is enabled by default. To disable it:

```yaml
Sandstorm:
  NeosDebugBar:
    enabled: false
```

## Roadmap

- [x] Minimal connection - PSR-15 middleware injecting StandardDebugBar into HTML responses
- [ ] Doctrine query collector - Log all SQL queries via Doctrine DBAL logging
- [ ] Flow routing collector - Show matched route, controller, action, arguments
- [ ] Fusion rendering collector - Profile Fusion path rendering times
- [ ] Content cache collector - Track cache hits/misses/flushes in Neos content cache
- [ ] Signal/slot collector - Log dispatched signals and connected slots
- [ ] Security context collector - Show current account, roles, CSRF token status
- [ ] Flow log collector - Bridge Flow SystemLogger messages into the Messages tab
- [ ] AJAX request support - Use DebugBar OpenHandler to capture AJAX/subrequest data
- [ ] Environment-aware activation - Auto-enable in Development context, disable in Production
- [ ] Asset publishing - Publish DebugBar assets via Flow resource management instead of inline rendering
