# tests-csrf — library-pure tests

Lives alongside `src-csrf/`. Tests that depend only on the
`Ray\Csrf\` library surface — no MyVendor\Cms dependency — live
here so they migrate with the source when `ray/csrf-module` is
published.

CMS-coupled tests (wiring, attribute coverage on Page/Admin) live
in `tests/Interceptor/` instead.

The interceptor unit tests (`SameOriginInterceptorTest`,
`CsrfTokenInterceptorTest`) currently live in `tests/Interceptor/`
rather than here because they depend on the CMS-side fakes
(`tests/Fake/Fake{CsrfToken,RequestOrigin,RequestBodyToken}`).
When the package is donated upstream, those fakes (and the unit
tests) should move alongside.
