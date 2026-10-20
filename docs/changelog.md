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
published release channel. The current integration was verified at `v0.3.0`, commit
`85474de5a16bb08f83c9f30e0ed72f331a210f07`. Version PR titles use
`chore(release): fast-forward/container v{current_version} → v{next_version}`. Before a local maintenance operation,
resolve the channel and corresponding full published tag again, then read that
release's documentation and skill. Do not silently use an older local install.
The standalone CLI needs PHP `^8.5`; the container library still needs PHP `^8.3`.
There is no new application Composer dependency.

The complete project skill is installed at
`.agents/skills/fast-forward-changelog`, including its native provenance/hash
receipt. Installation used the verified release's own command:

```sh
cpx --skip-local fast-forward/changelog skill:install --no-interaction
cpx --skip-local fast-forward/changelog skill:install --check --json --no-interaction
```

Use `--update` only for a deliberate update with a newly verified runtime.
The installer refuses local edits, foreign ownership and known downgrades;
inspect failures without replacing the copy manually. This project installation
creates no host aliases or global copy. Refresh the host session and check skill
discovery separately; successful installation does not prove session activation.

Use CPX with its separate tool installation; ordinary commands keep the stable
CLI interface without embedding a release number in each example:

```sh
cpx --skip-local fast-forward/changelog list --raw
cpx --skip-local fast-forward/changelog help check
cpx --skip-local fast-forward/changelog check --no-interaction
cpx --skip-local fast-forward/changelog check --since=origin/main --no-interaction
cpx --skip-local fast-forward/changelog status --json --source=tags --resolve-pull-requests --no-interaction
cpx --skip-local fast-forward/changelog version --dry-run --source=tags --resolve-pull-requests --no-interaction
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
reviews and gates before acting. For historical Release reconciliation, extract
each version's notes from the integrated SHA using the official `notes` command,
verify the existing tag/Release identity, update only the authorized body and
re-read it to confirm byte equality. Preserve targets, flags, assets and original
publication timestamps. Adoption evidence remains in
[PR #38](https://github.com/php-fast-forward/container/pull/38).
