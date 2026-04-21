# Contributing

Before considering a change complete:

1. keep the change inside the package scope
2. update tests with behavior changes
3. update docs with behavior changes
4. run `composer lint:all`
5. run `composer test`

When adding public behavior, update both `AGENTS.md` and `.agents/skills/chat-gateway-foundation/SKILL.md` if agent-facing guidance changes.