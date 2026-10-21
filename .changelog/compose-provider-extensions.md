---
category: fixed
type: patch
---

Fixes cross-provider extension composition by default in `container()`, `ContainerFactory::create()` and `ContainerBuilder::build()`. The first callable factory keeps precedence and all provider extensions run in registration order after selection, including earlier extension-only providers. Selected provider failures propagate instead of being masked by fallback. Explicit `AggregateServiceProvider` retains its last-factory precedence.
