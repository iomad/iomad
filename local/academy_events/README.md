# Academy Events Plugin

## Purpose

This plugin records custom events for IOMAD role assignments and captures user data before deletion to ensure webhooks receive actual email addresses instead of anonymized hashes.

## Features

Currently tracks:
- **Company Manager Role Assigned**: Logs when a user is assigned the company manager role
- **Company Manager Role Unassigned**: Logs when a user is unassigned from the company manager role (includes original email if user is being deleted)

## Event Details

Events are logged with:
- User who performed the action
- User who received/lost the role
- Role ID and name
- Context (company)
- Timestamp

For role unassignment events, additional data is included:
- Username
- Email address (original, not anonymized)
- First and last name
- `is_deletion` flag (true if triggered by user deletion)

## How It Works

### Normal Role Unassignment
When a company manager role is manually unassigned, the event includes the current user data from the database.

### User Deletion
When a user with the company manager role is deleted:
1. The `before_user_deleted` hook captures the user's original data (email, username, etc.) and stores it in cache
2. Moodle proceeds with deletion, which triggers `role_unassigned` events
3. The `role_unassigned` observer checks the cache for stored user data
4. If found (within 60 seconds), it includes the original email and user data in the event
5. The cache entry is then deleted

This ensures webhooks and integrations receive the actual email address, not the MD5 hash that Moodle creates during user deletion.

## Viewing Events

Events can be viewed in:
- Site administration → Reports → Logs
- Filter by component: "Academy Events"

## Webhook Integration

When using tools like Zapier or tool_trigger, the `companymanager_unassigned` event will include in the `other` field:
```json
{
  "roleid": 10,
  "rolename": "companymanager",
  "username": "johndoe",
  "email": "john.doe@example.com",
  "firstname": "John",
  "lastname": "Doe",
  "is_deletion": true
}
```

## Future Extensibility

The plugin is named "Academy Events" to allow easy addition of other custom events in the future without requiring a plugin rename.
