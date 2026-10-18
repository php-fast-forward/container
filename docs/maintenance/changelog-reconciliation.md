# Changelog adoption and Release reconciliation

Evidence captured on 2026-10-18 from `origin/main`
`fc881afa618b717199f72844f2ce380ad9b5262b`.

## Selected runtime and integration

- Latest stable GitHub Release: [v0.2.0](https://github.com/php-fast-forward/changelog/releases/tag/v0.2.0), published 2026-10-18T07:15:48Z.
- The lightweight tag resolves to commit `2d3f84fa9e5350f03636153fdb3ae2a822e5ae60`.
  The already available Composer/CPX tool installation identifies the same version,
  source and distribution reference. PHP 8.5.11 and Composer 2.10.3 were available.
- The owner explicitly selected `@v0` for the caller. This is a published-release
  **branch**, currently pointing to that same commit, rather than an immutable tag.
  Future channel movement must be resolved again before local maintenance.
- Read the [release's skill](https://github.com/php-fast-forward/changelog/blob/v0.2.0/resources/skills/fast-forward-changelog/SKILL.md),
  adoption/history/policy guides, caller example and reusable implementation at
  that commit. Documentation examples containing older `^0.1` constraints were
  not used to select the runtime.
- Preserve the repository's `main`, root paths, English presentation and `v`
  prefix. Existing Wiki, test, report and Dependabot status workflows are untouched.
  Existing jobs use Ubuntu or shared DevTools; no private runner, changelog App,
  token contract or previous version-PR naming policy was found.
- The caller uses Linux/Docker runners, explicit automatic PR resolution, one
  managed `changelog/version` branch and a package/version-specific PR title.
  `publish: false` preserves the explicit publication boundary.
- No application dependency or host skill installation was added. The original
  checkout and its untracked `docs/_static/` were preserved; work used a separate
  clone after confirming no open PR or suitable remote migration branch.

## Separate inventories and findings

The manual changelog already contained all 16 published versions. There were no
Changesets manifests, previous fragment directory, release automation or pending
notes. The sole legacy `Unreleased` section was empty. All GitHub release tags
resolve to the local tag commit and are ancestors of the selected base. No
duplicate identity, missing version, untagged Release, tag without a Release,
draft, prerelease or asset was found. Older Releases' `target_commitish: main`
is retained metadata; the verified tag SHA, not today's main, identifies them.

Dates already follow publication in `America/Sao_Paulo`. Apparent UTC differences
in 1.10.0, 1.8.0, 1.7.0 and 1.2.0 are timezone conversions, not conflicting
release evidence. Every existing section date and every correct version-link
definition is preserved byte for byte.

For each row below, the change interval is the previous semantic tag (exclusive)
through the tag (inclusive); 1.0.0 starts at repository inception. The exact
interval and integrated PR set are recorded in
[the reconciliation snapshot](release-reconciliation.json). Both local Git
ancestry and GitHub merge-commit identities were checked. Source diffs confirm
the recorded behavior; release-import PRs #35 and #37 are not delivery references.

- 1.0.0–1.5.1: inspect initial composition, provider/factory introduction, callable
  argument resolution, alias extensions, caching and method-target lookup in
  `src/`, their tests and tagged diffs. No delivery PR was proven for those
  notes, so no PR or commit fallback is appended.
- 1.6.0: #5 proves the PHP/toolchain migration. Provider-instance registration
  and subsequent documentation commits in that interval have no proven delivery
  PR; those notes remain unreferenced.
- 1.7.0: #12 delivers static composition, examples and provider extension/cache
  behavior. #8 and #9 deliver test status/Pages changes; #7 delivers the
  illustration. Recover the omitted environment-dump protection from #10 under
  Security. Keep other historical presentation/archive maintenance unreferenced.
- 1.7.1: #15 restores Wiki publication and removes the obsolete submodule.
- 1.8.0: #16 delivers the builder. Retain the released details about accepted
  initializers, fresh builds, source-object reuse and configuration handling.
- 1.9.0: #18 delivers native autowiring, optional dependency/error policy and
  the initial historical changelog. Keep that historical contract, including
  the then-available public APIs. Make its guide URL absolute at v1.9.0 so the
  isolated Release notes render correctly.
- 1.9.1: map each note to #29 (dependencies), #30 (examples), #31 (numeric IDs),
  #32 (per-object extensions), #33 (method targets) or #34 (provider cycles).
- 1.10.0: #28 delivers the consumer skill and Composer discovery metadata.
  Recover omitted repository contributor instructions from #36.

Existing release prose about validation totals, credits, issue proposals and
second full-changelog lists is omitted from the proposed Release bodies.
Behavioral notes remain in the corresponding version. No missing historical
publication date or conflicting version identity remains unresolved. Historical
PR attribution for direct commits is intentionally unknown.

## Reconciliation table

GitHub timestamps below are the proven publication instant; the calendar column
is the existing local-date convention. All rows require body-only updates after
approval and integration. No Release creation is currently needed.

| Tag / version | Verified tag commit | GitHub published_at (UTC) | Calendar date | CHANGELOG.md | GitHub Release |
| --- | --- | --- | --- | --- | --- |
| v1.10.0 | `fc881afa618b717199f72844f2ce380ad9b5262b` | 2026-10-17T01:09:27Z | 2026-10-16 | present | #415505450: body differs |
| v1.9.1 | `9559b1e0fd2547bf8e04ea875c923d6710449eff` | 2026-10-16T21:19:54Z | 2026-10-16 | present | #415388932: body differs |
| v1.9.0 | `a9d76438e205289b6831124e6e5a02d69a01611b` | 2026-10-16T17:29:35Z | 2026-10-16 | present | #415210061: body differs |
| v1.8.0 | `68ed6305e1aac233e1781b4887abf6b44c55e127` | 2026-10-13T02:30:15Z | 2026-10-12 | present | #411189934: body differs |
| v1.7.1 | `378de0dfc4b1d904f0772ae5ba7d7406d48e2b37` | 2026-10-12T22:57:31Z | 2026-10-12 | present | #411059417: body differs |
| v1.7.0 | `7f3f13d4dd13c4d4b0fd9bff5345a4238d49ba46` | 2026-10-12T02:29:40Z | 2026-10-11 | present | #410081475: body differs |
| v1.6.0 | `d9dd6d964189db80db0490cbe19dcd29841deba4` | 2026-03-27T15:35:54Z | 2026-03-27 | present | #302428761: body differs |
| v1.5.1 | `26c77ce9a086b656a8842b400fbcd35c7e0ce14b` | 2025-08-01T22:05:39Z | 2025-08-01 | present | #237012427: body differs |
| v1.5.0 | `dd6b43f681b4a116ee73d9cda718573fe38e82ab` | 2025-07-01T11:12:05Z | 2025-07-01 | present | #229072491: body differs |
| v1.4.1 | `3eaf84b3ce3b28b8ea4bdb86bc85ec254efebe45` | 2025-06-11T18:46:31Z | 2025-06-11 | present | #224694138: body differs |
| v1.4.0 | `00974c542a4800758ede6730f0dd939bf99bef4e` | 2025-06-11T18:38:44Z | 2025-06-11 | present | #224692607: body differs |
| v1.3.0 | `1cc4020ccedfdb836039f2c0ec25a7c3c0e4c3ab` | 2025-05-24T17:49:51Z | 2025-05-24 | present | #220803462: body differs |
| v1.2.1 | `84ea16fe6b342cebb62a30ebf626d6540cc43adf` | 2025-05-06T17:25:54Z | 2025-05-06 | present | #216824392: body differs |
| v1.2.0 | `1b6ea504c84bd462b24c95186b830ee627a4750d` | 2025-05-06T00:06:01Z | 2025-05-05 | present | #216625892: body differs |
| v1.1.0 | `01168864c1b873c5f46f2652908b4a2e127c4026` | 2025-04-20T03:35:45Z | 2025-04-20 | present | #213528351: body differs |
| v1.0.0 | `2c1492ceaf8ecd71c4e6852e9d8ad99f5dd38ca5` | 2025-04-20T03:00:57Z | 2025-04-20 | present | #213525591: body differs |

## Concrete Release changes and authorized execution boundary

[release-notes.diff](release-notes.diff) contains the exact old-to-proposed body
differences for all 16 Releases, extracted using the selected CLI's
`notes <version>`. [release-reconciliation.json](release-reconciliation.json)
records Release IDs, tag SHAs, original identities/flags/assets, publication
timestamps and before/after body SHA-256 values. These are review snapshots;
they do not authorize writes and must be refreshed if inputs drift.

The official `publish` operation validates managed version transactions and
refuses divergent existing releases. It is not a historical body repair tool.
After explicit authorization **and** integration of the canonical history:

1. Re-read the integrated SHA, reviews, checks and merge gates. Check out that
   exact SHA in a disposable clone with the canonical GitHub origin. Revalidate
   the selected release runtime and its notes extraction contract.
2. Re-read every tag and Release with `GET repos/php-fast-forward/container/git/ref/tags/<tag>`
   and `GET repos/php-fast-forward/container/releases/<id>`. Peel annotated tags
   if necessary. Compare tag commits, body hashes, target, name, draft/prerelease,
   assets and publication timestamps against the approved snapshot. Stop on drift.
3. Extract each approved version with the official CLI, for example:
   `changelog notes 1.10.0 --output=release-notes/1.10.0.md --no-interaction`.
   Export to a new file. Check its SHA-256 against the approved desired body and
   inspect the isolated Markdown. All current body links are absolute and inline;
   no document footer or extra version context is needed.
4. Execute only the corresponding approved body update, for example
   `gh api --method PATCH repos/php-fast-forward/container/releases/415505450 -F body=@release-notes/1.10.0.md`.
   The request contains **only body**. Apply the same operation using each row's
   ID/version. Do not change name, tag, target, flags or assets.
5. Re-read each Release. Require exact body bytes equal to notes extracted from
   the integrated SHA and unchanged identity, target, flags, assets and
   `published_at`; GitHub's `updated_at` may change. Re-read tags to confirm SHAs.
   Record per-version results; stop and inspect any ambiguous write outcome.

No command in that write phase has been run. No tag was created, moved or
recreated, and no Release was published or updated. A newly discovered missing
Release would need a new reviewed creation plan for an existing verified tag;
its new publication timestamp must never be represented as historical evidence.

## Validation and limits

Executed successfully with the verified v0.2.0 runtime:

- `composer validate --strict --no-check-lock`.
- `actionlint .github/workflows/changelog.yml`.
- `check`, `format --check`, `backfill --check`, `status --json` and
  `version --dry-run`, using `source=tags` and automatic PR resolution for
  planning/consolidation. Format and backfill are no-ops.
- The complete next consolidation in a disposable clone with the canonical
  GitHub origin: 1.10.0 → 1.10.1, consuming only the intended fragment. Assertions
  confirmed one new comparison definition before the older links, no duplicates,
  no Unreleased, exact retained introduction/history/dates/footer bytes, and no
  invented PR for the still-unmerged contribution.
- A disposable read-only fixture plan retains a proven explicit #28 as a
  clickable inline PR link and leaves the unknown migration PR absent. Applying
  that extra uncommitted fixture is correctly refused; no fake evidence was made.
- All 16 official `notes` extractions match the proposed body hashes; standalone
  links resolve without importing the central footer or any other version.

The ordinary comparison check correctly rejects the central-history edit until
the exact PR receives the legitimate, API-verified human maintenance grant.
No check was disabled or bypassed. The installed `actionlint` rejects the existing
`queue: max` in the untouched Wiki workflow when linting all workflows; the new
caller passes separately. No runtime PHP or dependency change was made, so the
full existing application suite remains a remote PR validation rather than a
new local toolchain installation.

The consumer checks cover the actual manual-history migration, empty legacy
pending section, 16 matching tag identities/dates, correct existing links,
unknown direct-commit PRs, known delivery PRs, isolated notes, and divergent
Release bodies. CLI fragment validation, formatting and tag backfill checks,
read-only planning and next-version application are exercised against the
selected runtime; write/consumption tests use disposable copies only.

Relevant upstream guarantees were inspected at the pinned release:
[ReleasePlannerTest](https://github.com/php-fast-forward/changelog/blob/v0.2.0/tests/Release/ReleasePlannerTest.php)
covers absent history with/without tags, history without tags, pending migration,
incremental preservation and formatting without consumption;
[HistoryReferenceBuilderTest](https://github.com/php-fast-forward/changelog/blob/v0.2.0/tests/History/HistoryReferenceBuilderTest.php)
covers retained link bytes and insertion order;
[AutomaticPullRequestReferenceTest](https://github.com/php-fast-forward/changelog/blob/v0.2.0/tests/Integration/AutomaticPullRequestReferenceTest.php)
covers proven/unassociated automatic references, recovery and publication
revalidation. Those upstream tests were inspected, not rerun as a new consumer
suite. Changesets/mixed-history fixtures are not applicable to this repository.

There was no open managed version PR to regenerate. Its first live signed-Bot
creation, downstream token-event approval and exact-head status must still be
observed after approved deployment. This PR does not alter branch protection.
Historical body reconciliation remains a separately approved operation.
