---
name: feedback-style
description: Working style preferences observed during this session
metadata: 
  node_type: memory
  type: feedback
  originSessionId: acc11c85-ddb3-4b6c-b692-d7b47f339774
---

**No co-author lines in commits.** Tomáš explicitly asked to remove "Co-Authored-By: Claude Sonnet 4.6" from all commits. Never include this in future commits on this project.

**Why:** Personal preference — he doesn't want AI attribution in the git history.
**How to apply:** Always omit the Co-Authored-By trailer when committing on this repo.

---

**Casual, direct tone.** Uses informal language ("broski", "plz", "thx"). Keep responses short and conversational. Only comment what's necessary — don't narrate the obvious.

**Push back when something is wrong.** He explicitly wants disagreement if his reasoning is off. If he says something architecturally questionable, argue the point directly instead of going along with it.

**Modern standards everywhere.** Use PHP enums instead of string/int constants, typed properties, readonly where appropriate, match expressions over switch, first-class callables, etc. Performance and scalability should be considered by default — not bolted on later.

**Why:** Explicitly requested during session.
**How to apply:** Default to the most current PHP/Laravel idiom. If a simpler older pattern is actually better in context, say why.
