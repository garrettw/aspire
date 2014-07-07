<?php
/**
 * File:  /core/parseurl.php
 * Converts search-engine-friendly URIs to the corresponding data items
 *
 * @since      0.1
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

/* scheme: /[WS_ROOT][index.php/][module][/controller][/params][?query-string]
 *                               [or custom shortcut ]
 *
 * The idea here is that a verbose URI that tells Tw exactly what to do can be
 * made less verbose if the desired module and/or controller to handle the request
 * is the same as the default set in the database. For example, let's say we're
 * running a site that is nothing more than a blog. The most explicit URI you
 * could use for the home page would be:
 *     /blog/show/0
 * or even:
 *     /blog/show/home-page-slug-is-here
 * But if our default module is "blog", default controller is "show", and default
 * params is "0", then you could access that same resource with a URI of,
 * simply, "/".
 *
 * Now let's say you want to access some specific blog post. There are multiple
 * ways you could do this:
 *     /blog/show/1
 *     /blog/1
 *     /show/1
 *     /1
 * ...where you could also replace "1" with the post's slug on any of those, like:
 *     /hello-world-first-post-woohoo
 * 
 * One last thing I do here is to take something like:
 *     /form/show/contact/from=your@email.com
 * and parse that section after the last slash into $_GET, so that this is true:
 *     $_GET['from'] == 'your@email.com'
 * Another way params can be tacked on is by index rather than by name, so:
 *     /shop/list/123/color/white
 * would be parsed such that:
 *     $_GET[0] == '123'        - presumably the category ID we want to list
 *     $_GET[1] == 'color'      - looks like we need to filter our results by color
 *     $_GET[2] == 'white'      - and the color needs to be white
 */


/* split slash-separated items into array,
    skipping the prefix of the installation root dir */
$path = explode('/', substr($_SERVER['REQUEST_URI'], WS_ROOT_LENGTH));
$parts = count($path);
// and just for the sake of brevity, make some shortcut-variables
$def_mod = &$twdb->configs['core']['default-module'];
$def_ctrl = &$twdb->configs['core']['default-controller'];

// detect 'index.php'; if not using or empty path, discard first part
if (strpos($path[0], 'index.php') !== FALSE || empty($path[0])) {
    array_shift($path);
    --$parts;
}
if ($parts != 0 && empty($path[$parts-1])) {
    unset($path[$parts-1]); // if path ends in a slash, ignore it
}

// Reconstruct full data set if parts are missing
if (isset($path[0])) {
    // first, check for shortcut
    if (is_array($twdb->configs['core']['shortcuts'])) {
        // $scuts = // finish later
        if (in_array($path[0], $twdb->configs['core']['shortcuts'])) {
            /* list($mod,$ctrl) = explode();
             if () { // finish later */

        }
    } else {
        // if part 0 is not a valid module, use the default if it exists
        if (!module_exists($path[0])) {
            if (module_exists($def_mod)) {
                array_splice($path, 0, 0, $def_mod);
            } else {
                // fatal error
            }
        }
        // if part 1 is not a valid controller, use the default if it exists
        if (isset($path[1])) {
            if (!controller_exists($path[0] . '/' . $path[1])) {
                if (controller_exists($path[0] . '/' . $def_ctrl)) {
                    array_splice($path, 1, 0, $def_ctrl);
                } else {
                    // fatal error
                }
            }
        } else {
            $path[] = $def_ctrl;
        }
    }
} else if (module_exists($def_mod)) {
    if (controller_exists($def_mod . '/' . $def_ctrl)) {
        // if neither of 0 & 1 are set, use both defaults if possible
        $path = [$def_mod, $def_ctrl];
    } else {
        // fatal error
    }
} else {
    // fatal error
}
// if there are no params, use the default
if (!isset($path[2])) {
    $path = array_merge($path,explode('/',$twdb->configs['core']['default-params']));
}


// Set up module/controller defs
define('CUR_MODULE', $path[0]);
define('CUR_CONTROLLER', $path[1]);
define('CUR_MM', CUR_MODULE . '/' . ucfirst(CUR_MODULE));
define('CUR_MC', CUR_MODULE . '/' . CUR_CONTROLLER);

// if there's no '?' in the URI (if there was, $_GET would already be populated)    /change to: parse up to '?' and ignore the rest
if (strpos($_SERVER['REQUEST_URI'], '?') === FALSE) {
  $parts = count($path);
  for ($i = 2; $i < $parts; ++$i) {   // starting after the controller name,
    if (strpos($path[$i], '=') === FALSE) { // if an item has no '=',           /todo: add condition where nothing before '='
      $_GET[] = $path[$i];        // add it to $_GET with a simple numeric index
    } else {                          // otherwise,
      $temp = explode('=', $path[$i]); // use the key name given in the URI
      $_GET[$temp[0]] = $temp[1];     // as the index of the item added to $_GET
    }
  }
}

// if query string exists:
  // if there's a named-param conflict, redirect to a URL with the query-string param replacing the slashed param but the rest of the QS intact.
  // else, splice any slashed params onto the front of $_GET (no redirect)

unset($path, $parts);
