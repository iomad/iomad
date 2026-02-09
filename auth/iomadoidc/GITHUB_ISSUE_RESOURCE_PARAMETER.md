# Issue: Resource Parameter Always Added to SSO Redirect URL for Non-Microsoft IdPs

## Issue Description

When using the `auth_iomadoidc` plugin with IdP type set to "Other" and an intentionally empty resource configuration, two related issues occur:

1. **Backend Issue**: The SSO redirect URL incorrectly includes `resource=https%3A%2F%2Fgraph.microsoft.com` parameter
2. **Form Validation Issue**: The application form incorrectly requires a resource value for "Other" IdP types

These issues cause authentication failures with non-Microsoft identity providers that do not expect or support this Microsoft-specific parameter.

### Expected Behavior
- For "Other" IdP types with empty resource configuration, no resource parameter should be added to the authentication request URL
- The resource parameter should only be included when explicitly configured or when using Microsoft Entra ID (v1.0) which requires it
- The application form should allow empty resource values for "Other" IdP types

### Actual Behavior
- **Backend**: The resource parameter is always added with a hardcoded value of `https://graph.microsoft.com`
- **Form**: The validation rejects empty resource values with error: "Resource cannot be empty when using Microsoft Entra ID (v1.0) or other types of IdP."
- This occurs even when the resource configuration is intentionally left empty
- Non-Microsoft IdPs receive an unwanted Microsoft Graph resource parameter

---

## Root Cause

### Issue 1: Backend Logic (iomadoidcclient.php)

The issue is located in `auth/iomadoidc/classes/iomadoidcclient.php` in the `setcreds()` method (lines 91-110).

#### Problem Code

```php
public function setcreds($id, $secret, $redirecturi, $tokenresource = '', $scope = '') {
    $this->clientid = $id;
    $this->clientsecret = $secret;
    $this->redirecturi = $redirecturi;
    if (!empty($tokenresource)) {
        $this->tokenresource = $tokenresource;
    } else {
        // PROBLEM: Always defaults to Microsoft Graph, regardless of IdP type
        if (auth_iomadoidc_is_local_365_installed()) {
            if (\local_o365\rest\o365api::use_chinese_api() === true) {
                $this->tokenresource = 'https://microsoftgraph.chinacloudapi.cn';
            } else {
                $this->tokenresource = 'https://graph.microsoft.com';
            }
        } else {
            $this->tokenresource = 'https://graph.microsoft.com';  // ← ALWAYS sets this
        }
    }
    $this->scope = (!empty($scope)) ? $scope : 'openid profile email';
}
```

#### Logic Flow

1. When resource config is empty, `$tokenresource` parameter is `null` or empty string
2. The `else` block (lines 97-106) executes
3. **Regardless of IdP type**, the code hardcodes `$this->tokenresource = 'https://graph.microsoft.com'`
4. Later, in `getauthrequestparams()` method (line 206), this value is unconditionally added to the auth request for non-Microsoft Identity Platform IdPs
5. The resource parameter appears in the SSO redirect URL even though it shouldn't

### Issue 2: Form Validation (application.php)

The issue is located in `auth/iomadoidc/classes/form/application.php` in the `validation()` method (lines 278-283).

#### Problem Code

```php
// Validate iomadoidcresource.
if (in_array($data['idptype'], [AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_ENTRA_ID, AUTH_IOMADOIDC_IDP_TYPE_OTHER])) {
    if (empty(trim($data['iomadoidcresource']))) {
        $errors['iomadoidcresource'] = get_string('error_empty_iomadoidcresource', 'auth_iomadoidc');
    }
}
```

#### Why This Is Wrong

- The validation incorrectly includes `AUTH_IOMADOIDC_IDP_TYPE_OTHER` in the required check
- Resource parameter should only be mandatory for Microsoft Entra ID (v1.0)
- For "Other" IdP types, the resource parameter should be optional
- The error message is misleading: "Resource cannot be empty when using Microsoft Entra ID (v1.0) or other types of IdP."

---

## Solution

The fix involves changes to three files:

### 1. Modify `setcreds()` Method in iomadoidcclient.php (Lines 91-115)

Only set Microsoft Graph as default resource for Microsoft Entra ID (v1.0) IdP type:

