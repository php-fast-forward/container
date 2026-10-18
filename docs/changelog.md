# Maintaining the changelog

Add one independent `.changelog/*.md` fragment per observable contribution:

```markdown
---
category: fixed
type: patch
---

Preserves configured dependencies when resolving a method factory.
```

Categories follow Keep a Changelog: `added`, `changed`, `deprecated`, `removed`,
`fixed`, `security`. Semantic impact is a separate `type`: `major`, `minor` or
`patch`. Omit unknown PR, issue and author metadata. Version preparation resolves
the delivery PR from committed fragment bytes and verified GitHub evidence;
opening a PR does not require another fragment commit. No commit/author fallback
is generated. Keep notes focused on behavior and preserve inherited fragments.

The [caller](../.github/workflows/changelog.yml) uses the owner-selected `v0`
published release channel. At adoption it resolves to `v0.2.0`, commit
`2d3f84fa9e5350f03636153fdb3ae2a822e5ae60`. Before a local maintenance operation,
resolve the channel and corresponding full published tag again, then read that
release's documentation and skill. Do not silently use an older local install.
The standalone CLI needs PHP `^8.5`; the container library still needs PHP `^8.3`.
There is no new application Composer dependency.

For the verified adoption runtime, use CPX with its separate tool installation:

```sh
cpx --skip-local fast-forward/changelog:0.2.0 list --raw
cpx --skip-local fast-forward/changelog:0.2.0 help check
cpx --skip-local fast-forward/changelog:0.2.0 check --no-interaction
cpx --skip-local fast-forward/changelog:0.2.0 check --since=origin/main --no-interaction
cpx --skip-local fast-forward/changelog:0.2.0 status --json --source=tags --resolve-pull-requests --no-interaction
cpx --skip-local fast-forward/changelog:0.2.0 version --dry-run --source=tags --resolve-pull-requests --no-interaction
```

The comparison requires committed changes. Run any operation that writes central
history or consumes fragments only in a disposable copy when testing. Keep the
canonical GitHub origin, complete tags and the selected runtime. Use
`source: tags`, English headings and automatic PR resolution consistently across
planning, version preparation, checks and publication. Unknown PRs remain absent;
unavailable or ambiguous API evidence blocks preparation.

Ordinary PRs add fragments. The `changelog/version` branch belongs to the verified
`github-actions[bot]` transaction; let the workflow regenerate it after the base
changes. Do not edit its branch, ownership trailers, labels or evidence manually.
Its default token may require approval for downstream workflows; no GitHub App or
custom token was configured in this repository at adoption.

Historical dates use the existing `America/Sao_Paulo` convention, derived from
GitHub `published_at`. Keep historical descriptions tied to their actual versions.
PR links belong inline on their delivered notes; the footer contains version
definitions only, in section order. `CHANGELOG.md` is the canonical published
history; pending work belongs in fragments, without an `Unreleased` section.

Central-history maintenance uses `changelog-maintenance` only when the latest
live timeline grant comes from a verified human with current maintain/admin
permission. Use `check --since=origin/main --pull-request=<number>` at the exact
committed PR head to verify this authorization. No public skip flag substitutes
for it. Read the selected release's policy documentation before using exceptions.

The workflow does not merge version PRs and has `publish: false`. Publishing tags,
creating Releases, changing existing Release bodies and enabling future automatic
publication each require explicit authorization. Recheck the exact approved SHA,
reviews and gates before acting. The initial historical body differences and
the gated reconciliation procedure are recorded in
[the adoption audit](maintenance/changelog-reconciliation.md).
