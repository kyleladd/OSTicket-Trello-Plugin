<?php

include_once INCLUDE_DIR.'class.api.php';

class TrelloApiController extends ApiController {

    function restGetTrello() {
    	$ticketNumber = "";
    	$statusesOrig = TicketStatusList::getStatuses(array('states' => $states))->all();
    	$statuses = array();
    	foreach ($statusesOrig as $status){
    		$statuses[$status->getId()] = $status->getName();
    	}
    	// If it is a card, get matching ticket
    	// If it is a card being moved into a new list,
    	if(true){
    		$ticketNumber = TrelloPlugin::parseTrelloTicketNumber("12345 - Title Here");
    		if($ticketNumber == ""){
    			$this->response(200, json_encode("Unable to parse ticket number"),
             $contentType="application/json");
    			// return false;
    		}
    		// If there is a matching OSTicket status, update ticket status to Trello list as status
    	}
        // $tickets = array("message"=>"hello");
        // $this->response(200, json_encode($statuses),
        //      $contentType="application/json");
        $this->response(200, json_encode($ticketNumber),
             $contentType="application/json");
    }

}
?>
