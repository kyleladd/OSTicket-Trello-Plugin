<?php

include_once INCLUDE_DIR.'class.api.php';

class TrelloApiController extends ApiController {

    function restGetTrello() {
        $tickets = array("message"=>"hello");
        $this->response(200, json_encode($tickets),
             $contentType="application/json");
    }

}
?>
