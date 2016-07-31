<?php
 
/**
 * Integrate Trello into OSTicket
 *
 * @author
 */
 
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__file__).'/include');
return array(
 'id' => 'kyleladd:trello', # notrans
 'version' => '0.1',
 'name' => 'Trello Plugin',
 'author' => 'Kyle Ladd',
 'description' => 'Trello Plugin to have tickets be in sync with a Trello Board',
 'url' => 'http://kyleladd.us',
 'plugin' => 'trello.php:TrelloPlugin'
);
 
?>