<?php
 
require_once(INCLUDE_DIR.'/class.plugin.php');
require_once(INCLUDE_DIR.'/class.forms.php');
require_once(INCLUDE_DIR.'/class.dept.php');
 
 class TrelloConfig extends PluginConfig{
	function hasCustomConfig(){
		return true;
	}
	function renderCustomConfig(){
		?>
		<br>
		<button id="fetchBoards">Fetch Trello Boards</button><br>
		<button id="fetchLists">Fetch Trello Lists</button><br>
		<script>
		$(function() {
			$("#fetchBoards").click(function(e){
				e.preventDefault();
				$.getJSON("https://api.trello.com/1/members/me/boards?key="+$("[name='trello_api_key']").val()+"&token="+$("[name='trello_api_token']").val(),function(data){
					console.log("BOARDS",data);
				})
				.fail(function(){
					alert("Failed to get boards");
				});
			});
			$("#fetchLists").click(function(e){
				e.preventDefault();
				$.getJSON("https://api.trello.com/1/boards/"+$("[name='trello_board_id']").val()+"/lists?key="+$("[name='trello_api_key']").val()+"&token="+$("[name='trello_api_token']").val(),function(data){
					console.log("LISTS",data);
				})
				.fail(function(){
					alert("Failed to get Lists");
				});
			});
		});
		</script>
		<?php
		$form = $this->getForm();
		$form->render();
	}

	function saveCustomConfig(){
		return $this->commitForm();
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
	 	'trello_board_id' => new TextboxField(array(
		 'id' => 'trello_board_id',
		 'label' => 'Trello Board ID',
		 'required'=>true,
		 'hint'=>__('Get your Token: https://trello.com/1/authorize?key=APPLICATIONKEYHERE&scope=read%2Cwrite&name=My+Application&expiration=never&response_type=token'),
		 'configuration' => array(
		 	'length' => 0,
		 	'desc' => 'Get your Token: https://trello.com/1/authorize?key=APPLICATIONKEYHERE&scope=read%2Cwrite&name=My+Application&expiration=never&response_type=token'
		 	),
		 )),
	 	'trello_list_id' => new TextboxField(array(
		 'id' => 'trello_list_id',
		 'label' => 'Trello Creation List ID',
		 'required'=>true,
		 'hint'=>__('When a ticket is created, add card to this list'),
		 'configuration' => array(
		 	'length' => 0,
		 	'desc' => 'When a ticket is created, add card to this list'
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