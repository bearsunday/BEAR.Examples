---
name: review-honestly
description: When asked to review a framework, library, codebase, or design, follow this procedure to keep unverified assertions (i.e. lies) out of the output. Distilled from a failure where I produced a fluent BEAR.Sunday "review" with eight false claims out of ten, only catching it after the user pushed back.
when_to_use: User asks to "review", "critique", "evaluate", or "assess" some technology or design. Also applies whenever you start drifting into critique-shaped writing on your own initiative.
type: skill
---

# Review Honestly

## Core principle

**"Writing a review" ≠ "generating review-shaped text."**

A review is an *investigation task*, not a writing task. The fact that text comes
out fluent does not guarantee its content is true. When fluent prose flows out,
the verification step is the first thing to get skipped.

When asked to review:

1. Do not start writing.
2. List what evidence would back each claim you might want to make.
3. Gather that evidence before any prose appears.

---

## Detection signals — you are entering "fake review production mode"

Watch for these. If any fire while drafting, **stop writing and return to
investigation**.

| Signal                            | What it looks like                                                                                              |
|-----------------------------------|-----------------------------------------------------------------------------------------------------------------|
| **Template filling**              | The structure ("Strengths / Weaknesses / When to adopt / Conclusion") is decided first, content is poured into slots |
| **Manufactured weaknesses**       | "I wrote strengths, so I need weaknesses to look balanced" — searching for things to criticise                  |
| **Industry meme reuse**           | "AOP is magic", "DI is hard to debug", "the community is small" applied without verifying for *this* target     |
| **Tautological criticism**        | "X has the downside of requiring you to learn X" — restating a feature as a flaw                                |
| **Recycled binary**               | "Laravel is comfortable / BEAR is structured" — the same opposition used three times in the document            |
| **Consumer voice**                | "Documentation is thin", "no cookbook" — when *you* are the one being asked to write the cookbook               |
| **Borrowed template > first-hand experience** | You just spent a day succeeding with X, and you are writing "X is unsuitable for short MVPs"        |

---

## Procedure (in order)

### Step 1 — Extract your own experience (5–15 min)

The only honest source is what actually happened while using the thing.
Write down, before any prose:

- moments of surprise (positive and negative)
- moments where you got stuck (specifically: where, how long, why)
- behaviour that diverged from what you expected (what you predicted vs. what occurred)
- moments where things went easily (specifically what was easy)
- repeated tasks (the feeling of repetition is real signal)

Example, from a real session:

> - DbQueryInterceptor never calls exec() — got stuck on this in Phase 9
> - Eleven phases finished with zero rework
> - Fake and real-SQL produced the same body shape
> - Hesitated when adopting the getBy{naturalKey} pattern

This is the only material you are entitled to use before investigation.

### Step 2 — Decompose abstract claims into concrete checks (10–30 min)

For each abstract claim you want to make, list what would have to be observed to
verify it.

| Abstract claim                   | Verification                                                                                  |
|----------------------------------|-----------------------------------------------------------------------------------------------|
| "Type-safety is high"            | grep the codebase for `mixed` / `array<>`. Coverage of typed constructor parameters.         |
| "DI errors are hard to follow"   | Build an unbound configuration on purpose. Read the actual error message.                     |
| "Documentation is fragmented"    | Enumerate doc paths and cross-link counts.                                                    |
| "Learning curve is steep"        | Which specific concept *did you* get stuck on the first time? Not "would a beginner".         |

If you cannot name a verification path, **you are not entitled to make the claim**.

### Step 3 — Actually investigate (30–60 min)

Typical moves:

- **Read the relevant source** — usually 1–3 core files, ~60–200 lines each
- **Trigger the error you want to characterise** — write code that breaks the
  condition and observe the actual exception, message, and stack
- **Inspect generated artefacts** — proxy classes, caches, compiled DI files
- **Run the counterexample** — if you want to write "unsuitable for X", build
  the X case and see what breaks

Record the findings in a draft. **Still do not start the prose.**

### Step 4 — Attach evidence to each claim

Beside every claim in your outline, write a `file:line` citation or the literal
output you observed. If a claim has no citation, it must be either:

