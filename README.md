# Troop Tracker — XenForo Addon

**Addon ID:** `ObsidianSlicers/TroopTracker`  
**Version:** 1.1.0  
**Developer:** [Obsidian Slicers](https://github.com/obsidianslicers)  
**Requires:** XenForo 2.2.0+

Custom XenForo addon for the Troop Tracking Xenforo integration. Exposes API endpoints for user groups, upgrade/donation stats, smilies, and mobile user actions (block, report), plus a class extension that allows guests to view image attachments.

---

## Table of Contents

1. [File Structure](#file-structure)
2. [Installation](#installation)
3. [Registering Routes](#registering-routes)
4. [API Key Setup](#api-key-setup)
5. [Endpoints Reference](#endpoints-reference)
6. [Class Extensions](#class-extensions)
7. [API Scopes](#api-scopes)
8. [Development — CLI Commands](#development--cli-commands)

---

## File Structure

```
ObsidianSlicers/TroopTracker/
│
├── addon.json                          Addon metadata (ID, version, author)
├── Setup.php                           Install / upgrade / uninstall hooks
│
├── Api/Controller/
│   ├── Smilies.php                     GET    /api/smilies/
│   ├── TrooperApi.php                  GET    /api/trooper-api/block-user
│   │                                   GET    /api/trooper-api/report-post
│   │                                   POST   /api/trooper-api/watch-thread
│   │                                   DELETE /api/trooper-api/watch-thread
│   ├── UpgradeStats.php                GET    /api/upgrade-stats/
│   │                                   GET /api/upgrade-stats/user
│   └── UserGroups.php                  GET /api/user-groups/
│
├── Repository/
│   ├── UpgradeStats.php                DB queries for upgrade/donation data
│   └── UserGroups.php                  DB queries for user group membership
│
├── XF/Entity/
│   └── User.php                        Class extension — grants guests attachment access
│
├── XF/Pub/Controller/
│   └── Attachment.php                  Class extension — bypasses permission check for images
│
└── _data/
    ├── routes.xml                      All 4 API route definitions
    ├── api_scopes.xml                  upgrades:read, usergroups:read, smilie:read
    ├── class_extensions.xml            XF\Entity\User + XF\Pub\Controller\Attachment
    └── phrases.xml                     Display labels for the 3 API scopes
```

---

## Installation

### Option A — Admin Panel (recommended for production)

1. Zip the `ObsidianSlicers/TroopTracker` directory.
2. Log in to **XenForo Admin Panel**.
3. Go to **Admin > Add-ons > Install/Upgrade**.
4. Upload the zip and click **Install**.
5. Proceed to [Registering Routes](#registering-routes).

### Option B — CLI (recommended for development)

From your XenForo root:

```bash
# Import all addon data (routes, scopes, extensions, phrases)
php cmd.php xf-dev:import --addon "ObsidianSlicers/TroopTracker"

# Rebuild caches
php cmd.php xf:rebuild-caches
```

After running these commands the addon is fully active — no admin panel steps required.

---

## Registering Routes

Routes must be registered in the XenForo database. If you used the CLI import above they are already registered. If you need to add or re-add them manually:

**Admin > Development > Routes > Add Route**

> Note: The **Development** menu is only visible when XenForo development mode is enabled (`config.php`: `$config['development']['enabled'] = true;`). If development mode is off, routes imported via CLI are still active — you just can't edit them in the panel.

Fill in **only** these fields for each route. Leave all others blank.

---

### Route 1 — Trooper API

| Field | Value |
|---|---|
| Route type | `API` |
| Route prefix | `trooper-api` |
| Controller | `ObsidianSlicers\TroopTracker\Api\Controller\TrooperApi` |
| Add-on | `ObsidianSlicers/TroopTracker` |

---

### Route 2 — User Groups

| Field | Value |
|---|---|
| Route type | `API` |
| Route prefix | `user-groups` |
| Controller | `ObsidianSlicers\TroopTracker\Api\Controller\UserGroups` |
| Add-on | `ObsidianSlicers/TroopTracker` |

---

### Route 3 — Upgrade Stats

| Field | Value |
|---|---|
| Route type | `API` |
| Route prefix | `upgrade-stats` |
| Controller | `ObsidianSlicers\TroopTracker\Api\Controller\UpgradeStats` |
| Add-on | `ObsidianSlicers/TroopTracker` |

---

### Route 4 — Smilies

| Field | Value |
|---|---|
| Route type | `API` |
| Route prefix | `smilies` |
| Controller | `ObsidianSlicers\TroopTracker\Api\Controller\Smilies` |
| Add-on | `ObsidianSlicers/TroopTracker` |

---

## API Key Setup

All endpoints require an API key passed in the request header.

1. Go to **Admin > Tools > API keys > Add API key**.
2. Set a **Key description** (e.g. "Troop Tracker Mobile App").
3. Under **Scopes**, enable:
   - `upgrades:read` — for upgrade stats endpoints
   - `usergroups:read` — for user groups endpoint
   - `smilie:read` — for smilies endpoint
4. Save and copy the generated key.

Pass the key on every request:

```
XF-Api-Key: YOUR_API_KEY
```

For endpoints that act on behalf of a logged-in user (block-user, report-post), also pass:

```
XF-Api-User: USER_ID
```

---

## Endpoints Reference

> **Base URL:** `http://your-forum.com/index.php?api/`  
> Local dev example: `http://localhost:8888/forum/index.php?api/`

---

### GET `api/smilies`

Returns all smilies and smilie categories for the forum.

**Required scope:** `smilie:read`  
**Auth required:** API key only

**Example:**
```
http://localhost:8888/forum/index.php?api/smilies
```

**Response:**
```json
{
  "categories": [
    {
      "smilie_category_id": 1,
      "title": "General",
      "display_order": 10
    }
  ],
  "smilies": [
    {
      "smilie_id": 1,
      "title": "Smile",
      "smilie_text": [":)"],
      "smilie_category_id": 1,
      "display_order": 10,
      "display_in_editor": true,
      "emoji_shortname": ":smile:",
      "image_url": "styles/default/xenforo/smilies/smile.png",
      "image_url_2x": "",
      "sprite_mode": false,
      "sprite_params": null
    }
  ],
  "base_url": "https://your-forum.com/",
  "total": 42
}
```

---

### GET `api/user-groups`

Returns all user groups (primary and secondary) that a given user belongs to.

**Required scope:** `usergroups:read`  
**Auth required:** API key only

**Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `user_id` | integer | Yes | XenForo user ID |

**Example:**
```
http://localhost:8888/forum/index.php?api/user-groups&user_id=1
```

**Response:**
```json
{
  "userId": 1,
  "userGroups": [
    {
      "groupID": 3,
      "title": "501st Member",
      "bannerText": "Stormtrooper",
      "order": 1000,
      "isPrimary": true
    },
    {
      "groupID": 7,
      "title": "Garrison Staff",
      "bannerText": "Staff",
      "order": 900,
      "isPrimary": false
    }
  ]
}
```

**Error responses:**

| Status | Reason |
|---|---|
| 400 | `user_id` not provided |
| 404 | User not found |

---

### GET `api/upgrade-stats`

Returns all user upgrade/subscription data and payment logs across the entire forum.

**Required scope:** `upgrades:read`  
**Auth required:** API key only

**Example:**
```
http://localhost:8888/forum/index.php?api/upgrade-stats
```

**Response:**
```json
{
  "userUpgradeActive": [ ... ],
  "userUpgradeExpired": [ ... ],
  "userUpgrades": [ ... ],
  "combinedResults": [ ... ],
  "paymentLog": [ ... ]
}
```

| Key | Description |
|---|---|
| `userUpgradeActive` | All currently active subscriptions (`xf_user_upgrade_active`) |
| `userUpgradeExpired` | All expired subscriptions (`xf_user_upgrade_expired`) |
| `userUpgrades` | Upgrade definitions/tiers (`xf_user_upgrade`) |
| `combinedResults` | Active and expired records that started in the current calendar month |
| `paymentLog` | All payment records with message `"Payment received, upgraded/extended."` |

---

### GET `api/upgrade-stats/user`

Returns donation history and totals for a single user.

**Required scope:** `upgrades:read`  
**Auth required:** API key only

**Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `user_id` | integer | Yes | XenForo user ID |

**Example:**
```
http://localhost:8888/forum/index.php?api/upgrade-stats/user&user_id=1
```

**Response:**
```json
{
  "user_id": 1,
  "months_donated": 6,
  "total_donated": 30.00,
  "donations": [
    {
      "status": "active",
      "user_upgrade_record_id": 12,
      "user_id": 1,
      "user_upgrade_id": 2,
      "start_date": 1700000000,
      "end_date": 1702678400,
      "title": "Monthly Supporter",
      "cost_amount": "5.00",
      "cost_currency": "USD"
    }
  ]
}
```

**Error responses:**

| Status | Reason |
|---|---|
| 400 | `user_id` not provided |

---

### GET `api/trooper-api/block-user`

Blocks or unblocks another user on behalf of the authenticated visitor. Calling it a second time on the same pair **toggles** — it will unblock if already blocked.

**Required scope:** None (visitor session required)  
**Auth required:** `XF-Api-Key` + `XF-Api-User`

**Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `blocked_id` | integer | Yes | User ID of the person to block/unblock |

**Example:**
```
http://localhost:8888/forum/index.php?api/trooper-api/block-user&blocked_id=2
```
Headers:
```
XF-Api-Key: YOUR_API_KEY
XF-Api-User: 1
```

**Response (blocked):**
```json
{
  "message": "User blocked successfully.",
  "blocked": true
}
```

**Response (unblocked / toggled):**
```json
{
  "message": "User unblocked successfully.",
  "blocked": false
}
```

**Error responses:**

| Reason |
|---|
| `blocked_id` not provided |
| Attempting to block yourself |
| Target user not found |
| Not logged in |

---

### GET `api/trooper-api/report-post`

Reports a forum post on behalf of the authenticated visitor. The report is created in the `open` state and appears in the standard moderator report queue.

**Required scope:** None (visitor session required)  
**Auth required:** `XF-Api-Key` + `XF-Api-User`

**Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `post_id` | integer | Yes | ID of the post to report |
| `message` | string | No | Reason for the report (defaults to `"No reason provided."`) |

**Example:**
```
http://localhost:8888/forum/index.php?api/trooper-api/report-post&post_id=1&message=Spam
```
Headers:
```
XF-Api-Key: YOUR_API_KEY
XF-Api-User: 1
```

**Response:**
```json
{
  "message": "Post reported successfully.",
  "report_id": 5,
  "report_state": "open"
}
```

**Error responses:**

| Reason |
|---|
| `post_id` not provided |
| Post not found |
| Validation failed |
| Not logged in |

---

### POST `api/trooper-api/watch-thread`

Watches a thread on behalf of the authenticated visitor.

**Required scope:** `thread:write`  
**Auth required:** `XF-Api-Key` + `XF-Api-User`

**Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `thread_id` | integer | Yes | ID of the thread to watch |
| `email_subscribe` | bool | No | Receive email notifications (default `false`) |

**Example:**
```
POST http://localhost:8888/forum/index.php?api/trooper-api/watch-thread
```
Headers:
```
XF-Api-Key: YOUR_API_KEY
XF-Api-User: 1
```
Body:
```
thread_id=42&email_subscribe=0
```

**Response:**
```json
{
  "success": true,
  "watching": true,
  "email_subscribe": false
}
```

**Error responses:**

| Reason |
|---|
| `thread_id` not provided |
| Thread not found |
| Not logged in |
| Token missing `thread:write` scope |

---

### DELETE `api/trooper-api/watch-thread`

Unwatches a thread on behalf of the authenticated visitor. Safe to call even if the thread is not currently watched.

**Required scope:** `thread:write`  
**Auth required:** `XF-Api-Key` + `XF-Api-User`

**Parameters:**

| Param | Type | Required | Description |
|---|---|---|---|
| `thread_id` | integer | Yes | ID of the thread to unwatch |

**Example:**
```
DELETE http://localhost:8888/forum/index.php?api/trooper-api/watch-thread
```
Headers:
```
XF-Api-Key: YOUR_API_KEY
XF-Api-User: 1
```
Body:
```
thread_id=42
```

**Response:**
```json
{
  "success": true,
  "watching": false
}
```

**Error responses:**

| Reason |
|---|
| `thread_id` not provided |
| Thread not found |
| Not logged in |
| Token missing `thread:write` scope |

---

## Class Extensions

These run automatically once the addon is installed — no configuration needed.

### `XF\Entity\User` → `ObsidianSlicers\TroopTracker\XF\Entity\User`

Overrides `hasPermission()`. When a guest (`user_id == 0`) accesses a URL containing `index.php?attachments`, permission checks return `true`, allowing image viewing without an account. All other permission checks fall through to XenForo's standard logic.

### `XF\Pub\Controller\Attachment` → `ObsidianSlicers\TroopTracker\XF\Pub\Controller\Attachment`

Overrides `actionIndex()`. For image file types (`jpg`, `jpeg`, `png`, `gif`, `webp`) the `canView()` permission check is skipped entirely — the file is served directly. For all other file types (zip, pdf, etc.) the standard permission check is enforced. Temporary-hash attachments (freshly uploaded, not yet associated with a post) always require the correct hash regardless of file type.

---

## API Scopes

Scopes are defined in `_data/api_scopes.xml` and visible under **Admin > Development > API scopes**.

| Scope ID | Used by | Description |
|---|---|---|
| `upgrades:read` | `UpgradeStats` controller | View upgrade and payment data |
| `usergroups:read` | `UserGroups` controller | View user group membership |
| `smilie:read` | `Smilies` controller | View smilies and categories |

The `trooper-api` endpoints (`block-user`, `report-post`) do not use a scope — they use visitor session authentication instead. The `watch-thread` endpoints require the `thread:write` scope.

---

## Development — CLI Commands

Run from the XenForo root directory (`/path/to/forum/`).

```bash
# Import all addon XML data into the database
php cmd.php xf-dev:import --addon "ObsidianSlicers/TroopTracker"

# Rebuild all caches after importing
php cmd.php xf:rebuild-caches

# Export current DB state back to _data XML files (after making changes in admin panel)
php cmd.php xf-dev:export --addon "ObsidianSlicers/TroopTracker"
```
