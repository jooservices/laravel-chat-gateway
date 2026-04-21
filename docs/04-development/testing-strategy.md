# Testing Strategy

Test layout:

- `tests/Feature/Http`
- `tests/Feature/Services`
- `tests/Feature/Providers`
- `tests/Feature/Persistence`
- `tests/Unit`
- `tests/Fixtures`

Rules:

- controller tests hit real package routes
- service tests use the real SQLite test database
- parser and verifier tests use real fixtures
- network calls are mocked only at the client-factory boundary
- poll and callback flows both require regression coverage
- polling offset advancement must be verified for success and failure paths

Official mocking convention:

- use fixture-based mocking
- use `loadFixture()` and `loadJsonFixture()`
- mock `ProviderHttpClientFactoryContract`
- return mocked `HttpClientInterface`
- return mocked `ResponseWrapperInterface`
- do not build provider-specific fake client classes as the default testing pattern

Polling-specific expectations:

- add `getUpdates` fixtures under `tests/Fixtures/{provider}`
- test the provider polling fetcher with mocked HTTP client responses
- test `gateway:poll {provider}` command behavior
- test `chat_polling_states` persistence and offset updates
- keep callback route tests in place when poll-mode defaults change