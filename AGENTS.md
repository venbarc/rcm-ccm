# Project Guidance

## Claims table layout

- Preserve the expanded CPT-line table's own horizontal scroller and its existing sticky `Actions` header/cells.
- Keep the parent claims table at `min-w-[1540px]`. Do not increase this minimum width merely to accommodate another parent column; doing so moves the nested sticky boundary beyond the visible claims panel.
- Keep `Primary Provider` in the parent claim row immediately after `Payer`, and hide it from the expanded CPT-line rows.
- Before restructuring this table's scrolling or sticky positioning, compare the proposed change with the working Git baseline. Prefer the smallest column-only change and verify both the collapsed and expanded layouts.
