<?php
 
require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.app.php');
 
require_once('config.php');
 
define('TRELLO_TABLE',TABLE_PREFIX.'trello');
define('PLUGINS_ROOT',INCLUDE_DIR.'plugins/');
define('TRELLO_PLUGIN_ROOT',PLUGINS_ROOT.'TrelloPlugin/');

require_once(TRELLO_PLUGIN_ROOT . 'class.trello_install.php');

require_once(TRELLO_PLUGIN_ROOT . 'vendor/autoload.php');
use Trello\Client; 

class TrelloPlugin extends Plugin {
 
    var $config_class = 'TrelloConfig';
 
    function bootstrap() {
        if ($this->firstRun()) {
            $this->configureFirstRun();
        }
 
        $config = $this->getConfig();
        
        Signal::connect('model.created', array($this, 'onTicketCreated'), 'Ticket');
    }
 
    /**
     * Checks if this is the first run of our plugin.
     * @return boolean
     */
    function firstRun() {
        $sql='SHOW TABLES LIKE \''.TRELLO_TABLE.'\'';
        $res=db_query($sql);
        return  (db_num_rows($res)==0);
    }
 
    /**
     * Necessary functionality to configure first run of the application
     */
    function configureFirstRun() {
       if(!$this->createDBTables())
       {
           echo "First run configuration error.  "
            . "Unable to create database tables!";
       }
    }
 
    /**
     * Kicks off database installation scripts
     * @return boolean
     */
    function createDBTables() {
       $installer = new TrelloInstaller();
       return $installer->install();
 
    }
 
    /**
     * Uninstall hook.
     * @param type $errors
     * @return boolean
     */
    function pre_uninstall(&$errors) {
       $installer = new TrelloInstaller();
       return $installer->remove();
    }

    function onTicketCreated($ticket){      
        try{
            // TRELLO CHANGES ON TICKET CREATION
            // If it is the web department ticket, send to Trello board
            // for now let's just pretend there is an if block here checking that
            $client = new Client();
            $config = $this->getConfig();
            $client->authenticate($config->get('trello_api_key'), $config->get('trello_api_token'), Client::AUTH_URL_CLIENT_ID);
            // // POST to Trello
            $newcard = array("idList"=> $config->get('create_in_trello_list'),"name"=>$ticket->getNumber() . " - " . $ticket->getSubject() ,"desc"=>$ticket->getLastMessage()->getBody());
            $client->cards()->create($newcard);
        }
        catch(Exception $e){
            error_log('Error posting to Trello. '. $e->getMessage());
        }
    }
 
}