<?php
/**
 * File:  /core/MySQLDB.class.php
 * MySQL abstraction layer
 * 
 * @since      0.1
 * @version    0.1
 * @author     Garrett Whitehorn
 * @package    Talkwork
 */

namespace Talkwork;

class MySQLDB
{
    private $dbo;
    private $changes;

    function __construct($host,$user,$pass,$dbname)
    {
        $this->dbo = @new mysqli($host,$user,$pass,$dbname);
        if (mysqli_connect_errno()) {
            $this->error();
        }
        if ($this->dbo->character_set_name() != 'utf8'
            && method_exists($this->dbo,'set_charset')
        ) {
            $this->dbo->set_charset('utf8');
        }
        $this->changes = [];
    }

    function __destruct()
    {
        $this->dbo->close();
        unset($this->dbo);
    }
  
    private function __clone()
    {
        // not allowed
    }

    private function __wakeup()
    {
        // unserialization not allowed
    }

    private function error($q = null)
    {
        if (mysqli_connect_errno()) {
            $errno = mysqli_connect_errno();
            $error = mysqli_connect_error();
            $q = '';
        } else {
            $errno = $this->dbo->errno;
            $error = $this->dbo->error;
            $q = " in \"$q\"";
        }
        Error::send(500,E_FATAL,"MySQL #$errno: $error$q");
    }

    function q($q)
    {
        $qr = $this->dbo->query($q) or $this->error($q);
        return $qr;
    }

    function insert($table,$vs)
    { //todo: allow $vs to be an assoc array
        $this->changes[] = 'INSERT INTO `'.$table."` $vs;";
    }

    // pass a resource returned from q() and get the assoc array
    function read_from_res($qr)
    {
        $ra = [];
        while ($row = $qr->fetch_assoc()) {
            $ra[] = $row;
        }
        $qr->free_result();
        return $ra;
    }
  
    // pass a query string and get the assoc array
    function read($q)
    {
        return $this->read_from_res($this->q($q));
    }

    // pass a resource returned from q() and get the count
    function count_from_res($qr)
    {
        return $qr->num_rows;
    }

    // pass a query string and get the count
    function count($q)
    {
        return $this->count_from_res($this->q($q));
    }

    function read_one_row($q)
    {
        $ta = $this->read($q);
        if (count($ta) == 0) {
            return false;
        }
        return $ta[0];
    }

    function read_one_field($q)
    {
        $ta = $this->read_one_row($q);
        if (!$ta) {
            return false;
        }
        return current($ta);
    }

    function update()
    {

    }

    function delete()
    {

    }

    function commit()
    { // I know this is bad -- I'll change it later
        foreach ($this->changes as $qs) {
            $this->q($q);
        }
        $this->changes = [];
    }
}
