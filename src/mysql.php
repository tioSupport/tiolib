<?php

namespace tiolib;

class mysql {

    private $debug_info = Array();
    private $cn;

    private function q($qry){

        $start_time = microtime(true);

        $rs = mysqli_query($this->cn, $qry) or $this->sql_err(mysqli_errno($this->cn),mysqli_error($this->cn),$qry);

        $stop_time = microtime(true);

        $dbg = Array();
        $dbg['page'] = basename($_SERVER['PHP_SELF']);
        $dbg['query'] = $qry;

        $dbg['time'] = round((($stop_time-$start_time) * 1000), 2).' ms';

        array_push($this->debug_info,$dbg);

        return $rs;

    }

    # genera un'errore e lo passa all'handler di PHP
    private function sql_err($num,$err,$info = ""){

        trigger_error($num.': '.$err.': '.$info);

    }

    # connessione al database
    public function connect($hostname,$database,$username,$password) {


        /*$this->cn = mysqli_init();
        mysqli_options($this->cn, MYSQLI_OPT_LOCAL_INFILE, true);
        mysqli_real_connect($this->cn,$hostname,$username,$password,$database);
*/

        $this->cn = mysqli_connect($hostname, $username, $password)
            or $this->sql_err(mysqli_connect_errno(),mysqli_connect_error(),'Impossibile connettersi al database');

        if (mysqli_connect_errno()){
            die();
        }

        $db = mysqli_select_db($this->cn, $database)
            or $this->sql_err(mysqli_connect_errno(),mysqli_connect_error(),'Impossibile selezionare il database');

        $this->q("SET NAMES 'utf8mb4'");

    }

    # Riporto errore
    public function last_error($qry) {

        return mysqli_error($this->cn);

    }

    # query standard
    public function query($qry) {

        return $this->q($qry);

    }

    # Escape string
    public function escape($str) {

        return mysqli_real_escape_string($this->cn, $str);

    }

    # ritorna il numero di righe ritornate da una query
    public function row_count($qry) {

        return mysqli_num_rows($this->q($qry));

    }

    # ritorna l'id dell'ultimo record inserito
    public function insert_id() {

        return mysqli_insert_id($this->cn);

    }

    # ritorna il numero di record presenti in una tabella (VELOCE)
    public function table_size($tbl_name) {

        $rs = $this->q('select count(*) from '.$tbl_name.';');
        $row = mysqli_fetch_row($rs);
        return $row[0];

    }

    # ritorna true se la query ha prodotto risultati
    public function row_exists($qry) {

        if (mysqli_num_rows($this->q($qry)) > 0){
            return true;
        } else {
            return false;
        }

    }

    # esegue una query al database e trasforma il risultato in un array
    public function queryex($qry) {

        $ar = array();
        $rs = $this->q($qry);

        if ($rs && !empty($rs->num_rows)){
            while($r = mysqli_fetch_assoc($rs)) {
            array_push($ar, $r);
            }
        }

        return $ar;

    }

    # ritorna informazioni di debug, query eseguite e tempi di esecuzione
    public function debug($expand = false){

        $dbg = '';
        if ($this->debug_info){

            $dbg .= '<div style="float:left;padding:10px;font-family:courier new;font-size:12px;color:#000000">';

            if ($expand){
                $dbg .= '<b>DEBUG MYSQL</b>';
                $dbg .= '<div id="mydbg">';
            } else {
                $dbg .= '<b>DEBUG MYSQL</b> <a style="text-decoration:underline;" href="javascript:void(0);" onclick="document.getElementById(\'mydbg\').style.display=\'block\'">Mostra</a>';
                $dbg .= '<div id="mydbg" style="display:none;">';
            }

            $i = 1;
            $timetotal = 0;
            foreach($this->debug_info as $qry){
                $timetotal += $qry['time'];
                $col = 'A00000';
                $rowcol = '445566';
                if ($qry['time'] > 20) $col = 'C00000';
                if ($qry['time'] > 50) $col = 'E00000';
                if ($qry['time'] < 0){
                    $qry['time'] = 'err';
                    $rowcol = 'F00000';
                }

                $dbg .= '<br /><br /><b>'.$i.') [<span style="color:#'.$col.'">'.$qry['time'].'</span>]</b> <span style="color:#'.$rowcol.'">'.$qry['query'].'</span>';
                $i++;
            }
            $dbg .= '<br /><br /><b>Total query time:</b> [<span style="color:#3366AA">'.$timetotal .' ms</span>]';
            $dbg .= '</div></div>';

        }
        return $dbg;
    }

}

?>