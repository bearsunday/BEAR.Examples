# Validation layer design

This note records the validation-layer architecture agreed on in
[Issue #37](https://github.com/bearsunday/MyVendor.Cms/issues/37) and
the modernisation meeting that followed. The corresponding code lives
under `src/Validation/`, `src/Exception/ValidationException.php`, and
the `errorMessage` keys in `var/json_validate/*.json`.

---

## Goal

Make the **error shape on the wire** the same regardless of which
layer detected the failure:

1. Structural failure — JSON Schema (string length, regex, enum,
   required, type, format, …).
2. Type-shape failure that runs before the schema — resource parameter /
   DTO input hydration (e.g. scalar `tagIds` against an `array` DTO
   parameter), surfacing as `BEAR\Resource\Exception\ParameterException`.
3. Domain failure (deferred) — DB-dependent checks such as slug
   uniqueness, FK existence, state-transition guards.

Clients (both the HAL+JSON API and the Qiq admin pages) only see one
shape: a field-keyed map of human-readable messages. Per Issue #37
agreement, **the resource method itself does not throw domain
validation exceptions** — that responsibility belongs to the DTO or a
dedicated validator service called from the DTO. Today that surface
isn't exercised yet; the architecture is set up so it can land
without changing the wire contract.

---

## Components

### `JsonSchemaRequestExceptionHandler`

Implements
`BEAR\Resource\JsonSchemaRequestExceptionHandlerInterface` — the
hook `JsonSchemaInterceptor` calls when `params:` validation fails.
The default `JsonSchemaRequestExceptionNullHandler` rethrows the
original exception.

BEAR.Resource now distinguishes JSON Schema failure source before app
code sees the exception:

- `JsonSchemaRequestException` — request/`params:` validation, 4xx-class.
- `JsonSchemaResponseException` — response body validation, 5xx-class.

That source split landed via
[bearsunday/BEAR.Resource#369](https://github.com/bearsunday/BEAR.Resource/issues/369).
The structured-error carrier on `JsonSchemaException` landed via
[bearsunday/BEAR.Resource#364](https://github.com/bearsunday/BEAR.Resource/issues/364);
`getErrors()` now returns a `JsonSchemaErrors` collection.

This handler is bound only to the request hook. It iterates the delivered
request exception's `JsonSchemaErrors` collection and groups the
already-rendered `JsonSchemaError::$message` values into a
`field => list<string>` map. Response schema failures are delivered to the
separate response handler and are deliberately not translated into
`ValidationException`: they are server-side contract bugs, not user input
errors.

If `getErrors()->hasErrors()` is false (manual throws, future paths that
bypass the upstream mapper) the handler rethrows the original exception
rather than surfacing a content-free `ValidationException` — an empty error
shape would silently swallow the signal.

Per-message lookup follows the **ajv-errors** convention, but it is now
performed upstream by BEAR.Resource's `JsonSchemaErrorMapper` before this app
handler runs:

- `properties.<field>.errorMessage.<constraint>` — per-constraint
  override on a field. `<constraint>` matches `ConstraintError`
  values: `pattern`, `minLength`, `maxLength`, `enum`, `minimum`,
  `format`, `type`, etc.
- `properties.<field>.errorMessage` as a string — fallback used for
  any failure on the field. Useful when one message covers several
  constraints (`"Slug is invalid."`).
- `errorMessage.required.<field>` on the parent object — `required`
  failures bubble up to the parent in justinrainbow, so the
  override lives at the object level (mirroring ajv-errors).

When no `errorMessage` matches, the validator's default message is
used unchanged.

### `ValidationException`

Lives in `src/Exception/ValidationException.php`. Carries the
`field => list<string>` map. Implements
`BEAR\Resource\Exception\ExceptionInterface` so it joins the
framework's exception hierarchy and is recognised by BEAR-side
machinery without further wiring.

The exception is the only practical short-circuit: the interceptor
calls the handler **before** `$invocation->proceed()`, and if the
handler returns normally the interceptor falls through to
`proceed()` — so the resource method would run with invalid input
regardless of any state the handler sets on `$ro`. Throwing is the
contract.

---

## Wiring

`AppModule::configure()` rebinds the handler after
`JsonSchemaModule` is installed:

```php
$this->install(new JsonSchemaModule(/* … */));
$this->bind(JsonSchemaRequestExceptionHandlerInterface::class)
    ->to(JsonSchemaRequestExceptionHandler::class)
    ->in(Scope::SINGLETON);
```

Test, fake, and CLI contexts inherit through `AppModule`, so the
handler is active everywhere the JSON Schema interceptor runs.

---

## Resource-side contract

App resources (`Resource/App/Article`, `Resource/App/Auth`, …)
**do not** catch `ValidationException`. The exception propagates to
the immediate caller — either a Page resource composing the App
resource, or the test harness asserting against the structured
errors. The wire contract for `app://` is "thrown exception with
structured errors", not "422 body" — the BEAR.Resource invocation
itself is the call site, and the body would otherwise need a global
exception-to-status mapping that the framework deliberately leaves
to the transfer layer.

Response schema failures do not enter this contract. BEAR.Resource delivers
them to `JsonSchemaExceptionHandlerInterface` as
`JsonSchemaResponseException`; this application leaves the default response
handler in place so invalid response bodies surface as framework/server
errors rather than as form-validation messages.

Page resources (`Resource/Page/Admin/Article`, etc.) catch
`ValidationException` from the inner `app://` call and rewrite the
form body to surface field-keyed errors:

```php
try {
    return $this->resource->post('app://self/article', $values);
} catch (ValidationException $e) {
    $this->code = 422;
    $this->body = $this->formBody($article, $values, $e->errors, null);
    return $this;
}
```

`ParameterException` (resource parameter / DTO input shape failures) is
caught separately and funnelled into the same 422 body under the `_global`
field, so the form template renders both paths through a single error-list
block.

---

## Schema authoring rules

1. `errorMessage` lives next to the constraint it overrides. The
   constraint itself stays — `pattern: "^[a-z]+$"` keeps doing the
   actual validation, and `errorMessage.pattern` only owns the
   *copy*.
2. Required-field copy goes on the parent object under
   `errorMessage.required.<field>`. JSON Schema reports `required`
   on the parent; we keep the schema true to the validator's model.
3. `errorMessage` is non-normative JSON Schema (sourced from
   `ajv-errors`); other validators ignore it. That's the intended
   trade-off — schemas are still portable, and only this codebase's
   handler reads the overrides.
4. Don't put `errorMessage` everywhere preemptively. Add it when the
   default message reads awkwardly to a user or when the wording
   needs to match the admin's vocabulary (e.g. "Status must be
   either 'draft' or 'published'").

---

## Why DTO-side, not resource-side, for domain validation

The meeting decision was that DB-dependent validation (slug
uniqueness, FK existence) belongs in the DTO or a validator service
called from the DTO — **not** in the resource method via inline
`throw new SlugAlreadyTakenException`. The reasoning:

- The resource method's job is the state transition. Mixing
  validation throws into it confuses the layer.
- The DTO is already the typed boundary between unsafe request data
  and the resource. Pushing the domain check there means the
  resource can trust the materialised object.
- Today, Input DTOs (Ray.InputQuery's `#[Input]`-bound parameters)
  are hydrated from the flat request array, not the DI container.
  Extending them to invoke an injected validator is the next step;
  the architectural slot is reserved here so the wire contract
  doesn't change when it lands.

When that lands, the DTO calls into a validator service that
throws `ValidationException` with the same `field => list<string>`
shape. `Page/Admin/Article` already catches `ValidationException`,
so no resource-layer change is needed for the domain path.

---

## Out of scope (deferred)

- **`SameOrigin` / `CsrfToken` interceptors** — landed alongside this
  PR; see [`csrf-design.md`](csrf-design.md).
- **Confirmation-screen resource** — landed alongside this PR; see
  [`article-publish-flow-design.md`](article-publish-flow-design.md).
- **JSON Schema for response error shape** — register a
  `validation_problem.json` schema and validate the 422 body shape
  in tests. Useful but not load-bearing for correctness; can land
  alongside response-shape catalogue work.

---

## Test coverage

- `tests/Resource/App/ArticleTest::testPostRejectsInvalidSlugPattern`
  and `…testPostRejectsInvalidStatusEnum` lock the App-side
  behaviour: `ValidationException` with a field key matching the
  failing input.
- `tests/Validation/JsonSchemaRequestExceptionHandlerTest` exercises
  the handler against hand-constructed `JsonSchemaRequestException`
  instances to cover field grouping, repeated messages for one field, root
  errors, and empty-errors rethrow.
  Hand-constructed because the unit test is intentionally hermetic from
  `justinrainbow/json-schema`; upstream owns the mapper and
  `errorMessage` resolution tests.
- `tests/Resource/Page/Admin/ArticleTest::testInvalidCreateReturnsFormWithEscapedValues`
  pins the Page-side 422 rendering: `<section class="ErrorList">`
  appears and unsafe input is escaped.
