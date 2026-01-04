# phpBB Participants List

## Description
With this extension, the creator of a new topic can add a participant list after their first post.
Users with the “Can see participant list” permission can view the participant list. Only registered users can add a list to the topic and add themselves to the list. The list is activated when the first post is created using the “Add participant list” option.

This function can be activated in forums with the forum right “Can create participants list”.
Moderators with the permissions “Can edit posts” and “Can permanently delete posts” can change or delete entries in the list.

If the first post in the topic is deleted, the associated list is also deleted and all entries in the participant list are removed from the database.

## Screenshots
- [Participants List](https://raw.githubusercontent.com/IMC-GER/images/main/screenshots/participantslist/list_reg_user.png)
- [Registrations Form](https://raw.githubusercontent.com/IMC-GER/images/main/screenshots/participantslist/registration_form.png)
- [Moderators View](https://raw.githubusercontent.com/IMC-GER/images/main/screenshots/participantslist/list_moderators.png)
- [Forum permissions](https://raw.githubusercontent.com/IMC-GER/images/main/screenshots/participantslist/forum_permissions.png)

## Requirements
- phpBB >= 3.3.2 and < 4.0.0-dev
- php >= 8.0.0 and <= 8.5.x

## Installation
Copy the extension to `phpBB3/ext/imcger/participantslist`.
Go to “ACP” > “Customise” > “Manage extensions” and enable the “Show Hidden Password” extension.
After enable zhe extension go to “ACP” > “Permissions” > “Forum permissions”. Select a Forum and add the permission “Add participant list” for registered user.

## Update
- Navigate in the ACP to `Customise -> Manage extensions`.
- Click the `Disable` link for “Participants List”.
- Delete the `participantslist` folder from `phpBB3/ext/imcger/`.
- Copy the extension to `phpBB3/ext/imcger/participantslist`.
- Go to “ACP” > “Customise” > “Manage extensions” and enable the “Participants List” extension.

## Uninstallation
- Navigate in the ACP to `Customise -> Manage extensions`.
- Click the `Disable` link for “Participants List”.
- To permanently uninstall, click `Delete Data`, then delete the `participantslist` folder from `phpBB3/ext/imcger/`.

## Changelog

### v1.1.0-b1 (04-01-2025)
- Fixed: Incorrect colours in list while using css-class `zebra-list`.
- Fixed: Sign counter don't count when paste text.
- Added: Button properties can be changed in css-file.
- Added: Support for emojis in comment.
- Added: User Permission “Can see participant list”.
- Changed: Improved permission check.
- Changed: Improved db-query for the breadcrumbs menue.

### v1.0.0-rc2 (16-12-2025)
- Fixed: Incorrect permissions have been granted for the role with full access.
- Fixed: Sign counter leaves position when changing the size of the textarea
- Fixed: Footer display incorrect

### v1.0.0-rc (13-12-2025)

## License
[GPLv2](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)
