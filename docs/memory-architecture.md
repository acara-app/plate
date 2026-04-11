# Semantic Memory System - Implementation Architecture

This document describes the architecture for implementing the semantic memory system contracts.

## Overview

The memory system provides AI agents with persistent, semantic memory capabilities - allowing them to store, retrieve, and reason about information across conversations.

```
┌─────────────────────────────────────────────────────────────┐
│                        AI AGENT                             │
│  Uses Memory::store(), Memory::search(), etc.               │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Memory Facade                            │
│  App\Ai\Facades\Memory                                      │
│  Resolves tool contracts from container                     │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                  Tool Contracts                             │
│  16 interfaces in App\Ai\Contracts\Memory\                  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Concrete Implementations                       │
│  (To be created in App\Ai\Tools\Memory\)                    │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Vector Database + PostgreSQL/pgvector          │
└─────────────────────────────────────────────────────────────┘
```

---

## Storage Schema

### Memory Record (Vector DB)

| Field         | Type        | Description                 |
| ------------- | ----------- | --------------------------- |
| `id`          | UUID        | Unique identifier           |
| `content`     | string      | Natural language content    |
| `vector`      | float[1536] | Embedding from AI model     |
| `metadata`    | JSON        | Tags, source, user_id, etc. |
| `importance`  | int (1-10)  | Priority score              |
| `categories`  | string[]    | Semantic categories         |
| `is_archived` | bool        | Cold storage flag           |
| `expires_at`  | timestamp?  | TTL for temporal memories   |
| `created_at`  | timestamp   | Creation time               |
| `updated_at`  | timestamp   | Last update time            |

### Memory Links Table (PostgreSQL)

| Field              | Type      | Description                               |
| ------------------ | --------- | ----------------------------------------- |
| `source_memory_id` | UUID      | Source memory                             |
| `target_memory_id` | UUID      | Target memory                             |
| `relationship`     | string    | Type: related, contradicts, follows, etc. |
| `created_at`       | timestamp | When link was created                     |

---

## Tool Implementation Guide

### CRUD Operations

| Tool               | Implementation                                         |
| ------------------ | ------------------------------------------------------ |
| `StoreMemoryTool`  | Generate embedding → Insert to vector DB               |
| `GetMemoryTool`    | Lookup by ID                                           |
| `UpdateMemoryTool` | Update fields, regenerate embedding if content changed |
| `DeleteMemoryTool` | Delete by ID or filter                                 |

### Search Operations

| Tool                       | Implementation                                          |
| -------------------------- | ------------------------------------------------------- |
| `SearchMemoryTool`         | Embed query → ANN search → Filter by metadata/threshold |
| `GetImportantMemoriesTool` | Query importance >= threshold                           |
| `GetRelatedMemoriesTool`   | BFS/DFS graph traversal on links table                  |

### AI-Powered Operations

| Tool                      | Implementation                          |
| ------------------------- | --------------------------------------- |
| `CategorizeMemoriesTool`  | AI prompt to classify content           |
| `ReflectOnMemoriesTool`   | AI analyzes patterns in recent memories |
| `ValidateMemoryTool`      | AI fact-checks content accuracy         |
| `ConsolidateMemoriesTool` | AI synthesizes + merge memories         |

### Maintenance Operations

| Tool                  | Implementation                        |
| --------------------- | ------------------------------------- |
| `DecayMemoriesTool`   | Cron job: reduce importance over time |
| `ArchiveMemoriesTool` | Set is_archived = true                |
| `RestoreMemoriesTool` | Set is_archived = false               |
| `GetMemoryStatTool`   | Aggregate queries for statistics      |
| `LinkMemoriesTool`    | Insert into links table               |

---

## Suggested File Structure

```
app/Ai/
├── Contracts/Memory/           # ✅ Interfaces (done)
├── Exceptions/Memory/          # ✅ Exceptions (done)
├── Facades/
│   └── Memory.php              # ✅ Static facade (done)
└── Tools/Memory/               # 🔲 Implementations (todo)
    ├── StoreMemory.php
    ├── SearchMemory.php
    ├── GetMemory.php
    ├── UpdateMemory.php
    ├── DeleteMemory.php
    ├── CategorizeMemories.php
    ├── ConsolidateMemories.php
    ├── ReflectOnMemories.php
    ├── GetImportantMemories.php
    ├── GetMemoryStat.php
    ├── LinkMemories.php
    ├── GetRelatedMemories.php
    ├── DecayMemories.php
    ├── ValidateMemory.php
    ├── ArchiveMemories.php
    └── RestoreMemories.php

app/Data/Memory/         # ✅ DTOs (done)

app/Services/Memory/            # 🔲 Supporting services (todo)
    ├── EmbeddingService.php    # Generate embeddings
    └── VectorStoreService.php  # Vector DB abstraction
```
