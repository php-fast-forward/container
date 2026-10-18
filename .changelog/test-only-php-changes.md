---
category: changed
type: patch
---

Runs PHPUnit and production consumer suites only when PHP files change, while keeping required test statuses explicit for documentation-only pushes and Dependabot. Manual runs still exercise the complete suite.
