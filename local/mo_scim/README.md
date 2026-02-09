# Moodle SCIM Solution by miniOrange

This Moodle SCIM solution by miniOrange enables automated user provisioning, including user creation, updating, and deletion, from your Identity Provider (IDP) directly to your Moodle site. This integration ensures seamless synchronization between your IDP and Moodle, streamlining user management and enhancing security.

## Features

- **Automated User Provisioning**: Automatically create, update, and delete users in Moodle based on changes made in your IDP.
- **Seamless Synchronization**: Ensures that your Moodle site and IDP remain synchronized, reducing manual intervention.
- **Enhanced Security**: Improved control over user management, ensuring that only authorized users are allowed access.
- **Simplified User Management**: Eliminates the need for manual updates in Moodle when user data changes in your IDP.

## Requirements

- **Moodle Instance**: The solution requires an existing Moodle installation.
- **Identity Provider (IDP)**: Supported IDPs include Okta, Azure AD, and others.
- **miniOrange SCIM Plugin**: The SCIM plugin must be installed and configured in your Moodle site.

## Setup Instructions

### Step 1: Install the miniOrange SCIM Plugin

1. Log in to your Moodle admin dashboard.
2. Navigate to the **Plugins** section.
3. Search for the **miniOrange SCIM Plugin**.
4. Click **Install** and activate the plugin.

### Step 2: Configure the SCIM Plugin

1. After activation, go to the plugin settings page.
2. Configure your IDP settings (e.g., Okta, Azure AD) to work with the SCIM plugin.
3. Enable automated user provisioning by selecting the necessary options for user creation, updates, and deletions.
4. Save the settings.

### Step 3: Connect Your IDP to Moodle

1. Go to your IDP settings and configure the SCIM endpoint URL that your Moodle site provides.
2. Map the necessary attributes (such as first name, last name, email) between the IDP and Moodle.
3. Save the configuration.

### Step 4: Test the Integration

1. Ensure the user data from your IDP is syncing correctly with Moodle.
2. Create, update, and delete users in your IDP and verify that these changes are reflected in Moodle.
3. Confirm that new users are created, existing users are updated, and deleted users are removed from Moodle.

## Troubleshooting

- **User Sync Issues**: If users are not syncing correctly, verify that your SCIM endpoint URL is configured properly in your IDP and Moodle.
- **Authentication Errors**: Ensure that the API credentials and mappings are correct between your IDP and Moodle site.

## Benefits

- **Time-saving**: Automates the user management process, saving time for admins and reducing errors.
- **Scalability**: Easily scale the user provisioning process for large organizations with many users.
- **Security**: Ensures only authorized users are provisioned and authenticated in Moodle.

## Support

For support or questions about the plugin, please contact miniOrange support via [support@miniorange.com](mailto:support@miniorange.com).

## License

This solution is licensed under the [MiniOrange License Agreement](https://www.miniorange.com/license).
