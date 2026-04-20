# tool_mobile Upgrade notes

## 5.2

### Changed

- The WS tool_mobile_get_public_config now returns whether MFA and reCAPTCHA are enabled for login/recover password.

  For more information see [MDL-87003](https://tracker.moodle.org/browse/MDL-87003)
- Improve the mobile app subscription page UI and add a subscription cache refresh task and an application-level cache. The cache name used for mobile subscription information has changed, the get_subscription() helper now accepts additional parameters and the undocumented config.php setting $CFG->disablemobileappsubscription has been removed.

  For more information see [MDL-87494](https://tracker.moodle.org/browse/MDL-87494)

## 5.0

### Removed

- Remove chat and survey support from tool_mobile.

  For more information see [MDL-82457](https://tracker.moodle.org/browse/MDL-82457)
