<?php

require_once(INCLUDE_DIR.'/class.plugin.php');
require_once(INCLUDE_DIR.'/class.forms.php');
require_once(INCLUDE_DIR.'/class.dept.php');
define('TRELLO_TABLE',TABLE_PREFIX.'trello');
define('PLUGINS_ROOT',INCLUDE_DIR.'plugins/');
define('TRELLO_PLUGIN_ROOT',PLUGINS_ROOT.basename(__DIR__).'/');
require_once(TRELLO_PLUGIN_ROOT . 'vendor/autoload.php');
use Trello\Client;

// We do not store the choices because they come from Trello's API via javascript
// Therefore, they cannot be matched/validated against config choices
class OptionalValidationChoiceField extends ChoiceField {
    static $widget = 'OptionalValidationChoicesWidget';
}
class OptionalValidationChoicesWidget extends ChoicesWidget{
    function getValue() {
        if (!($value = Widget::getValue()))
            return null;

        if ($value && !is_array($value))
            $value = array($value);
        // Assume multiselect
        $values = array();
        $config = $this->field->getConfiguration();
        if (isset($config['validate_choices']) && $config['validate_choices'] == false) {
            $values = array_flip($value);
        }
        else{
            $choices = $this->field->getChoices();
            if (is_array($value)) {
                foreach($value as $k => $v) {
                    if (isset($choices[$v]))
                        $values[$v] = $choices[$v];
                }
            }
        }
        return $values;
    }
}
 class TrelloConfig extends PluginConfig{
    function hasCustomConfig(){
        return true;
    }
    function renderCustomConfig(){
        $form = $this->getForm();
            $form->isValid();
        ?>
        <script>
        $(function() {
            $('<button id="fetchBoards" style="margin-left:5px;">Fetch Trello Boards</button>').insertAfter($("[name='trello_board_id[]']"));
            $('<button id="fetchLists" style="margin-left:5px;">Fetch Trello Lists</button>').insertAfter($("[name='trello_list_id[]']"));
            $("#fetchBoards").click(function(e){
                e.preventDefault();
                $.getJSON("https://api.trello.com/1/members/me/boards?filter=open&key="+$("[name='trello_api_key']").val()+"&token="+$("[name='trello_api_token']").val(),function(data){
                    var result = "";
                    for(var i in data){
                        var board = data[i];
                        result += "<option value=\"" + board.id + "\" " + ("<?=$form->getField("trello_board_id")->value;?>" === board.id ? "selected=\"selected\"" : "" ) + ">" + board.name + "</option>";
                    }
                    $("[name='trello_board_id[]']").html(result);
                })
                .fail(function(){
                    alert("Failed to get Trello Boards. Be sure to fill out the Trello API Key and Trello API Token first.");
                });
            });
            $("#fetchLists").click(function(e){
                e.preventDefault();
                $.getJSON("https://api.trello.com/1/boards/"+$("[name='trello_board_id[]']").val()+"/lists/?filter=open&key="+$("[name='trello_api_key']").val()+"&token="+$("[name='trello_api_token']").val(),function(data){
                    var result = "";
                    for(var i in data){
                        var list = data[i];
                        result += "<option value=\"" + list.id + "\" " + ("<?=$form->getField("trello_list_id")->value;?>" === list.id ? "selected=\"selected\"" : "" ) + ">" + list.name + "</option>";
                    }
                    $("[name='trello_list_id[]']").html(result);
                })
                .fail(function(){
                    alert("Failed to get Trello Lists. Be sure to fill out the Trello API Key, Trello API Token, and Trello Board first.");
                });
            });
        });
        </script>
        <?php
        $form->render();
    }

    function saveCustomConfig(){
        try{
            global $cfg;
            $client = new Client();
            $config = TrelloPlugin::getConfig();
            // Initial board
            $initial_board = $config->get('trello_board_id');
            $initial_webhook = $config->get('trello_webhook_id');
            $form = $this->getForm();
            $form->getField('trello_webhook_id')->value;
            $create_new_webhook = true;
            if(!empty($form->getField('trello_webhook_id')->value) && $form->getField('trello_webhook_id')->value !== $initial_webhook){
                // Webhook ID was manually entered and not generated
                // Check to see if entered webhook id is an already used webhook for this endpoint - verification 
                // $client->authenticate($form->getField('trello_api_key')->value, $form->getField('trello_api_token')->value, Client::AUTH_URL_CLIENT_ID);
                // $client->webhooks()->get()
                $create_new_webhook = false;
            }

            if($this->commitForm()){
                $config = TrelloPlugin::getConfig();
                $saved_board = $config->get('trello_board_id');
                // Create webhook for new board and remove webhook from old board if there is one
                if($saved_board !== $initial_board || empty($config->get('trello_webhook_id'))){
                    $client->authenticate($config->get('trello_api_key'), $config->get('trello_api_token'), Client::AUTH_URL_CLIENT_ID);
                    if(!empty($initial_webhook)){
                        // Remove existing webhook
                        try{
                            $client->webhooks()->remove($config->get('trello_webhook_id'));
                        }
                        catch(Exception $e){
                            error_log("Unable to delete Trello Webhook. " . $e->getMessage());
                            echo "Unable to delete Trello Webhook.";
                            var_dump($e);
                        }
                    }
                    if($create_new_webhook){
                        $trello_webhook_create = $client->webhooks()->create(array("idModel"=>$saved_board,"callbackURL"=>$cfg->getBaseUrl() . "/api/trello","description"=>"OSTicket Plugin"));
                        if(TrelloConfig::update('trello_webhook_id',$trello_webhook_create['id']) === false){
                            echo "Failed to save created webhook to database";
                            return false;
                        }
                    }
                }

            }
        }
        catch(Exception $e){
            error_log("Error authenticating to Trello. " . $e->getMessage());
            var_dump($e);
            return false;
        }
        return true;
    }

    function getOptions() {
      return array(
        'trello_api_key' => new TextboxField(array(
         'id' => 'trello_api_key',
         'label' => 'Trello API Key',
         'required'=>true,
         'hint'=>__('Get your Key: https://trello.com/app-key'),
         'configuration' => array(
            'length' => 0
            )
         )),
        'trello_api_token' => new TextboxField(array(
         'id' => 'trello_api_token',
         'label' => 'Trello API Token',
         'required'=>true,
         'hint'=>__('Get your Token: https://trello.com/1/authorize?key=APPLICATIONKEYHERE&scope=read%2Cwrite&name=My+Application&expiration=never&response_type=token'),
         'configuration' => array(
            'length' => 0
            ),
         )),
        'trello_board_id' => new OptionalValidationChoiceField(array(
         'id' => 'trello_board_id',
         'label' => 'Trello Board ID',
         'required'=>true,
         'hint'=>__('Get your Token: https://trello.com/1/authorize?key=APPLICATIONKEYHERE&scope=read%2Cwrite&name=My+Application&expiration=never&response_type=token'),
         'configuration' => array(
            'multiselect' => false,
            'validate_choices' => false
            ),
         )),
        'trello_list_id' => new OptionalValidationChoiceField(array(
         'id' => 'trello_list_id',
         'label' => 'Trello Creation List ID',
         'required'=>true,
         'hint'=>__('When a ticket is created, add card to this list in Trello'),
         'configuration' => array(
            'multiselect' => false,
            'validate_choices' => false
            ),
         )),
        'osticket_department_id' => new ChoiceField(array(
            'id'=>'osticket_department_id',
            'label'=>__('Department'),
            'required'=>true,
            'hint'=>__('When a ticket is created for this department, create the corresponding card in Trello.'),
            'choices'=>Dept::getDepartments(),
            'configuration'=>array(
                'multiselect' => false
            )
        )),
        'trello_user_email' => new TextboxField(array(
         'id' => 'trello_user_email',
         'label' => 'OSTicket User Email for Trello',
         'required'=>true,
         'hint'=>__('For Ticket creation in Trello - Just needs to be an email address that has already created at least one ticket in OSTicket. This email address will create all Trello-created tickets.'),
         'configuration' => array(
            'length' => 0
            ),
         )),
        'trello_webhook_id' => new TextboxField(array(
         'id' => 'trello_webhook_id',
         'label' => 'Current Trello Webhook',
         'required'=>false,
         'hint'=>__('Generated and used for webhook removal. However, if you\'ve previously created a webhook for this plugin for this url, you can manually enter it. To see your current webhooks: https://developers.trello.com/sandbox -> Get Webhooks'),
         'configuration' => array(
            'length' => 0
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
