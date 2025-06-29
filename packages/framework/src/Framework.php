<?php

namespace Aspire;

class Framework
{
    public function input($server, $get, $post, $cookie, $session, $files)
    {
        return $this;
    }

    public function run()
    {
        return true;
    }
}
