<?php
/**
 * Router class
 * 
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 * @namespace  Talkwork
 */

namespace Talkwork;

class Router
{
    /*
/module/custom

module has a front controller which:
- has a model for its stored data about routing
- first calls action-handling controller (post/put/delete) which should be passed a particular model
- then sets up a VM (passed a model) and a view (passed the VM) depending on accept type

tw: if no module exists in path, then try the default list for pattern matches.
    */
}
