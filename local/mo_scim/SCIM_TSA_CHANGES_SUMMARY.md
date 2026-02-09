# SCIM User Provisioning with IOMADOIDC Authentication - Implementation Summary

## Objective
Modify the `local/mo_scim` plugin to provision users with the `IOMADOIDC` authentication method instead of "manual account", enabling the `local/iomad_signup` plugin to automatically assign users to the correct tenant based on their email domain.

## Changes Made

### 1. Authentication Method Priority (`local/mo_scim/classes/utility/BaseUtility.php`)
**Modified:** `getauthenticationtypetoset()` method

Changed the authentication priority order to:
1. `iomadoidc` (first priority)
2. `iomadsaml2`
3. `mo_saml`
4. `saml2`
5. `manual` (fallback)

This ensures SCIM-provisioned users are created with IOMADOIDC authentication.

### 2. User Creation Flow (`local/mo_scim/classes/request/GetAndPostRequest.php`)
**Modified:** `createuser()` method

Key changes:
- **Uses `user_create_user()` with password bypass**: Calls `user_create_user($newuser, false, true)` to skip password policy validation
- **Sets password after creation**: Uses `update_internal_user_password()` to set the password (bypasses policy validation, similar to `create_user_record()`)
- **Password handling**: Uses Okta's password if provided, otherwise generates a random one. Since users authenticate via OIDC, password strength is not critical
- **Pre-lookup company by email domain**: Queries the `company_domains` table and sets `$CFG->foundcompanyid` before user creation
- **Domain caching**: Implements in-memory cache to avoid repeated database queries for the same domain
- **Clears session company variables**: Prevents interference from existing session state
- **Restores session state**: Maintains original session after user creation

**Added:** `getcompanyidfromdomain()` method
- Implements static in-memory cache for domain → company ID mappings
- Reduces database queries from N users to N unique domains
- For 2,000 users across 50 domains: 50 queries instead of 2,000 (98% reduction)

### 3. Error Handling (`local/mo_scim/classes/request/GetAndPostRequest.php`)
**Modified:** `handlepostrequest()` method

Added try-catch block to return proper SCIM-compliant JSON error responses instead of HTML error pages.

## How It Works

### User Provisioning Flow:
1. **Okta sends SCIM POST request** with user data (username, email, firstname, lastname, optional password)
2. **SCIM plugin receives request** and extracts user information
3. **Email domain lookup (cached)**: Plugin checks cache first, then queries `company_domains` table if needed
4. **Set company context**: Sets `$CFG->foundcompanyid` with the matched company ID
5. **Clear session**: Removes any existing session company variables
6. **Password handling**: Uses Okta's password if provided, otherwise generates random password
7. **Create user**: Calls `user_create_user()` with `$updatepassword = false` (all fields populated)
8. **Set password**: Calls `update_internal_user_password()` to bypass policy validation
9. **Event fires**: `user_created` event triggers with complete user data
10. **iomad_signup observer**: Detects IOMADOIDC auth type and assigns user to company
11. **Company assignment**: User is assigned to the company found via `$CFG->foundcompanyid`

### Why This Approach Works:
- **Domain-based routing**: Email domain determines the correct tenant automatically
- **Performance optimized**: Domain caching prevents redundant database queries during bulk sync
- **Password flexibility**: Accepts Okta passwords without policy enforcement (users authenticate via OIDC anyway)
- **No manual intervention**: Users are assigned to companies without admin action
- **OIDC authentication**: Users authenticate via Okta, not Moodle passwords
- **Session isolation**: SCIM requests don't interfere with existing user sessions
- **Event-driven**: Leverages existing IOMAD signup infrastructure

## Configuration Requirements

### 1. Enable and Configure `local/iomad_signup`
Run the setup script or manually configure:
- Enable the plugin: `local_iomad_signup_enable = 1`
- Set allowed auth types: `local_iomad_signup_auth = iomadoidc,iomadsaml2`

### 2. Company Domain Mappings
Ensure `company_domains` table has correct mappings:
- Domain: `treesolution.ch` → Company ID: `19`
- Domain: `treesolution.com` → Company ID: `3`

### 3. IOMADOIDC Authentication Plugin
Ensure `auth_iomadoidc` is installed and configured for Okta.

## Performance Considerations

### Domain Lookup Caching
The implementation uses a static in-memory cache for domain lookups:
- **First user from domain**: 1 database query
- **Subsequent users from same domain**: 0 queries (cached)
- **Bulk sync example**: 2,000 users across 50 domains = 50 queries instead of 2,000

This is critical for bulk user provisioning from Okta.

## Testing

### Verify User Assignment:
```
https://your-site.com/local/mo_scim/verify_assignment.php?userid=XXXX
```

### Check Domain Mappings:
```
https://your-site.com/local/mo_scim/verify_domains.php
```

### Test Password Generation:
```
https://your-site.com/local/mo_scim/check_password_policy.php
```

## Files Modified

1. `local/mo_scim/classes/utility/BaseUtility.php` - Authentication priority
2. `local/mo_scim/classes/request/GetAndPostRequest.php` - User creation flow, domain caching, and error handling

## Files Created (for debugging/testing)

1. `local/mo_scim/debug_signup.php` - Diagnose iomad_signup configuration
2. `local/mo_scim/setup_signup.php` - Enable and configure iomad_signup
3. `local/mo_scim/verify_assignment.php` - Check user company assignment
4. `local/mo_scim/verify_domains.php` - Verify domain mappings
5. `local/mo_scim/check_password_policy.php` - Test password generation
6. `local/mo_scim/test_observer.php` - Test event observer
7. `local/mo_scim/test_user_create.php` - Test user_create_user function
8. `local/mo_scim/debug_user_full.php` - Comprehensive user assignment debug

## Result

✅ Users provisioned from Okta via SCIM are now:
- Created with `IOMADOIDC` authentication method
- Automatically assigned to the correct company based on email domain
- Ready to authenticate via Okta SSO
- No manual intervention required
- Optimized for bulk provisioning with domain caching
