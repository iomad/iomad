## Feature Enhancement: Add UserInfo Endpoint Support and Improve Username Claim Handling for Non-Microsoft IdPs

### Summary
The `auth/iomadoidc` plugin currently has limited support for non-Microsoft OIDC providers (like Okta, Auth0, Keycloak, etc.) due to two main issues:
1. It does not fetch claims from the OIDC UserInfo endpoint
2. Username extraction logic only checks for Microsoft-specific claims (`upn`, `unique_name`) for non-Microsoft IdP types, missing standard OIDC claims like `preferred_username` and `email`

This results in missing user profile data (First Name, Last Name) and incorrect username mapping (falling back to the `sub` claim instead of using the user's email).

### Problem Description

#### Issue 1: Missing UserInfo Endpoint Support
Many OIDC providers (including Okta) do not include all user claims in the ID Token by default. According to the OIDC specification, claims like `given_name`, `family_name`, and other profile information should be retrieved from the UserInfo endpoint when not present in the ID Token.

**Current Behavior:**
- The plugin only processes claims from the ID Token
- When `given_name` and `family_name` are not in the ID Token, user provisioning fails or creates users with empty names
- No mechanism exists to fetch additional claims from the UserInfo endpoint

**Example with Okta:**
Okta's ID Token typically contains:
```json
{
  "sub": "00u1a2b3c4d5e6f7g8h9",
  "name": "John Doe",
  "email": "john.doe@example.com",
  "preferred_username": "john.doe@example.com"
}
```

But the plugin expects `given_name` and `family_name`, which are only available via the UserInfo endpoint.

#### Issue 2: Inadequate Username Claim Fallback for Non-Microsoft IdPs
The username extraction logic discriminates based on IdP type:

**For Microsoft Identity Platform:**
- Checks: `preferred_username` → `email` → `sub`

**For Other IdP Types (including Okta, Auth0, etc.):**
- Checks: `upn` → `unique_name` → `sub`
- **Problem:** `upn` and `unique_name` are Microsoft-specific claims that other providers don't send
- **Result:** Falls back to `sub` (a random unique ID like `00u1a2b3c4d5e6f7g8h9`) instead of using the user's email

This creates usernames like `00u1a2b3c4d5e6f7g8h9` instead of `john.doe@example.com`.

### Proposed Solution

#### Enhancement 1: Implement UserInfo Endpoint Support
1. Add a configurable UserInfo endpoint URL field in the application settings
2. Implement a method to fetch user claims from the UserInfo endpoint using the access token
3. Merge UserInfo claims with ID Token claims, giving priority to ID Token claims
4. Add fallback logic to extract `givenName` and `surname` from the `name` claim if `given_name` and `family_name` are not available

#### Enhancement 2: Improve Username Claim Handling
Update the username extraction logic for non-Microsoft IdP types to check standard OIDC claims before falling back to `sub`:

**New fallback order for non-Microsoft IdPs:**
1. `upn` (Microsoft-specific, for backward compatibility)
2. `unique_name` (Microsoft-specific, for backward compatibility)
3. `preferred_username` (standard OIDC claim) ← **NEW**
4. `email` (standard OIDC claim) ← **NEW**
5. `sub` (unique identifier, last resort)

This ensures compatibility with standard OIDC providers while maintaining backward compatibility with existing Microsoft configurations.

### Benefits

1. **Broader OIDC Provider Support:** Works seamlessly with Okta, Auth0, Keycloak, and other standard OIDC providers
2. **Complete User Profiles:** Ensures First Name and Last Name are populated during user provisioning
3. **Meaningful Usernames:** Uses email addresses instead of cryptic unique IDs for usernames
4. **Standards Compliance:** Follows the OIDC specification by utilizing the UserInfo endpoint
5. **Backward Compatibility:** Maintains existing behavior for Microsoft Identity Platform configurations
6. **Flexible Name Mapping:** Supports both structured claims (`given_name`/`family_name`) and unstructured (`name`) with automatic splitting

### Implementation Areas

The following components need to be modified:

1. **Configuration & UI:**
   - `auth/iomadoidc/classes/form/application.php` - Add UserInfo endpoint field
   - `auth/iomadoidc/manageapplication.php` - Handle UserInfo endpoint configuration
   - `auth/iomadoidc/lang/en/auth_iomadoidc.php` - Add language strings

2. **OIDC Client:**
   - `auth/iomadoidc/classes/iomadoidcclient.php` - Implement UserInfo endpoint fetch method

3. **Authentication Flow:**
   - `auth/iomadoidc/classes/loginflow/authcode.php` - Integrate UserInfo call and update username extraction
   - `auth/iomadoidc/classes/loginflow/base.php` - Add claim merging logic and update username extraction

4. **Field Mapping:**
   - `auth/iomadoidc/lib.php` - Add support for `name` claim in field mapping

### Testing Recommendations

1. Test with Okta (IdP Type: Other)
   - Verify First Name and Last Name are populated
   - Verify username uses email instead of `sub`
   
2. Test with Microsoft Entra ID (IdP Type: Microsoft Identity Platform)
   - Verify existing behavior is maintained
   - Ensure no regression in username handling

3. Test with other OIDC providers (Auth0, Keycloak, Google)
   - Verify UserInfo endpoint integration works
   - Verify username fallback logic works correctly

4. Test field mapping configuration
   - Verify `name` claim can be mapped to Moodle fields
   - Verify name splitting logic works when `given_name`/`family_name` are absent

### Related Standards

- [OpenID Connect Core 1.0 - UserInfo Endpoint](https://openid.net/specs/openid-connect-core-1_0.html#UserInfo)
- [OpenID Connect Core 1.0 - Standard Claims](https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims)

### Environment

- **IOMAD Version:** [Your version]
- **Moodle Version:** [Your version]
- **IdP Tested:** Okta
- **IdP Type Configuration:** Other

---

**Note:** This enhancement maintains full backward compatibility with existing Microsoft Identity Platform configurations while extending support to standard OIDC providers.
