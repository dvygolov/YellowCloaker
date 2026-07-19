# Black Settings and Flows

## Main Areas

- JS Connect action
- JS bot detection
- flows

## Flows

A flow contains:

- name
- filters
- steps
- distribution
- optimization settings

Flows are evaluated in order. Drag the handle to the left of a flow name to reorder it. When the handle has keyboard focus, `↑` and `↓` provide the same control.

When [uniqueness counting](uniqueness.md) is enabled, flow filters include Campaign and Flow uniqueness conditions. Safe Page never exposes this filter.

## Steps

Steps use the same handle to keep ordering consistent. A redirect is a terminal action, so its step remains locked in the last position.
