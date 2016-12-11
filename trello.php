<?php

require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.app.php');

require_once('config.php');

require_once(TRELLO_PLUGIN_ROOT . 'class.trello_install.php');
require_once(TRELLO_PLUGIN_ROOT . 'api.trello.php');

require_once(TRELLO_PLUGIN_ROOT . 'vendor/autoload.php');
use Trello\Client;

class TrelloPlugin extends Plugin {

    var $config_class = 'TrelloConfig';

    function bootstrap() {
        if ($this->firstRun()) {
            $this->configureFirstRun();
        }

        $config = $this->getConfig();
        Signal::connect ( 'api', array (
                'TrelloPlugin',
                'callbackDispatch' 
        ) );
        Signal::connect('ticket.created', array($this, 'onTicketCreated'), 'Ticket');
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
            $config = $this->getConfig();
            // If the ticket was made for the department with a hook into Trello
            if($config->get('osticket_department_id')==$ticket->dept->id){
                // TRELLO CHANGES ON TICKET CREATION
                $client = new Client();
                
                $client->authenticate($config->get('trello_api_key'), $config->get('trello_api_token'), Client::AUTH_URL_CLIENT_ID);
                // // POST to Trello
                $newcard = array("idList"=> $config->get('trello_list_id'),"name"=>TrelloPlugin::createTrelloTitle($ticket) ,"desc"=>$ticket->getLastMessage()->getBody());
                $client->cards()->create($newcard);
            }
        }
        catch(Exception $e){
            error_log("Error posting to Trello. " . $e->getMessage());
        }
    }
    static function createTrelloTitle($ticket){
        return $ticket->getId() . " - " . $ticket->getSubject();
    }
    static function parseTrelloTicketNumber($title){
        try{
            return substr ( $title , 0, strpos ( $title , "-" ) - 1 );
        }
        catch(Exception $e){
            return "";
        }
    }
    // Add new Routes
    static public function callbackDispatch($object, $data) {
        $trello = url_post ( '^/trello$', array('api.trello.php:TrelloApiController','postFromTrello'));
        $trello_all = url ( '^/trello$', array('api.trello.php:TrelloApiController','allFromTrello'));
        $object->append ( $trello );
        $object->append ( $trello_all );
        
    }
    // https://developers.trello.com/apis/webhooks#source
    static public function isValidTrelloIP($ip){
        $trelloIPs = array("107.23.104.115","107.23.149.70","54.152.166.250","54.164.77.56");
        return in_array($ip,$trelloIPs);
    }
}