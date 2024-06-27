<?php

namespace controllers;

use models\ChatMessage;

use traits\SecurityController;

class ChatMessageController
{
    use SecurityController;

    private ChatMessage $chatmessage;

    
    public function __construct()
    {
        $this -> chatmessage = new ChatMessage();

    }

}
