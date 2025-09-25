# Messaging Integration (Prasso_Church ↔ Prasso_Messaging)

This document describes how the Church Management package integrates with the Messaging package without creating circular dependencies.

## Overview

- The Messaging package defines an interface contract (`Prasso\Messaging\Contracts\MemberContact`) that any domain "member-like" model can implement.
- The Church package's `Member` model implements this interface to expose contact details needed for messaging.
- The Messaging package is configured to use the fully qualified class name (FQCN) of the Church `Member` model via `config('messaging.member_model')` (env: `MESSAGING_MEMBER_MODEL`).
- This preserves a clean dependency direction: Church depends on Messaging's interface only; Messaging does not import from Church.

## Interface Contract

Implement the following interface on your domain model that represents a member/contact:

```
Prasso\Messaging\Contracts\MemberContact
```

Required methods:

- `getMemberId()`
- `getMemberEmail(): ?string`
- `getMemberPhone(): ?string`
- `getMemberDisplayName(): ?string`

The Church package implements this on `Prasso\Church\Models\Member` by mapping to existing fields (`id`, `email`, `phone`, `full_name`/`first_name` + `last_name`).

## Church Package Implementation

- File: `packages/prasso/church/src/Models/Member.php`
- Changes:
  - `implements Prasso\Messaging\Contracts\MemberContact`
  - New methods:
    - `getMemberId()` returns `$this->getKey()`
    - `getMemberEmail()` returns `$this->email`
    - `getMemberPhone()` returns `$this->getAttribute('phone')`
    - `getMemberDisplayName()` returns `$this->full_name` or a fallback of `first_name + last_name`

## Messaging Package Configuration

- File (package-level): `packages/prasso/messaging/config/messaging.php`
- File (app-level override): `config/messaging.php`
- Environment variable:

```
MESSAGING_MEMBER_MODEL="Prasso\\Church\\Models\\Member"
```

Ensure your app-level config includes:

```php
// config/messaging.php
'member_model' => env('MESSAGING_MEMBER_MODEL'),
```

## How Messaging Uses the Interface

- Jobs: `packages/prasso/messaging/src/Jobs/ProcessMsgDelivery.php`
  - Resolves members using `config('messaging.member_model')`
  - If the model implements `MemberContact`, it calls the interface methods.
  - If not, it falls back to common attributes (`email`, `phone`, `name`/`full_name`).

- Services: `packages/prasso/messaging/src/Services/RecipientResolver.php`
  - Same resolution approach when building recipient lists.

- API Validation: `packages/prasso/messaging/src/Http/Controllers/Api/MessageController.php`
  - Validates `member_ids.*` using a dynamic table derived from the configured `member_model` (no hardcoded table names).

## Testing the Integration

1. Set env:
   - `MESSAGING_MEMBER_MODEL="Prasso\\Church\\Models\\Member"`
2. Clear and cache config (optional):
   - `php artisan config:clear && php artisan cache:clear && php artisan config:cache`
3. Create a message via API (`/api/messages`) with `type` of `email` or `sms`.
4. Send using `/api/messages/send` with `member_ids: [<member_id>]`.

Notes:
- SMS requires Twilio credentials and a valid `phone` on the member.
- Email requires mailer configuration or will log by default depending on `MAIL_MAILER`.

## Rationale

- Avoids circular dependency: Messaging no longer imports Church models.
- Encourages clean boundaries via an interface defined in the Messaging package.
- Backwards compatible: If the model does not implement the interface, Messaging attempts reasonable fallbacks.
