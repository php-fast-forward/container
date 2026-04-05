Providers API
=============

``ArrayServiceProvider``
------------------------

``ArrayServiceProvider`` is a lightweight implementation of
``Interop\Container\ServiceProviderInterface`` backed by two arrays:

- factories
- extensions

Use it when you want the shortest possible path to a working provider.

``AggregateServiceProvider``
----------------------------

``AggregateServiceProvider`` combines several providers into a single provider object.

Factory behavior:

- each child provider keeps its own factory entries
- later providers overwrite earlier providers when they use the same service ID
- the aggregate also registers itself and each child provider under their class names through ``ServiceFactory``

Extension behavior:

- extensions are merged across all child providers
- when the same ID appears more than once, the extensions are composed in provider order
- non-callable extensions cause ``RuntimeException``

This makes ``AggregateServiceProvider`` useful when you want to publish one composed provider
as a package-level integration point.

Important distinction
---------------------

``AggregateServiceProvider`` is not the same as passing providers separately to
``container($providerA, $providerB)``.

Use ``AggregateServiceProvider`` when:

- you want one merged provider object
- later factory definitions should replace earlier ones
- repeated extensions should be composed

Pass providers separately to ``container()`` when:

- you want first-match-wins resolution between providers
- you want easy override layering
- you want to keep provider boundaries visible in the final aggregate container
