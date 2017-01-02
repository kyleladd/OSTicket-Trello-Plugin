<?php

require_once INCLUDE_DIR.'class.api.php';
require_once INCLUDE_DIR.'class.ticket.php';
require_once(TRELLO_PLUGIN_ROOT . 'trello.php');
use Trello\Client;
use Trello\Manager;
class TrelloApiController extends ApiController {
    function allFromTrello(){
            $this->response(200, json_encode("Hello World from Trello Plugin."),
             $contentType="application/json");
    }
    function postFromTrello(){
        try{
            global $ost, $cfg;
            $config = TrelloPlugin::getConfig();
            $errors = array();
            $ticket = null;
            // https://developers.trello.com/apis/webhooks
            // HTTP_X_REAL_IP
            $json = $this->getRequest('json');
            if(!TrelloPlugin::isvalidTrelloIP($_SERVER['HTTP_X_REAL_IP'])){
                $this->response(401, json_encode("Bad IP"),
                 $contentType="application/json");
            }
            // TODO - if not valid webhook/listId Trello Model Id, return 401
            if($json['model']['id'] !== $config->get('trello_board_id')){
                $this->response(401, json_encode("Trello list id does not match what is stored in OSTicket"),
                 $contentType="application/json");
            }

            // For matching the Trello list names to the status names
            $statusesOrig = TicketStatusList::getStatuses(array('states' => $states))->all();
            $statuses = array();
            foreach ($statusesOrig as $status){
                $statuses[$status->getId()] = $status->getName();
            }

            $client = new Client();
            $client->authenticate($config->get('trello_api_key'), $config->get('trello_api_token'), Client::AUTH_URL_CLIENT_ID);
            $manager = new Manager($client);
            
            if($json['action']['type']==="createCard"){
                $ticket_id = TrelloPlugin::parseTrelloTicketNumber($json['action']['data']['card']['name']);
                if($ticket_id != null){
                    $ticket = Ticket::lookup($ticket_id);
                }
                // Ticket creation was initiated from trello
                if($ticket == null){
                    // $duedate = () ? : "";
                    $duedate = "";
                    $subject = $json['action']['data']['card']['name'];
                    $statusId = array_search($json['action']['data']['list']['name'],$statuses);
                    $card = $manager->getCard($json['action']['data']['card']['id']);
                    $desc = $card->getDescription();
                    
                    if(!empty($desc)){
                        $message = $desc;
                    }
                    else{
                        $message = "Card was created in Trello, description is coming soon. The card is located: <a href=\"https://trello.com/c/".$json['action']['data']['card']['shortLink']."\">https://trello.com/c/".$json['action']['data']['card']['shortLink']."</a>";
                    }

                    $ticketToBeCreated = array(
                        "subject" => "***OSTICKETPLUGIN***".$subject,
                        "message" => $message,
                        "duedate" => $duedate,
                        "statusId" => $statusId,
                        // "source" => "Trello",
                        "source" => "Other",
                        "email" => $config->get('trello_user_email')
                    );
                    
                    $ticket = Ticket::create($ticketToBeCreated, $errors, "api", false, false);
                    if($ticket == null || !empty($errors)){
                        $ost->logDebug("DEBUG","Can't create ticket. ". json_encode($json));
                        $this->response(500, json_encode($errors),
                            $contentType="application/json");
                    }
                    $entries = $ticket->getThread()->getEntries();
                    $ticket->_answers['subject'] = $ticket->getId() . " - " . $subject;

                    foreach (DynamicFormEntryAnswer::objects()
                        ->filter(array(
                            'entry__object_id' => $ticket->getId(),
                            'entry__object_type' => 'T'
                        )) as $answer
                    ) {
                        if(mb_strtolower($answer->field->name)
                            ?: 'field.' . $answer->field->id == "subject"){
                            $answer->setValue($ticket->getId() . " - " . $subject);
                            $answer->save();
                        }
                    }
                    foreach ($entries as $entry) {
                        $entry->title = $ticket->getId() . " - " . $subject;
                        $entry->save();
                    }
                    $client->cards()->setName($json['action']['data']['card']['id'], $ticket->getSubject());
                }
            }
            // If it is a card being moved into a new list,
            elseif($json['action']['type']==="updateCard"){
                // Get matching ticket to the card that was updated
                $ticket_id = TrelloPlugin::parseTrelloTicketNumber($json['action']['data']['card']['name']);
                if($ticket_id == null){
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

                // If we are moving between lists - Updating the ticket status
                if(isset($json['action']['data']['listAfter'])){
                    $status = array_search($json['action']['data']['listAfter']['name'],$statuses);
                    if(!empty($status) && $ticket->getStatusId() != $status){
                        if($ticket->setStatus($status)){
                            $this->response(200, json_encode($ticket),
                            $contentType="application/json");
                        }
                        else{
                            $ost->logDebug("DEBUG","Can't update ticket. ". json_encode($json));
                            $this->response(500, json_encode("Unable to update ticket status"),
                            $contentType="application/json");
                        } 
                    }
                    // If there is a matching OSTicket status, update ticket status to Trello list as status
                }
                // Update the ticket description 
                // TODO - verify \n\r\t are maintained during syncing
                if(isset($json['action']['data']['card']['desc']) && $ticket->getThreadEntries()[0]->getBody()->body !== $json['action']['data']['card']['desc']){
                    $ticket->getThreadEntries()[0]->setBody($json['action']['data']['card']['desc']);
                }
            }
            // If comment was added to card
            elseif($json['action']['type']==="commentCard"){
                $ticket = TrelloPlugin::getOSTicketFromTrelloHook($json);
                $ticket->getThread()->addResponse(array("threadId"=>$ticket->getThreadId(), "response"=>$json['action']['data']['text']), $errors);
            }
            if(!empty($errors)){
                $ost->logDebug("DEBUG","Errors: ". json_encode($errors));
                $this->response(500, json_encode($errors),
                        $contentType="application/json");
            }
            else{
                $this->response(200, "Ticket updated",
                        $contentType="application/json");
            }
        }
        catch(Exception $e){
            $ost->logDebug("DEBUG","Error post from Trello. " . $e->getMessage());
            $this->response(500, json_encode($e->getMessage()),
                        $contentType="application/json");
        }
    }
}
?>
