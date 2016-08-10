<?php

require_once INCLUDE_DIR.'class.api.php';
require_once INCLUDE_DIR.'class.ticket.php';

class TrelloApiController extends ApiController {
    function allFromTrello(){
            $this->response(200, json_encode("Hello World from Trello Plugin."),
             $contentType="application/json");
    }
    function postFromTrello(){
        try{
            global $ost;
            $ost->logDebug("DEBUG","POST ENDPOINT. ");
            // https://developers.trello.com/apis/webhooks
            // HTTP_X_REAL_IP
            // https://trello-attachments.s3.amazonaws.com/560559353b2a6add4ccd6375/578bbcf546a39bec58b8cc07/b016dc04542b9caf8c90e8d9ee9d02cf/trello-post-server-data.json
            // $json = json_decode(file_get_contents('php://input'));
            $json = $this->getRequest('json');
            if(!TrelloPlugin::isvalidTrelloIP($_SERVER['HTTP_X_REAL_IP'])){
                $this->response(401, json_encode("Bad IP"),
                 $contentType="application/json");
            }
            $ticket_id = "";
            $statusesOrig = TicketStatusList::getStatuses(array('states' => $states))->all();
            $statuses = array();
            foreach ($statusesOrig as $status){
                $statuses[$status->getId()] = $status->getName();
            }
            // If it is a card, get matching ticket
            // If it is a card being moved into a new list,
            if($json['action']['type']==="updateCard"){
                // If we are moving between lists
                if(isset($json['action']['data']['listAfter'])){
                    $status = array_search($json['action']['data']['listAfter']['name'],$statuses);
                    $ticket_id = TrelloPlugin::parseTrelloTicketNumber($json['action']['data']['card']['name']);
                    if($ticket_id == ""){
                        $ost->logDebug("DEBUG","Can't parse ticket. ". json_encode($json));
                        $this->response(404, json_encode("Unable to parse ticket id"),
                            $contentType="application/json");
                    }
                    $ticket = Ticket::lookup($ticket_id);
                    if($ticket == null){
                        $ost->logDebug("DEBUG","Can't find ticket. ". json_encode($json));
                        $this->response(404, json_encode("Unable to find ticket."),
                            $contentType="application/json");
                    }
                    if($ticket->setStatus($status)){
                        $this->response(200, json_encode($ticket),
                        $contentType="application/json");
                    }
                    else{
                        $ost->logDebug("DEBUG","Can't update ticket. ". json_encode($json));
                        $this->response(500, json_encode("Unable to update ticket status"),
                        $contentType="application/json");
                    }
                    // If there is a matching OSTicket status, update ticket status to Trello list as status
                }
                else{
                    $ost->logDebug("DEBUG","Not moving between lists. ". json_encode($json));
                }
            }
            else{
                $ost->logDebug("DEBUG","Not updating a card. ". json_encode($json));
            }
            $this->response(403, json_encode("Not prepared to handle this request yet."),
                        $contentType="application/json");
        }
        catch(Exception $e){
            $ost->logDebug("DEBUG","Error post from Trello. " . $e->getMessage());
            var_dump($e);
            return false;
        }
    }

}
?>
