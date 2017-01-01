<?php

require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.app.php');

require_once('config.php');

require_once(TRELLO_PLUGIN_ROOT . 'class.trello_install.php');
require_once(TRELLO_PLUGIN_ROOT . 'api.trello.php');

require_once(TRELLO_PLUGIN_ROOT . 'vendor/autoload.php');
use Trello\Client;
use Trello\Manager;

class TrelloPlugin extends Plugin {

    var $config_class = 'TrelloConfig';

    function bootstrap() {
        if ($this->firstRun()) {
            $this->configureFirstRun();
        }

        $config = $this->getConfig();
        Signal::connect ( 'api', array('TrelloPlugin', 'callbackDispatch' ));
        Signal::connect('ticket.created', array($this, 'onTicketCreated'), 'Ticket');
        Signal::connect('model.updated', array($this, 'onModelUpdated'));
        Signal::connect('model.created', array($this, 'onModelCreated'));
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
        //If it did not come from trello plugin API
        if(!strpos($ticket->getSubject(), "***OSTICKETPLUGIN***") === 0){
        // if($ticket->getSource() != "Trello"){
            try{
                $config = $this->getConfig();
                // If the ticket was made for the department with a hook into Trello
                if($config->get('osticket_department_id') == $ticket->dept->id){
                    // TRELLO CHANGES ON TICKET CREATION
                    $client = new Client();
                    $client->authenticate($config->get('trello_api_key'), $config->get('trello_api_token'), Client::AUTH_URL_CLIENT_ID);
                    // // POST to Trello
                    $newcard = array("idList"=> $config->get('trello_list_id'), "name"=>TrelloPlugin::createTrelloTitle($ticket), "desc"=>$ticket->getLastMessage()->getBody());
                    $client->cards()->create($newcard);
                }
            }
            catch(Exception $e){
                error_log("Error posting to Trello. " . $e->getMessage());
            }
        // }
        }
    }

    function onModelUpdated($object, $data){
        // $data is the old data that was before being changed
        // $object is the object with the updated data
        // A Ticket was updated
        if(get_class($object) === "Ticket"){
            $ticket = $object;
            // Authenticate to Trello
            $config = $this->getConfig();
            $client = new Client();
            $client->authenticate($config->get('trello_api_key'), $config->get('trello_api_token'), Client::AUTH_URL_CLIENT_ID);

            // If the status was updated
            if(isset($data['dirty']['status_id'])){
                // $data['dirty']['status_id'] - is the old status id
                // $ticket->getStatusId(); is the new status id

                // Matching the status in OSTicket to a List in Trello
                $trelloCardId = TrelloPlugin::getTrelloCardId($ticket, $client, $config);
                $trelloListId = TrelloPlugin::getTrelloListId($ticket->getStatusId(), $client, $config);
                if(!empty($trelloCardId) && !empty($trelloListId)){
                    // Updating the list/status in Trello
                    $client->cards()->setList($trelloCardId, $trelloListId);
                }
            }
        }
    }

    function onModelCreated($object, $data){

    }

    static function createTrelloTitle($ticket){
        return $ticket->getId() . " - " . $ticket->getSubject();
    }

    static function parseTrelloTicketNumber($title){
        try{
            $ticketNumber = substr ( $title , 0, strpos ( $title , "-" ) - 1 );
            if(TrelloPlugin::isInteger($ticketNumber)){
                return $ticketNumber;
            }
        }
        catch(Exception $e){
        }
        return null;
    }

    // Add new Routes
    public static function callbackDispatch($object, $data) {
        $object->append(url_post('^/trello$', array('TrelloApiController', 'postFromTrello')));
        $object->append(url('^/trello$', array('TrelloApiController', 'allFromTrello')));
    }

    // https://developers.trello.com/apis/webhooks#source
    public static function isValidTrelloIP($ip){
        $trelloIPs = array("107.23.104.115","107.23.149.70","54.152.166.250","54.164.77.56");
        return in_array($ip,$trelloIPs);
    }

    public static function getTrelloListId($osticketStatusId, $client, $config){
        try{
            //Get all statuses
            $statusesOrig = TicketStatusList::getStatuses(array('states' => $states))->all();
            $statuses = array();
            foreach ($statusesOrig as $status){
                $statuses[$status->getId()] = $status->getName();
            }
            $statusName = $statuses[$osticketStatusId];
            // get trello lists for board
            // Lists
            $trelloLists = $client->boards()->lists()->all($config->get('trello_board_id'));
            // Match based on names
            $matchingTrelloList = TrelloPlugin::searchArrayByProperty($trelloLists,'name',$statusName);
            // https://developers.trello.com/advanced-reference/board#get-1-boards-board-id-lists
            return $matchingTrelloList['id'];
        }
        catch(Exception $e){
            return null;
        }
    }

    public static function getTrelloCardId($ticket, $client, $config){
        try{
            $trelloCards = $client->boards()->cards()->all($config->get('trello_board_id'));
            $matchingTrelloCard = TrelloPlugin::searchArrayByProperty($trelloCards,'name',TrelloPlugin::createTrelloTitle($ticket));
            return $matchingTrelloCard['id'];
            // https://developers.trello.com/advanced-reference/board#get-1-boards-board-id-cards
        }
        catch(Exception $e){
            return null;
        }
    }

    public static function searchArrayByProperty($array,$property,$value){
        try{
            $item = null;
            foreach($array as $struct) {
                if ($value == $struct[$property]) {
                    $item = $struct;
                    break;
                }
            }
            return $item;
        }
        catch(Exception $e){
            return null;
        }
    }
    public static function isInteger($input){
        return (ctype_digit(strval($input)) && !empty($input));
    }
}