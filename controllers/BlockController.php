<?php

namespace controllers;

use models\Block;

use traits\SecurityController;

class BlockController
{
    use SecurityController;

    private Block $block;

    
    public function __construct()
    {
        $this -> block = new Block();

    }

}