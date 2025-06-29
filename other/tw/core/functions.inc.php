<?php
/**
 * Does this file really need an explanation?
 *
 * @since      0.1
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

function shortcut_exists($s)
{
    
}

function module_exists($m)
{
    return is_dir(DIR_MODULES.$m);
}

function model_exists($mm)
{
    return file_exists(DIR_MODULES."$mm.model.php");
}

function check_shortcut($s)
{
    if (!shortcut_exists($s)) {
        Error::send(404,E_FATAL,"Shortcut '$s' not found.");
    }
    $ss = explode('/',$s);
    check_module($ss[0]);
    check_controller($ss[1]);
}

function check_module($m)
{
    if (!module_exists($m)) {
        Error::send(404,E_FATAL,"Module '$m' not found.");
    }
}

function check_model($mm)
{
    if (!model_exists($mm)) {
        Error::send(404,E_FATAL,"Model '$mm' not found."); // substr($m,0,strpos($m,'/'))
    }
}

function check_class($c)
{
    if (!class_exists($c)) {
        Error::send(500,E_FATAL,"Class '$c' not defined.");
    }
}

function check_theme_file($f)
{
    if (file_exists($f)) {
        return true;
    } else {
        Error::send(500,E_FATAL,"Theme file '$f' not found.");
    }
}

function load_model($mm)
{
    check_module($mm);
    include DIR_MODULES . $mm . '.model.php';
}

function load_view($mc)
{
    load_theme_file('config');
    load_theme_file($mc . '.view');
}

function load_theme_file($f)
{
    $path = DIR_THEMES . CUR_THEME . '/' . $f . '.php';
    if (check_theme_file($path)) {
        include $path;
    }
}

function pwhash($password, $salt = null)
{
    $salt = '$5$' . ((isset($salt)) ? $salt : uniqid()) . '$';
    return crypt($password, $salt);
}

function pwstrength($pw, $options = [])
{
    $errors = [];
    
    // Default options
    $defaults = [
        "minChars"			=> 8,		// Minimum characters a password must have
        "numRequired"		=> true,	// At least one number is required
        "lcaseRequired"		=> true,	// At least one lower-case letter must be required
        "ucaseRequired"		=> true,	// At least one upper-case letter must be required
        "specialRequired"	=> true,	// There must be at least one special character (Non-alpha numeric)
    ];
    
    // Merge the custom $options with the $defaults
    $opts = array_merge($defaults, $options);
    
    // Check the password's length
    if (mb_strlen($pw) < $opts['minChars'])
        $errors[] = "Your password must contain at least " . $opts['minChars'] . " characters or more";
    
    // Check if the password contains a number
    if ($opts['numRequired'] === true && !preg_match("/[0-9]+/i", $pw))
        $errors[] = "Your password must contain at least one number";
    
    // Check if password contains a lower case letter
    if ($opts['lcaseRequired'] === true && !preg_match("/[a-z]+/i", $pw))
        $errors[] = "Your password must contain at least one lower case character";
    
    // Check if password contains an upper case letter
    if ($opts['ucaseRequired'] === true && !preg_match("/[A-Z]+/i", $pw))
        $errors[] = "Your password must contain at least one upper case character";
    
    // Check if password contains a special character (non-alphanumerical)
    if ($opts['specialRequired'] === true && !preg_match("/[^A-Za-z0-9]+/i", $pw))
        $errors[] = "Your password must contain at least one special character";
    
    return $errors;
}

function format_page_title($breadcrumbs)
{ // in order from least to most specific
    global $twdb;
    if ($twdb->configs_core['site-name-in-title'] == 'last') {
        $breadcrumbs = array_reverse($breadcrumbs);
    }
    array_unshift($breadcrumbs,$twdb->configs_core['site-name']);
    $output = '';
    $separator = $twdb->configs_core['title-separator'];
    foreach ($breadcrumbs as $item) {
        $output .= "$item $separator ";
    }
    $output = substr($output,0,strlen($output)-strlen(" $separator "));
    return $output;
}

function insertLinkPath()
{
    return (($GLOBALS['twdb']->configs_core['sef-urls'] == 'true') ? 'index.php/' : '');
}

function is_sql_clean($s)
{
    return preg_match('/[`\']/',$s);
}

function sql_clean($s)
{
    return preg_replace('/[`\']/','',$s);
}

function loadfile($file,$nl = "\n")
{
    return explode($nl,file_get_contents($file));
}

function writefile($path,$content,&$errmsg,$verify = false,$timeLimit = 2,$nl = "\n",$endTime = null)
{
    if (is_array($content)) {
        $content = implode($nl,$content); // force $content to be a string
    }
    ignore_user_abort(true);
    fclose(fopen($path,'a'));     // if !file_exists, then make one. otherwise it's a simple open/close on an existing file.
    if (!$fileout = @fopen($path,'r+b')) { // do the fopen() or die (r+ == read&write, pointer@beginning)
        $errmsg = 'fopen';
        ignore_user_abort(false);
        return false;
    }
    $oldtime = time();                    // start flock timer
    do {
        $didlock = @flock($fileout,LOCK_EX,$blocked); // do the flock() ...
        $timedout = (time() - $oldtime > $timeLimit);
    } while ((!$didlock || $blocked) && !$timedout); // or ...
    if ($timedout) {                                 // if not locked within $timeLimit ...
        $oldtime = time();                             // start lock dir timer
        $lockpath = "$path.lock";
        do {
            $lockdir = @mkdir($lockpath);                // make the .lock dir ...
            $timedout = (time() - $oldtime > $timeLimit);
        } while (!($lockdir || $timedout));
        if ($timedout) {                                // or die
            fclose($fileout);
            $errmsg = 'flock';
            ignore_user_abort(false);
            return false;
        }
    }

    rewind($fileout);
    if (!(@fwrite($fileout,$content) && @fflush($fileout))) {        // do the fwrite()/fflush() or die
        fclose($fileout);
        if ($lockpath) {
            rmdir($lockpath);
        }
        $errmsg = 'fwrite/fflush';
        ignore_user_abort(false);
        return false;
    }
    ftruncate($fileout, ftell($fileout));
    fclose($fileout);                     // fclose()

    if ($lockpath) {
        rmdir($lockpath);
    }

    if ($verify) {
        if (!$endTime) {
            $endTime = time() + $timeLimit;
        } else if (time() >= $endTime) {
            $errmsg = 'fwrite_verify';
            ignore_user_abort(false);
            return false;
        } else if (file_get_contents($path) != $content) {
            writefile($path,$content,$errmsg,true,$timeLimit,$endTime);
        }
    }

    ignore_user_abort(false);
    return true;
}

function strip_html($string)
{
	return htmlentities($string, ENT_QUOTES | ENT_IGNORE, "UTF-8");
}
