<?php
 
require_once(INCLUDE_DIR.'/class.plugin.php');
require_once(INCLUDE_DIR.'/class.forms.php');
 
class TrelloConfig extends PluginConfig{
 function getOptions() {
	 return array(
	 	'trello_api_key' => new TextboxField(array(
		 'id' => 'trello_api_key',
		 'label' => 'Trello API Key',
		 'configuration' => array(
		 	'length' => 0,
		 'desc' => 'Get your Key: https://trello.com/app-key')
		 )),
	 	'trello_api_token' => new TextboxField(array(
		 'id' => 'trello_api_token',
		 'label' => 'Trello API Token',
		 'configuration' => array(
		 	'length' => 0,
		 'desc' => 'Get your Token: https://trello.com/1/authorize?key=APPLICATIONKEYHERE&scope=read%2Cwrite&name=My+Application&expiration=never&response_type=token')
		 )),
		'create_in_trello_list' => new TextboxField(array(
		 'id' => 'create_in_trello_list',
		 'label' => 'Create Trello Card in List',
		 'configuration' => array(
		 	'length' => 0,
		 'desc' => 'Where to create a Trello card - in which list Ex. To Do list id')
		 )),
	 );
 }
 
 function pre_save(&$config, &$errors) {
	global $msg;
 
	if (!$errors)
	  $msg = 'Configuration updated successfully';
	 
	return true;
 }
}
?>