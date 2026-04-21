# AI Integration Boundary Note

This package is intentionally an AI-ready transport foundation, not an AI runtime.

Future AI layers may subscribe to runtime events or query the operational plus audit records, but that orchestration belongs in another package or application layer.

This repository does not add prompts, memory, agents, or RAG behavior.