WordPress plugin that integrates Contact Form 7 with Yandex Messenger (Yandex Chat). This plugin will send form submissions to a specified Yandex Messenger chat when a CF7 form is successfully submitted.

# Plugin Features

* Adds a settings page under "Contact Form 7" → "Yandex Messenger"

* Stores Bot API Key (OAuth token) and Chat ID

* Sends formatted form data to Yandex Messenger API after CF7 mail is sent

* Uses WordPress HTTP API for secure requests

* Logs errors to WordPress debug log

# Installation Instructions

1. Upload the cf7-to-yandex-messenger folder to /wp-content/plugins/.

2. Activate the plugin:
    Go to Plugins → Installed Plugins and activate "CF7 to Yandex Messenger".
    Note: Contact Form 7 must be active.

3. Configure settings:

   Navigate to Contact Form 7 → Yandex Messenger.

   Enter your Bot API Key (OAuth token) and Chat ID.

   Save changes.

4. Test:
    Submit a test form on your site. If configured correctly, a formatted message will appear in your Yandex Messenger chat.

# Obtaining Credentials

## Bot API Key (OAuth Token)

* Create a Yandex application at [Yandex OAuth](https://oauth.yandex.ru/).

* Enable Yandex.Messenger API permissions.

* Generate an OAuth token with the messenger.bot scope.

## Chat ID

The chat ID format is 0/0/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.

To find it:

* Send a message to your bot in Yandex Messenger.

* Call the /chats API endpoint with your token to list available chats and their IDs.
