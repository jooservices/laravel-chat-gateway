# Extending A New Provider

To add a provider:

1. add a new module under `src/Providers/{Provider}`
2. implement provider, parser, verifier, sender, mapper, and polling fetcher when the provider supports poll mode
3. expose capabilities through `ProviderCapabilitiesDto`
4. register the provider in the service provider and manager
5. add fixtures and provider tests
6. document verification rules, payload mapping, and outbound behavior

For providers that support polling:

- implement `PollingCapableProviderContract`
- implement a `PollingUpdateFetcherContract`
- keep offset-safe behavior inside the provider fetcher plus `PollingService`
- reuse the normalized ingestion pipeline instead of duplicating contact, conversation, and message persistence

Keep all provider branching inside the new provider module.