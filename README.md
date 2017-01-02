# OSTicket Trello Plugin
The goal of this plugin is to be able to sync your OSTicket tickets with a Trello Board.

## Requirements
- OSTicket v1.10 - slightly modified [https://github.com/kyleladd/osTicket/commits/OSTicketTrello](https://github.com/kyleladd/osTicket/commits/OSTicketTrello)
	- Support coming soon for unmodified OSTicket v1.9.14, v1.9.15 installs
- Guzzle's Requirements: V6 (see composer.json) [http://docs.guzzlephp.org/en/stable/overview.html](http://docs.guzzlephp.org/en/stable/overview.html)
  - PHP 5.5

## Installation
**Note**: For now, this plugin depends on OSTicket having an api which has not been merged in yet (PR [#2947](https://github.com/osTicket/osTicket/pull/2947)) as well as a PUT endpoint that I created. To see the modifications: [https://github.com/kyleladd/osTicket/commits/OSTicketTrello](https://github.com/kyleladd/osTicket/commits/OSTicketTrello)

- ```composer install```
- Copy Repo to OSTicket's plugin directory (include/plugins/)
- Add New Plugin: [OSTICKET_URL]/scp/plugins.php
- Select the OSTicket Trello Plugin
- Enable the plugin within OSTicket
- Configure the plugin via the plugin's form. *Note: This must be completed after the plugin is enabled because Trello verifies the url is a 200 status code in order for the webhook to be created. Enabling the plugin first allows the plugin/OSTicket to answer the request with a 200 status code when configuring the plugin. When enabled, the plugin hooks into the url dispatcher and creates the api endpoints for Trello within OSTicket. "The provided callbackURL must be a valid URL during the creation of the webhook. We run a quick HTTP HEAD request on the URL, and if a 200 status code is not returned in the response, then the webhook will not be created." - https://developers.trello.com/apis/webhooks*
- The webhook field will be automatically filled when the webhook is successfully created.

## Additional Configuration

### Updating ticket status
 Updating the ticket status is done by matching the name of the list (in Trello) to the name of the status (in OSTicket)

#### Adding ticket statuses in OSTicket
Admin Panel->Manage->Lists->Ticket Statuses->Add New Item

- **Value**: Match the name of the list in Trello
- **Item Properties**: Set the state of the ticket when it is (this status - OSTicket)/(in this list - Trello)

## Functionality
### Events triggered by Trello
- Creating a card in Trello creates a ticket in OSTicket
- Moving a card between lists in Trello updates the ticket status in OSTicket
- Updating a card's description in Trello updates the ticket's description 

### Events triggered by OSTicket
- Creating a ticket in OSTicket creates a card in Trello

## Ticket Creation - Fields
### Action initiated in OSTicket -> Trello
| Action  | Create |
| ------------- | ------------- |
| Title | X |
| Description | X |
| Status | X |
| Due Date |  |
| Attachment |  |

### Action initiated in Trello -> OSTicket
| Action  | Create |
| ------------- | ------------- |
| Title | X |
| Description | X |
| Status | X |

## Syncing
### Action initiated in OSTicket
| Action  | Create | Update  | Delete  |
| ------------- | ------------- | ------------- | ------------- |
| Ticket | X | NA |  |
| Title | NA |  |  |
| Description | NA | X |  |
| Status | NA | X | NA |
| Due Date |  |  |  |
| Public Comment | X |  |  |
| Internal Comment |  |  |  |
| Attachment |  |  |  |
| Tasks |  |  |  |

### Action initiated in Trello
| Action  | Create | Update  | Delete  |
| ------------- | ------------- | ------------- | ------------- |
| Ticket | X | NA |  |
| Title | NA |  | NA |
| Description | NA | X | NA |
| Status | NA | X | NA |
| Due Date | NA |  |  |
| Public Comment | X |  |  |
| Internal Comment |  |  |  |
| Attachment |  |  |  |
| Tasks |  |  |  |
