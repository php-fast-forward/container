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
published release channel. Adoption used `v0.2.0`, commit
`2d3f84fa9e5350f03636153fdb3ae2a822e5ae60`. The integration and installed skill
were subsequently reviewed at `v0.3.0`, commit
`85474de5a16bb08f83c9f30e0ed72f331a210f07`. Before a local maintenance operation,
resolve the channel and corresponding full published tag again, then read that
release's documentation and skill. Do not silently use an older local install.
The standalone CLI needs PHP `^8.5`; the container library still needs PHP `^8.3`.
There is no new application Composer dependency.

The complete project skill is installed at
`.agents/skills/fast-forward-changelog`, including its native provenance/hash
receipt. Use the verified release's native commands to update an intact existing
copy and check its resulting integrity:

```sh
cpx --skip-local fast-forward/changelog:0.3.0 skill:install --update --no-interaction
cpx --skip-local fast-forward/changelog:0.3.0 skill:install --check --json --no-interaction
```

Omit `--update` for a fresh installation. Use it only for a deliberate update
with a newly verified runtime. The check reports `installed_source` separately
from the executing runtime's bundled `source`; a newer package version alone
does not require replacement when its instruction bytes are identical.
The installer refuses local edits, foreign ownership and known downgrades;
inspect failures without replacing the copy manually. This project installation
creates no host aliases or global copy. Refresh the host session and check skill
discovery separately; successful installation does not prove session activation.

For the reviewed runtime, use CPX with its separate tool installation:

```sh
cpx --skip-local fast-forward/changelog:0.3.0 list --raw
cpx --skip-local fast-forward/changelog:0.3.0 help check
cpx --skip-local fast-forward/changelog:0.3.0 check --no-interaction
cpx --skip-local fast-forward/changelog:0.3.0 check --since=origin/main --no-interaction
cpx --skip-local fast-forward/changelog:0.3.0 status --json --source=tags --resolve-pull-requests --no-interaction
cpx --skip-local fast-forward/changelog:0.3.0 version --dry-run --source=tags --resolve-pull-requests --no-interaction
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

The workflow has `publish: true`: merging the reviewed managed version PR
authorizes publication of its exact tag and GitHub Release. It never merges the
PR itself. Review its exact SHA, planned version, notes, fragment deletions and
checks before merging. Enabling publication does not publish older consolidated
versions retroactively. Version planning advances from the greatest stable
version in committed history or ancestral tags, including unpublished sections.

Historical recovery and edits to existing Release bodies need separate explicit
authorization. For a missing publication, read the selected release's publication
guide and inspect `help github`. Preview `github publish` with the verified merged
PR number, original signed head, merge target, base branch, managed branch, Bot
identity and the same source/PR-resolution options used during consolidation.
Use `--dry-run=true` before repeating that exact operation with `--dry-run=false`.
The contextual route verifies the original signed head and exact merge target;
a squash merge may carry stale trailers that the generic `publish` command
correctly refuses. Do not amend committed evidence or move conflicting tags.
Confirm the resulting tag targets the approved commit and the Release body
matches its exact committed notes; retrying a complete publication must be
unchanged.

For separately authorized edits to existing Release bodies, extract notes with
the official `notes` command, verify the existing tag/Release identity, update
only the approved body and re-read it to confirm byte equality. Preserve targets,
flags, assets and original publication timestamps. Adoption evidence remains in
[PR #38](https://github.com/php-fast-forward/container/pull/38).
