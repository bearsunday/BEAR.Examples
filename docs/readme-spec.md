# README Spec v1

README spec v1 gives BEAR.Sunday-aligned projects a shared first-page
shape. It is meant for Skeleton, Hello, BEAR.Cms, and similar reference
projects so readers can move between repositories without relearning the
entry points each time.

The repository README is the front door, not the manual, API reference,
or decision log. It should answer four questions in this order:

1. What is this project?
2. How do I run it?
3. Where do I read next?
4. Which related projects or upstream references matter?

## Required Shape

A conforming README starts with the project name as `# <Project>` and
may include a language switch before the title. After that, use these
top-level sections in order. Localized READMEs may translate the section
labels, but must keep the same order and meaning.

| Section | Required content |
|---|---|
| `Description` | One-sentence project identity, project scope, key capabilities, and the context in which the project was built. |
| `Setup` | The shortest working path first, then database or platform-specific variants, runtime contexts, useful commands, and test commands. |
| `Reference` | Reading order, generated API or semantic artefacts, question-oriented index, and journal or background material when relevant. |
| `Links` | Upstream BEAR.Sunday references, related packages, generated profiles, related repositories, and upstream issues or PRs that explain active gaps. |

Do not add unrelated top-level sections. Project-specific material should
be nested under one of the four required sections.

## Section Rules

### Description

- Identify the project directly: "`<Project>` is ...".
- State what is intentionally in scope and out of scope.
- For reference implementations, explain the construction context: for
  example ALPS-first, BDR-oriented, app-resource only, or tutorial code.
- Keep capability lists compact. Use a table only when it improves scan
  speed.
- Link to deeper architecture material instead of carrying design
  rationale in the README.

### Setup

- Put the lowest-friction path first. If a fake or in-memory path exists,
  it comes before database-backed paths.
- Keep each setup path executable as pasted.
- Show the canonical runtime contexts when the project has more than one.
- Include the small command set readers need repeatedly: test, full
  checks, generated artefacts, local server, and CLI/demo commands.
- If integration tests require external services, say how they are
  enabled and what happens when the service is missing.

### Reference

- Give a reading order for both humans and coding agents.
- Include an "index by question" table for convention-heavy projects.
- Point to generated API docs and semantic artefacts from here, not from
  a separate top-level section.
- Keep journals and build logs clearly marked as background, not API
  surface.

### Links

- Prefer durable upstream project URLs over transient blog or release
  links.
- Link generated local artefacts when they are part of the reference
  surface, such as an ALPS profile or OpenAPI document.
- Link upstream issues or pull requests only when they explain a current
  constraint or compatibility decision.

## Conformance Checklist

- The top-level section order is `Description`, `Setup`, `Reference`,
  `Links`.
- Every setup block can be pasted into a fresh checkout.
- Links are valid repository-relative paths or durable upstream URLs.
- Generated artefacts are identified as generated.
- Project journals are background material and are not presented as the
  runtime contract.
- Localized READMEs preserve the same structure as the primary README.
