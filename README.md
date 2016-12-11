# OSTicket Trello Plugin
## Installation
**Note**: This plugin depends on OSTicket having an api which has not been merged in yet (PR [#2947](https://github.com/osTicket/osTicket/pull/2947)) as well as a PUT endpoint that I created. To see the modifications: [https://github.com/kyleladd/osTicket/commits/OSTicketTrello](https://github.com/kyleladd/osTicket/commits/OSTicketTrello)

- ```composer install```
- Copy Repo to OSTicket's plugin directory (include/plugins/)
- Add New Plugin: [OSTICKET_URL]/scp/plugins.php
- Enable Plugin
- Configure Plugin