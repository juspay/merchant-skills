# Juspay Skill Bank — Framework and Structure

<!-- SKELETON — content to be filled in. This document explains the methodology
     behind the skill bank: the layered architecture, SKILL.md anatomy, naming
     conventions, splitting heuristics, the hybrid docs strategy, and phasing. -->

## 1. Problem statement

<!-- TODO -->

## 2. Solution overview — the layered skill bank

<!-- TODO: the layers — mcp/ (shared docs-access skill), integrations/
     (orchestrators), go-live/, bank entry point. There is no foundations or
     api-references layer: cross-cutting concerns (auth, webhooks, errors,
     status) and per-API schemas are carried per-product by the juspay-docs MCP
     docs and fetched on demand (see §4) — re-narrating them in the bank would
     only duplicate the MCP. -->

## 3. Core principles

<!-- TODO: single responsibility, discoverable activation, progressive disclosure,
     composability. -->

## 4. Docs strategy — hybrid (structure + MCP)

<!-- TODO: skill cards own structure/sequence/decisions/gotchas; exhaustive
     endpoint/payload/field schemas are fetched on demand from the juspay-docs
     MCP server. Why hybrid: we don't hand-maintain full schemas, and the MCP
     keeps field-level detail current. -->

## 5. SKILL.md anatomy

<!-- TODO: frontmatter + standard sections per layer. -->

## 6. Folder structure

<!-- TODO -->

## 7. The layer contract

<!-- TODO: knowledge flows one direction; what each layer owns vs delegates. -->

## 8. Naming conventions

<!-- TODO -->

## 9. Splitting vs merging heuristics

<!-- TODO -->

## 10. Authoring quality bar

<!-- TODO -->

## 11. Phasing

<!-- TODO -->

## References

<!-- TODO -->