- (a) deleted, or
- (b) explicitly marked as "speculation / not verified", or
- (c) sent back to Step 3 for more investigation.

### Step 5 — Write the prose

Only now is writing allowed. Rules:

1. **Observation first** — start with "When I did X, Y happened"
2. **Abstraction follows observation** — "this means the design has property Z" is
   a *consequence* of an observation, not a leading sentence
3. **Mark the unverified** — explicitly say "I did not check" or "speculative"
   before any unverified claim. Never assert without evidence
4. **Restrict comparisons** — "compared to Laravel" claims are valid only if you
   have investigated both. Otherwise drop them
5. **Skip "When to adopt" sections** — that is consumer-grade prose. A reviewer
   can only say "for the case I had, here is what I decided"

### Step 6 — Self-check before publishing

For each paragraph:

```
[ ] Does this come from the last 24 hours of doing the thing, or is it borrowed?
[ ] Can I cite a file:line, an observed output, or a session log entry for it?
[ ] Did I add this weakness for "balance"?
[ ] Am I writing as a designer, or as a consumer?
[ ] Did I reuse the same binary opposition somewhere else in this document?
[ ] Am I leaning on phrases like "the industry says", "in general", "beginners struggle with"?
```

If any check fails, delete or rewrite the paragraph.

---

## Banned phrases (never use without verification)

These are content-free industry filler. They acquire meaning only when grounded
in a specific observed event.

- "the learning curve is steep"
- "the community is small"
- "hard to see in an IDE"
- "documentation is fragmented"
- "hard to reproduce N years from now"
- "not suitable for short MVPs"
- "beginners get lost"
- "magic" / "black magic"
- "(other framework) has better DX"

When tempted to use one, replace it with the specific event:

> ✗ "DI binding errors are opaque."
>
> ✓ "I expected a clear missing-binding message and triggered an unbound
> dependency to verify. The actual `__toString()` walked the full Throwable
> chain and showed `'TypeName-' in file:line ($paramName)` for every level
> down to the deepest unresolved dependency. My expectation was wrong."

---

## Output format

**Do not** use the strengths / weaknesses / when-to-adopt template.

Use this instead:

### 1. What happened in this session (raw material)
- Specific events, bullet form
- Each item annotated with `file:line` / time spent / lines of code

### 2. Observed behaviour (verified facts)
- Claim + evidence (`file:line` / experiment output)

### 3. Open questions
- Things you did not check, stated explicitly

### 4. (Optional) Personal opinion
- Derived from items 1–3, target-specific
- No general statements about "the industry"

Do not include adoption recommendations, balanced strengths/weaknesses lists,
or framework comparisons. Readers who want those should look elsewhere.

---

## Final check before publishing

Ask yourself, paragraph by paragraph:

> **If someone who knows this target completely (e.g. the author) read this
> sentence, where would they reply "what are you talking about"?**

Wherever an answer comes to mind, the sentence is unverified. Rewrite or remove
it.

---

## Meta — why this skill exists

After one day of building with a framework, I was asked for a review. I produced
fluent review-shaped text without doing the underlying investigation. Eight of
ten weaknesses I listed were factually wrong; two flipped to their opposites
under verification. The author of the framework caught it and named it as
lying. They were right.

Failure structure:

- Treated "write a review" as a text-generation task instead of an
  investigation task
- Implicitly equated "fluent prose" with "true content"
- Filled the strengths/weaknesses/when-to-adopt template
- Let a borrowed template ("BEAR is unsuitable for short MVPs") overwrite my
  own first-hand experience (one-day reference build with zero rework)

This skill encodes the procedure that, if I had followed it, would have stopped
the lie at Step 2.

---

## Related artefacts in this project

- [build-log.md](build-log.md) — phase-by-phase build log; example of the
  granularity Step 1 needs
- [verified.md](verified.md) — experiments that confirmed/refuted specific
  claims; example of Step 3–4 evidence
- [framework-critique.md](framework-critique.md) — what happens without this
  skill. Cautionary example
- [critique-second-pass.md](critique-second-pass.md) — post-mortem of the
  failure that produced the cautionary example
