# OSTicket Trello Plugin
## Installation
**Note**: For now, this plugin depends on OSTicket having an api which has not been merged in yet (PR [#2947](https://github.com/osTicket/osTicket/pull/2947)) as well as a PUT endpoint that I created. To see the modifications: [https://github.com/kyleladd/osTicket/commits/OSTicketTrello](https://github.com/kyleladd/osTicket/commits/OSTicketTrello)

- ```composer install```
- Copy Repo to OSTicket's plugin directory (include/plugins/)
- Add New Plugin: [OSTICKET_URL]/scp/plugins.php
- Enable Plugin
- Configure Plugin via the plugin's form. *Note: This must be completed after the plugin is enabled because Trello verifies the url is a 200 status code in order for the webhook to be created. The plugin can't respond to or create that url until the plugin is enabled.*
- The webhook field will be automatically filled when the webhook is successfully created.

## Requirements
- OSTicket v1.10 [https://github.com/kyleladd/osTicket/commits/OSTicketTrello](https://github.com/kyleladd/osTicket/commits/OSTicketTrello)
- Guzzle's Requirements: V6 (see composer.json) [http://docs.guzzlephp.org/en/stable/overview.html](http://docs.guzzlephp.org/en/stable/overview.html)
  - PHP 5.5
  
Support coming soon for Unmodified OSTicket v1.9.14, v1.9.15
