# BEAR.Sunday official manual review - 2026-06-04

## Scope

Reviewed `https://bearsunday.github.io/llms-full.txt` as fetched on
2026-06-04 JST. This is a documentation review from the perspective of a
reader copying examples into a BEAR.Sunday application, and from the perspective
of this repository as a reference implementation.

## Findings

### P1. Production `ProdModule` example does not compile as written

Manual snapshot: lines 1526-1545.

The sample imports `BEAR\Package\AbstractAppModule` but declares
`class ProdModule extends AbstractModule`. `AbstractModule` is not imported in
that snippet, and the imported `AbstractAppModule` is unused. The same snippet
also references `ErrorPageFactoryInterface` and `MyErrorPageFactory` without
showing imports or explaining that they are placeholders.

Why this matters: the Production chapter is exactly where readers are likely to
copy code. A non-compiling production module undermines the highest-risk setup
path.

Suggested fix:

- Make the class extend the imported base class, or import the intended
  `Ray\Di\AbstractModule`.
- Add `: void` return type on `configure()`.
- Mark error-page classes as placeholders, or include minimal imports.

### P1. Resource link examples contain invalid PHP attribute syntax

Manual snapshot: lines 5559-5631.

Issues:

- Heading typo: `Reousrce link`.
- `#[Link rel: 'profile', href: '/profile{?id}']` is not valid PHP attribute
  syntax.
- `#[Embed(rel: 'website', src: '/website{?id}']` is missing the closing
  parenthesis.
- Several code blocks are partial without explicitly saying they are excerpts.

Why this matters: Link and Embed are central BEAR.Resource features. Invalid
attribute examples will be copied into applications and fail immediately.

Suggested fix:

- Rename the chapter to `Resource Link`.
- Use `#[Link(rel: 'profile', href: '/profile{?id}')]`.
- Use `#[Embed(rel: 'website', src: '/website{?id}')]`.
- Add `// excerpt` comments or provide complete class/method snippets.

### P1. Supported PHP table is stale

Manual snapshot: lines 325-349.

The manual lists PHP 8.1 through 8.4 as supported and calls 8.4 current stable.
As of 2026-06-04, php.net lists PHP 8.2, 8.3, 8.4, and 8.5 as currently
supported; PHP 8.5 was released on 2025-11-20 and is supported through
2029-12-31.

Why this matters: runtime support is operational guidance, not just background
information. This repository already targets PHP 8.5, so the manual now lags
behind the reference project and the official PHP support table.

Suggested fix:

- Update the supported PHP list from php.net.
- Move 8.1 to EOL.
- Add 8.5 as current stable or latest stable.

### P2. Graphviz command uses the wrong output format

Manual snapshot: lines 1746-1753.

The `module.dot` section says `dot -T svn module.dot > module.svg`. Graphviz
uses `svg`, not `svn`, for SVG output.

Why this matters: this is a direct command example. A reader following it will
get an error instead of a module graph.

Suggested fix:

```bash
dot -T svg module.dot > module.svg
```

### P2. Production chapter has small but copy-breaking typos

Manual snapshot: lines 1680-1745.

Examples:

- `vendor/autload.php` should be `vendor/autoload.php`.
- The `.compile.php` sample uses `$_SERVER[__REQUIRED_KEY__]` without quoting
  the array key.
- The Redis dummy adapter sample contains full-width spaces in the method body.

Why this matters: these are small, but they appear in production compilation
guidance where copy-paste reliability matters.

Suggested fix:

- Correct the path typo.
- Use `$_SERVER['__REQUIRED_KEY__'] = 'fake';` or a realistic server key.
- Remove full-width spaces from code blocks.

### P2. Context names are inconsistent across chapters

Manual snapshot examples:

- `prod-hal-api-app`: lines 4, 672, 772.
- `hal-api-app`: lines 4452, 4571, 7566.
- `prod-hal-app`: lines 2798, 3528, 7852, 7995, 8139.
- `prod-cli-hal-api-app`: line 3663.
- `cli-hal-app`: line 6619.

