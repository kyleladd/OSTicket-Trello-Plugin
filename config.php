<?php
 
require_once(INCLUDE_DIR.'/class.plugin.php');
require_once(INCLUDE_DIR.'/class.forms.php');
require_once(INCLUDE_DIR.'/class.dept.php');
 
 class TrelloConfig extends PluginConfig{
// class TrelloConfig implements PluginCustomConfig{
	function hasCustomConfig(){
		// echo "HAS CUSTOM CONFIG";
		return true;
	}
	function renderCustomConfig(){
		echo "HERE IS THE FORM";
		?>
		<script>
		alert("hi");
		</script>
		<?php
		// return "HERE IS A FORM";
		$form = $this->getForm();
		$form->render();
		// if ($form && $_POST)
  //           $form->isValid();
	}

	function saveCustomConfig(){
		// $form = $this->getForm();
		return $this->commitForm();
		// return $form->isValid();
		// return $this->pre_save();
	}

	function getOptions() {
	  return array(
	 	'trello_api_key' => new TextboxField(array(
		 'id' => 'trello_api_key',
		 'label' => 'Trello API Key',
		 'required'=>true,
		 'hint'=>__('Get your Key: https://trello.com/app-key'),
		 'configuration' => array(
		 	'length' => 0,
		 	'desc' => 'Get your Key: https://trello.com/app-key'
		 	)
		 )),
	 	'trello_api_token' => new TextboxField(array(
		 'id' => 'trello_api_token',
		 'label' => 'Trello API Token',
		 'required'=>true,
		 'hint'=>__('Get your Token: https://trello.com/1/authorize?key=APPLICATIONKEYHERE&scope=read%2Cwrite&name=My+Application&expiration=never&response_type=token'),
		 'configuration' => array(
		 	'length' => 0,
		 	'desc' => 'Get your Token: https://trello.com/1/authorize?key=APPLICATIONKEYHERE&scope=read%2Cwrite&name=My+Application&expiration=never&response_type=token'
		 	),
		 )),
		'osticket_department_id' => new ChoiceField(array(
            'id'=>'osticket_department_id',
            'label'=>__('Department'),
            'required'=>true,
            'hint'=>__('Apply this plugin to this departments\' tickets.'),
            'choices'=>Dept::getDepartments(),
            'configuration'=>array(
                'multiselect' => false,
            )
        )),
  		// // https://developers.trello.com/advanced-reference/
		// use Trello\Client;
		// $client = new Client();
		// $client->authenticate($key, $token, Client::AUTH_URL_CLIENT_ID);
		// // Get All boards for username
		// $boards = $client->api('member')->boards()->all("TRELLOUSERNAME");
		// // Get all lists on board by Board ID
		// $lists = $client->boards()->lists()->all("BOARDID");

        'trello_list_id' => new TextboxField(array(
		 'id' => 'trello_list_id',
		 'label' => 'Trello Creation List ID',
		 'required'=>true,
		 'hint'=>__('When a ticket is created, add card to this list'),
		 'configuration' => array(
		 	'length' => 0,
		 	'desc' => 'When a ticket is created, add card to this list'
		 	),
		 ))
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