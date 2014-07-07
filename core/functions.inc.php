<?php
/**
 * Does this file really need an explanation?
 *
 * @since      0.1
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

function shortcut_exists ($s) {
    
}

function module_exists ($m) {
    return is_dir(DIR_MODULES.$m);
}

function model_exists ($mm) {
    return file_exists(DIR_MODULES."$mm.model.php");
}

function controller_exists ($mc) {
    return file_exists(DIR_MODULES."$mc.ctrl.php");
}

function check_shortcut ($s) {
    if (!shortcut_exists($s)) {
        Error::send(404,E_FATAL,"Shortcut '$s' not found.");
    }
    $ss = explode('/',$s);
    check_module($ss[0]);
    check_controller($ss[1]);
}

function check_module ($m) {
    if (!module_exists($m)) {
        Error::send(404,E_FATAL,"Module '$m' not found.");
    }
}

function check_model ($mm) {
    if (!model_exists($mm)) {
        Error::send(404,E_FATAL,"Model '$mm' not found."); // substr($m,0,strpos($m,'/'))
    }
}

function check_controller ($mc) {
    if (!controller_exists($mc)) {
        Error::send(404,E_FATAL,"Controller '$mc' not found.");
    }
}

function check_class ($c) {
    if (!class_exists($c)) {
        Error::send(500,E_FATAL,"Class '$c' not defined.");
    }
}

function check_theme_file ($f) {
    if (file_exists($f)) {
        return true;
    } else {
        Error::send(500,E_FATAL,"Theme file '$f' not found.");
    }
}

function load_model ($mm) {
    check_module($mm);
    include DIR_MODULES . $mm . '.model.php';
}

function load_controller ($c) {
    check_controller($c);
    include DIR_MODULES . $c . '.ctrl.php';
}

function load_view ($mc) {
    load_theme_file('config');
    load_theme_file($mc . '.view');
}

function load_theme_file ($f) {
    $path = DIR_THEMES . CUR_THEME . '/' . $f . '.php';
    if (check_theme_file($path)) {
        include $path;
    }
}

function include_plugin_file ($f) {
    $f = DIR_PLUGINS . $f;
    if (file_exists($f)) {
        include $f;
    } else {
        Error::send(200,E_NONFATAL,"Plugin file '$f' not found.");
    }
}

/* function pwhash ($pw,$salt=false) {
    if (!$salt) $salt = '$5$'.uniqid().'$';
    return crypt($pw,$salt);
    //return '$5$'.$salt.'$'.hash_hmac('sha256',$pw,$salt,true);
}

function pw2hash ($pw1, $pw2) { // This one is a lot faster, so not as good against brute-force, but possibly better against db hackers
  return hash_hmac('ripemd160',$pw1,$pw2);
} */

function pwstrength ($pw) {
  // FINISH
}

function format_page_title ($breadcrumbs) { // in order from least to most specific
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

function insertLinkPath () {
    return (($GLOBALS['twdb']->configs_core['sef-urls'] == 'true') ? 'index.php/' : '');
}

function string_to_slug ($s) {
    return strtolower(preg_replace(array('/[!"#\'\(\)\*,\-\.:;\?`‘’“”–— ´]/','/[ \/\\…·]/','/(\d+)%/','/&/','/(==|=)/'),
                                   array('','-','$1-percent','and','equals'),$s));
}

function is_sql_clean ($s) {
    return preg_match('/[`\']/',$s);
}

function sql_clean ($s) {
    return preg_replace('/[`\']/','',$s);
}

function loadfile ($file,$nl = "\n") {
    return explode($nl,file_get_contents($file));
}

function writefile ($path,$content,&$errmsg,$verify = false,$timeLimit = 2,$nl = "\n",$endTime = null) {
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
            writefile($path,$content,&$errmsg,true,$timeLimit,$endTime);
        }
    }

    ignore_user_abort(false);
    return true;
}