Why this matters: context strings are a core BEAR.Sunday concept. The manual
teaches that each segment maps to modules, so inconsistent examples make it
hard to tell which context is canonical for API, HTML, CLI, HAL, and production
use.

Suggested fix:

- Add a canonical context matrix.
- Normalize server examples to the same API context vocabulary, or explain why
  `prod-hal-app` differs from `prod-hal-api-app`.
- Avoid rarely used permutations unless the chapter explains segment order.

### P2. Modern attribute sections still describe legacy annotations

Manual snapshot: lines 6312-6349 and 6767-6797.

The JSON Schema chapter still says `@JsonSchema`, and the Hypermedia API
chapter says `@Link` / `@Embed` immediately under `#[Link]` / `#[Embed]`
headings.

Why this matters: the manual supports historical annotation compatibility, but
new PHP 8 readers need one primary spelling. Mixing both in the same paragraph
causes uncertainty about what should be used in new code.

Suggested fix:

- Use PHP 8 attributes as the primary spelling in current examples.
- Add a short note that Doctrine-style annotations are legacy-compatible where
  applicable.

### P2. File upload section lacks installation and module prerequisites

Manual snapshot: lines 5265-5490.

The Resource Parameters chapter introduces `#[InputFile]`,
`Koriym\FileUpload\FileUpload`, and `ErrorFileUpload`, but the section does not
show the composer package or module setup needed before the examples.

Why this matters: file upload is a boundary feature with security and runtime
dependencies. Readers need the exact install/setup path before copying the
resource method.

Suggested fix:

- Add the required Composer package(s).
- Add any required module installation.
- Add a short security note about storage path, random names, MIME/type checks,
  and maximum size.

### P2. JSON Schema request-validation example appears incomplete

Manual snapshot: lines 6410-6441.

The `todo.post.json` example does not appear to close the top-level schema
object fully. It also uses draft-04 while later tooling and generated OpenAPI
material elsewhere speak in more modern terms.

Why this matters: invalid JSON in a validation chapter is high-friction for new
users and for AI agents that copy examples.

Suggested fix:

- Provide a complete, valid JSON document.
- Consider using the schema draft currently expected by BEAR.Resource examples,
  or state why draft-04 is used.

### P3. The API documentation service example has visible typos

Manual snapshot: lines 6767-6830.

Examples:

- `latest post entrty` should be `latest post entry`.
- `MyVendor\MyPorject` should be `MyVendor\MyProject`.

Why this matters: these are not conceptual bugs, but they reduce trust in a
section about generated API documentation.

Suggested fix:

- Correct spelling and run a spell-check pass over generated manual text.

### P3. Some fenced code info strings look like source-site artifacts

Examples: many code blocks use `php?start_inline`.

Why this matters: it is probably harmless in the rendered manual, but in
`llms-full.txt` it is noisy and may reduce downstream code-block quality for
LLM readers.

Suggested fix:

- Normalize code fences in `llms-full.txt` output to standard language names
  such as `php`, `bash`, `json`, or `text`.

## Structural recommendations

1. Add "copyable examples" checks for manual snippets.
   A lightweight extractor could run PHP syntax checks on fenced PHP examples
   that are intended to be complete, and JSON validation on fenced JSON.

2. Separate legacy annotation compatibility from current PHP 8 style.
   Keep annotations documented, but make attributes the default vocabulary in
   new examples.

3. Add a context-string reference table early in the manual.
   This would prevent confusion across tutorial, server, content negotiation,
   CLI, and production chapters.

4. Keep `llms-full.txt` as a first-class output.
   It should remove source-site fence suffixes and stale proofread notices where
   possible because AI agents consume this exact file.

5. Cross-link manual gaps to reference examples.
   MyVendor.Cms can become the executable companion for PSR-7, `#[InputFile]`,
   cache invalidation, security, and production-slice examples once those slices
   are added.

## Sources

- BEAR.Sunday official manual: https://bearsunday.github.io/llms-full.txt
- PHP supported versions: https://www.php.net/supported-versions.php
- PHP 8.5 release announcement: https://www.php.net/releases/8.5/