```php
public function setcreds($id, $secret, $redirecturi, $tokenresource = '', $scope = '') {
    $this->clientid = $id;
    $this->clientsecret = $secret;
    $this->redirecturi = $redirecturi;
    if (!empty($tokenresource)) {
        $this->tokenresource = $tokenresource;
    } else {
        // Check IdP type before setting default resource
        $idptype = get_config('auth_iomadoidc', 'idptype' . $this->postfix);
        if ($idptype == AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_ENTRA_ID) {
            // Microsoft Entra ID (v1.0) requires a resource parameter
            if (auth_iomadoidc_is_local_365_installed()) {
                if (\local_o365\rest\o365api::use_chinese_api() === true) {
                    $this->tokenresource = 'https://microsoftgraph.chinacloudapi.cn';
                } else {
                    $this->tokenresource = 'https://graph.microsoft.com';
                }
            } else {
                $this->tokenresource = 'https://graph.microsoft.com';
            }
        } else {
            // For Microsoft Identity Platform (v2.0) and Other IdP types, leave empty
            $this->tokenresource = '';
        }
    }
    $this->scope = (!empty($scope)) ? $scope : 'openid profile email';
}
```

### 2. Modify `getauthrequestparams()` Method in iomadoidcclient.php (Lines 210-214)

Only add resource parameter if it has a value:

```php
if (get_config('auth_iomadoidc', 'idptype' . $this->postfix) != AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_IDENTITY_PLATFORM) {
    // Only add resource parameter if it has a value
    if (!empty($this->tokenresource)) {
        $params['resource'] = $this->tokenresource;
    }
}
```

### 3. Modify Token Request Method in iomadoidcclient.php (Lines 341-345)

Apply the same check for consistency:

```php
if (get_config('auth_iomadoidc', 'idptype' . $this->postfix) != AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_IDENTITY_PLATFORM) {
    // Only add resource parameter if it has a value
    if (!empty($this->tokenresource)) {
        $params['resource'] = $this->tokenresource;
    }
}
```

### 4. Fix Form Validation in application.php (Lines 278-283)

Only require resource for Microsoft Entra ID (v1.0):

```php
// Validate iomadoidcresource.
if ($data['idptype'] == AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_ENTRA_ID) {
    if (empty(trim($data['iomadoidcresource']))) {
        $errors['iomadoidcresource'] = get_string('error_empty_iomadoidcresource', 'auth_iomadoidc');
    }
}
```

### 5. Update Error Message in lang/en/auth_iomadoidc.php (Line 264)

Correct the error message to reflect the actual requirement:

```php
$string['error_empty_iomadoidcresource'] = 'Resource cannot be empty when using Microsoft Entra ID (v1.0).';
```

---

## Affected Files

1. **auth/iomadoidc/classes/iomadoidcclient.php**
   - Modified `setcreds()` method (lines 91-115)
   - Modified `getauthrequestparams()` method (lines 210-214)
   - Modified token request method (lines 341-345)

2. **auth/iomadoidc/classes/form/application.php**
   - Modified `validation()` method (lines 278-283)

3. **auth/iomadoidc/lang/en/auth_iomadoidc.php**
   - Updated error message string (line 264)

---

## Impact

### Before Fix
- ❌ Non-Microsoft IdPs fail authentication due to unexpected resource parameter
- ❌ Cannot use "Other" IdP type with empty resource configuration
- ❌ Form validation incorrectly rejects empty resource for "Other" IdP types
- ❌ Hardcoded Microsoft Graph URL sent to all IdP types
- ❌ Misleading error message confuses administrators

### After Fix
- ✅ Non-Microsoft IdPs work correctly with empty resource configuration
- ✅ Form allows empty resource for "Other" IdP types
- ✅ Form correctly requires resource only for Microsoft Entra ID (v1.0)
- ✅ Clear, accurate error message
- ✅ Microsoft Entra ID (v1.0) still gets required resource parameter
- ✅ Microsoft Identity Platform (v2.0) continues to work without resource parameter
- ✅ Custom resource values are respected when configured
- ✅ Backward compatibility maintained for existing Microsoft configurations

---

## Related Constants

From `auth/iomadoidc/lib.php`:
```php
CONST AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_ENTRA_ID = 1;
CONST AUTH_IOMADOIDC_IDP_TYPE_MICROSOFT_IDENTITY_PLATFORM = 2;
CONST AUTH_IOMADOIDC_IDP_TYPE_OTHER = 3;
```

---

## Backward Compatibility

This fix maintains full backward compatibility:
- Existing Microsoft Entra ID (v1.0) configurations continue to work
- Microsoft Identity Platform (v2.0) behavior unchanged
- Only affects "Other" IdP types where resource was incorrectly hardcoded and validated
- No configuration changes required for existing installations
- Form validation now correctly matches the backend behavior
