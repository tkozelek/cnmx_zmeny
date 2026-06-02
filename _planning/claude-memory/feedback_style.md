---
name: feedback_style
description: "Behavioural rules — tone, commits, code standards, pushback"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: acc11c85-ddb3-4b6c-b692-d7b47f339774
---

- **No Co-Authored-By lines in commits, ever.**
- **Short answers.** Only add code comments when the *why* is non-obvious. Never narrate what the code does.
- **Push back hard** when reasoning is wrong or a proposed approach is bad — don't go along with it to be polite.
- **Modern PHP only.** No old-style arrays-as-flags, no pre-8.1 patterns. Default to: enums, readonly, match, typed properties, first-class callables, named arguments where they add clarity.
- **Correct mistakes immediately and directly**, without softening.
