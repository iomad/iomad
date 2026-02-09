# SCIM 2.0 compliant user provisioning plugin for Moodle

This plugin makes use of 3rd party AltoRouter class for routing the SCIM endpoints (https://github.com/dannyvankooten/AltoRouter)

## How to use

### Add Client identifier:

1. Go to *Site Administration > Server > OAuth provider settings*

2. Click *Add new client*

3. Fill in the form. Your Client Identifier and Client Secret (which will be given later) will be used for you to authenticate. The Redirect URL is not used for this plugin as we using `client_credentials` as the authorization grant type.

* Client identifier: must be same as set in the Organisation short name for the Portal organisation.
e.g. If we are creating a Client secret key for Organisation `MSI Reproductive Choices`, the Client identifier will be `portal-61`
* Redirect URL: is not in use and can be set to N/A
* Grant Types: should be set to `client_credentials`
* Scope: should be set to `SCIMv2`
* User ID: should be set to `0`

### The URL for Azure AD user provisioning is:
`https://{siteurl}/local/user_provisioning/scim/rest.php/v2`

The client will have to create two Custom extention attribues for `Team` and `Auth`

### Field mapping of Moodle to Azure AD are as follows:

* idnumber : id (Azure AD - unique identifier)
* Username : userName
* Firstname : name.givenName
* Lastname : name.familyName
* Email address : emails.value (Type = Work AND primary = True)
* Alternate name : displayName
* Preferred language : preferredLanguage
* City : addresses.locality (Type = Work AND primary = True)
* Country : addresses.country (Type = Work AND primary = True)
* Position : title
* Suspended : active
* Manager : urn:ietf:params:scim:schemas:extension:enterprise:2.0:User manager.value
* Department : urn:ietf:params:scim:schemas:extension:enterprise:2.0:User department
* Authentication : urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User auth
* Team : urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User team

Schemas endpoint provides a fair representation of Azure AD fields.

### Generate a Long-lived bearer token:


### Valid end points for this API are:

#### `/token` - To validate / generate a token using the Client identifier and Client secret.

curl https://{siteurl}/local/user_provisioning/scim/rest.php/v2/token -d 'grant_type=client_credentials&client_id=<CLIENT_ID>&client_secret=<CLIENT_SECRET>'

Where CLIENT_ID is the Client identifier and the CLIENT_SECRET is the key generated when adding a new client as part of `Add Client identifier`

#### `/ServiceProviderConfig` - To view the SCIM service provider config.

curl --location --request GET 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/ServiceProviderConfig' \
--header 'Authorization: Bearer {BEARER_TOKEN}'

#### /Schemas` - To view the SCIM schemas that are used. Append following to view the individual User, Enterprise and Custom extention schema:

* urn:ietf:params:scim:schemas:core:2.0:User
* urn:ietf:params:scim:schemas:extension:enterprise:2.0:User
* urn:ietf:params:scim:schemas:extension:CustomExtensionName:2.0:User

curl --location --request GET 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/Schemas' \
--header 'Authorization: Bearer {BEARER_TOKEN}'

#### `/Users?filters={SEARCH_CRITERIA}` - GET request to fetch a given user's data

curl --location --request POST 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/Users?filter={SEARCH_CRITERIA}' \
--header 'Authorization: Bearer {BEARER_TOKEN}'

Supported SEARCH_CRITERIA fields are:
userName, name.familyName, name.givenName and emails.

Supported SCIM filter types are:
eq (equal) Ee.g. filter=userName+eq+"joeb@job.com"
neq (Not Equal) Ee.g. filter=userName+neq+"joeb@job.com"
co (Contains) e.g. filter=userName+co+"joeb@job.com"
sw (Starts With) e.g. filter=userName+sw+"joeb@job.com"
ew (Ends With) e.g. filter=userName+ew+"joeb@job.com"

#### `/Users/{USER_ID}` - GET request to fetch a given user's data

curl --location --request POST 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/Users' \
--header 'Authorization: Bearer {BEARER_TOKEN}'

#### `/Users` - POST request to create / provision a new user.

curl --location --request POST 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/Users' \
--header 'Authorization: Bearer {BEARER_TOKEN}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "schemas": ["urn:ietf:params:scim:schemas:core:2.0:User", "urn:ietf:params:scim:schemas:extension:enterprise:2.0:User"],
    "externalId": "XXXXXXXX",
    "userName": "catalyst.admin@catalyst-eu.net",
    "active": true,
    "addresses": [{
        "primary": true,
        "type": "work",
        "country": "United Kingdom",
        "locality": "Brighton"
    }],
    "displayName": "Catalyst Admin",
    "emails": [{
        "primary": true,
        "type": "work",
        "value": "catalyst.admin@catalyst-eu.net"
    }],
    "name": {
        "familyName": "Catalyst",
        "givenName": "Admin"
    },
    "title": "Site Administrator",
    "urn:ietf:params:scim:schemas:extension:enterprise:2.0:User": {
        "department": "Internet Technologist",
        "manager": {
            "value": ""
        }
    },
    "urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User": {
        "auth": "saml2",
        "team": "IT"
    }
}'

#### `/Users` - PATCH request to update given user's data.

curl --location --request PATCH 'https://{SITE_URL}/local/user_provisioning/scim/rest.php/v2/Users' \
--header 'Authorization: Bearer {BEARER_TOKEN}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "schemas": [
        "urn:ietf:params:scim:api:messages:2.0:PatchOp"
    ],
    "Operations": [
        {
            "op": "Add",
            "path": "displayName",
            "value": "Catalyst Admin"
        },
        {
            "op": "Add",
            "path": "emails[type eq \"work\"].value",
            "value": "bitsprintuk+catalyst@gmail.com"
        },
        {
            "op": "Replace",
            "path": "name.givenName",
            "value": "Admin"
        },
        {
            "op": "Replace",
            "path": "name.familyName",
            "value": "Catalyst"
        }
    ]
}'
