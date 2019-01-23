<?php

/*CLASS MYSQL with PDO*/

namespace tiolib;
use \PDO;

class mysqlPDO {

    private $debug_info = Array();
    public $db;
    private $stmt;
    private $rows;
    private $mysqlcache = true;
    private $debug = false;
    private $trace_level = -1;
    private $debug_plaintext = false;

    # genera un'errore e lo passa all'handler di PHP
    public function printErrors(){

        print_r($this->stmt->errorInfo()); //versione brutale stampa array senza spiegazioni

    }

    #pulisce la classe da vecchi risultati
    private function clearQuery(){ 
            $this->stmt = null;
            $this->rows = null;
    }

    # connessione al database
    public function connect($hostname,$database,$username,$password,$charset = "utf8mb4") {

         $this->db = new PDO('mysql:host='.$hostname.';dbname='.$database.';charset='.$charset.'', $username, $password);
    }

    # prepara la query
    public function query($qry) {
         $this->clearQuery();
         $this->stmt = $this->db->prepare($qry);

    }

    private function getDataType($data_type){

            if(gettype($data_type) == "integer"){ //le constanti PDO::PARAM sono interi, quindi se e' gia' un intero ritornalo
                    return  $data_type;
            }

            switch ($data_type) {
                case "int":
                     return PDO::PARAM_INT;
                case "str":
                     return PDO::PARAM_STR;
                case "bool":
                     return PDO::PARAM_BOOL;
                case "lob":
                     return PDO::PARAM_LOB;
                default :
                     return PDO::PARAM_STR;
            }
    }

    private function getDataTypeQueryEx($data_type){

            switch ($data_type) {
                case "%s":
                     return "str";
                case "%d":
                     return "int";
                case "%b":
                      return "bool";
                case "%l":
                      return "lob";
                default :
                      return "str";
            }
    }

    private $queryexCount;
    private $replaceMatches;
    private $querycount = 0;
    private $slowquerycount = 0;

    // Ritorna i campi di una tabella mysql
    public function get_table_fields($table){

        $stmt = $this->db->prepare('desc '.$table);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

    // Ritorna un record vuoto basandosi sui campi di una tabella mysql
    public function generate_empty_record($table){
        $item = array();
        $fieldnames = $this->get_table_fields($table);
        foreach ($fieldnames as $f){
            $item[$f['Field']] = '';
        }
        return $item;
    }

    // Aggiornamento automatico della banca dati in base al post
    public function auto_insert($table, $key = 0, $redirect_url = '', $apicall = ''){

        if (strtolower($_SERVER['REQUEST_METHOD']) == 'post'){

            // Validazione dei campi
            if (trim($key) == ''){
                $key = 0;
            }
            $fieldnames = $this->get_table_fields($table);
            $insert_fields = array();

            foreach ($fieldnames as $field){

                if (strtolower($field['Key']) == 'pri'){
                    $keyfield = (string)$field['Field'];
                } else {

                    unset($fdata);
                    if (isset($_POST[(string)$field['Field']])){
                        $fdata = $_POST[(string)$field['Field']];

                        if (stristr($field['Type'], 'tinyint') && !is_numeric($fdata)){
                            if ($fdata == 'on' || $fdata == 'true' || $fdata == 'yes' || $fdata == 'checked'){
                                $fdata = 1;
                            }
                        }
                    } else {
                        if (stristr($field['Type'], 'tinyint')){
                            $fdata = 0;
                        }

                    }

                    if (isset($_POST[(string)$field['Field']])){
                        if (isset($fdata) && strlen($fdata) > 0){
                            $val = $fdata;
                        } else {
                            $val = '';
                        }

                        $insert_fields[(string)$field['Field']] = $val;
                    }
                }
            }

            // Insert / Update
            if (sizeof($insert_fields) > 0){
                $params = [];
                if ($key == 0){
                    $quqery = 'INSERT INTO '.$table.' (';
                    foreach ($insert_fields as $field => $value){
                        $quqery .= $field.',';
                    }
                    $quqery = substr($quqery,0,-1).') values (';
                    foreach ($insert_fields as $field => $value){
                        $quqery .= ':'.$field.',';
                        $params[$field] = $value;
                    }
                    $quqery = substr($quqery,0,-1).')';
                    $this->queryex($quqery, $params, false);
                    $key = $this->insert_id();
                } else {
                    $quqery = 'UPDATE '.$table.' SET ';
                    foreach ($insert_fields as $field => $value){
                        $quqery .= $field.'= :'.$field.',';
                        $params[$field] = $value;
                    }
                    $quqery = substr($quqery,0,-1);
                    $quqery .= ' WHERE '.$keyfield.'= :'.$keyfield.';';
                    $params[$keyfield] = $key;

                    $this->queryex($quqery, $params, false);
                }

            }


            if(!empty($apicall)){
               url_get_contents($apicall);
            }



            if ($redirect_url != ''){
                if ($redirect_url == 'auto'){
                    $current_url = $_SERVER['REQUEST_URI'];
                    if ($current_url{strlen($current_url)-1} == '0'){
                        $target_url = substr($current_url,0,-1).$this->insert_id();
                        header('Location: '.$target_url);
                    }
                } else {
                    header('Location: '.$redirect_url);
                }
            }

            return $key;
        }
    }

    // Abilita cache mysql (default false)
    public function enable_cache($enable = true){
        $this->mysqlcache = $enable;

    }

    // Ritorna informazioni di debug
    public function debug($enable = false, $trace_level = -1, $plain_text = false){
        global $site;
        $this->debug = $enable;
        $this->trace_level = $trace_level;
        $this->debug_plaintext = $plain_text;


    }

    public function queryex($q, $params = array(), $fetch = true, $fetchall = true){

        global $site, $config;
        if ($this->debug){
            if(!$this->debug_plaintext && isset($site)){
                $rand_id = random_code(8);
                $site->add_footerhtml("<div class=\"aw_layout sql_".$rand_id."\" id=\"aw_main\" style=\"background-color: #FFFFFF;\">");
                $site->add_footerhtml("<a href=\"javascript:$('.sql_".$rand_id."').hide();\">(x) chiudi</a>");
            }
        }
        /* 
            Versione L.C.16.02

            Esempio di utilizzo:
                $id = 1;
                $array = queryex('select * from table where id = :id', array('id' => (int)$id));

            Ritorno:    Ritorna un array con i risultati della query
            Nota:       PDO tratta tutti i campi "mysql" come stringhe
                        La differenza nel trattamento degli interi influisce solo banche dati non mysql
    
        */
        if ($this->mysqlcache == false && strtolower(substr($q, 0, 7)) == 'select '){
            $q = 'select SQL_NO_CACHE '.substr($q, 7);
        }

        
        if ($this->debug){
            $this->querycount++;
            $start_time = microtime(true);
            $dbg_query = ' '.trim(str_replace(chr(9), '', str_replace("\n", ' ', str_replace("\r", ' ', strtolower($q)))));
            $real_query = $dbg_query;
        }

        $stmt = $this->db->prepare($q);

        if (!empty($params)){
            foreach ($params as $p => $val){
                if (is_int($val)){
                    $stmt->bindValue(':'.$p, $val, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':'.$p, $val, PDO::PARAM_STR);
                }
                if ($this->debug){
                    $dbg_query = str_replace(':'.$p, (!is_int($val) ? '<span style="color:#678C62;font-style:italic">"'.$val.'"</span>' : $val), $dbg_query);
                    $real_query = str_replace(':'.$p, (!is_int($val) ? '"'.$val.'"' : $val), $real_query);
                }
            }
        }
        $stmt->execute();

        ### [PFI] - gestione errore request sql
        $errors = $stmt->errorInfo();
        if (!empty($errors[2]) && $errors[2] <> "(null) [0] (severity 0) [(null)]") {
            trigger_error("SQL<br />".$q.'<br />'.$errors[2], E_USER_ERROR);
            die();
        }

        if ($this->debug){

            $stop_time = microtime(true);
            $time = round((($stop_time-$start_time) * 1000), 2);

            $dbg = Array();
            $dbg_query = str_replace('= ', "<span style=\"color:#fff\">= </span>", $dbg_query);
            $dbg_query = str_replace('(', "<span style=\"color:#fff\">(</span>", $dbg_query);
            $dbg_query = str_replace(')', "<span style=\"color:#fff\">)</span>", $dbg_query);
            $dbg_query = str_replace('< ', "<span style=\"color:#fff\">&lt; </span>", $dbg_query);
            $dbg_query = str_replace(' >', "<span style=\"color:#fff\"> &gt;</span>", $dbg_query);
            $dbg_query = str_replace(' >=', "<span style=\"color:#fff\"> &gt;=</span>", $dbg_query);
            $dbg_query = str_replace('<= ', "<span style=\"color:#fff\">&lt;= </span>", $dbg_query);
            $dbg_query = str_replace(' select ', "\n<span style=\"color:#7799bb\">SELECT</span> ", $dbg_query);
            $dbg_query = str_replace('where ', "\n<span style=\"color:#7799bb\">WHERE</span> ", $dbg_query);
            $dbg_query = str_replace('and ', "\n<span style=\"color:#7799bb\">AND</span> ", $dbg_query);
            $dbg_query = str_replace('or ', "\n<span style=\"color:#7799bb\">OR</span> ", $dbg_query);
            $dbg_query = str_replace('values ', "\n<span style=\"color:#7799bb\">VALUES</span> ", $dbg_query);
            $dbg_query = str_replace(' update ', "\n<span style=\"color:#7799bb\">UPDATE</span> ", $dbg_query);
            $dbg_query = str_replace(' insert ', "\n<span style=\"color:#7799bb\">INSERT</span> ", $dbg_query);
            $dbg_query = str_replace('limit ', "\n<span style=\"color:#7799bb\">LIMIT</span> ", $dbg_query);
            $dbg_query = str_replace('from ', "\n<span style=\"color:#7799bb\">FROM</span> ", $dbg_query);
            $dbg_query = str_replace('count(', "<span style=\"color:#7799bb\">COUNT</span>(", $dbg_query);
            $dbg_query = str_replace('now', "<span style=\"color:#7799bb\">NOW</span>", $dbg_query);
            $dbg_query = str_replace('order by ', "\n<span style=\"color:#7799bb\">ORDER BY</span> ", $dbg_query);
            $dbg_query = str_replace('group by ', "\n<span style=\"color:#7799bb\">GROUP BY</span> ", $dbg_query);
            $dbg_query = str_replace('distinct ', "\n<span style=\"color:#7799bb\">DISTINCT</span> ", $dbg_query);
            $dbg_query = str_replace(' in ', " <span style=\"color:#7799bb\">IN</span> ", $dbg_query);
            $dbg_query = str_replace('sql_no_cache ', "<span style=\"color:#ff9999\">SQL_NO_CACHE</span> ", $dbg_query);

           // print $this->debug_plaintext ? "\n".str_repeat("_", 60)."\n".strip_tags($dbg_query) : $dbg_query;

            $traces = debug_backtrace();
            $arg_items = array();
            $countTrace = 0;
            $q_info = "";

            foreach($traces as $trace){
                if($this->trace_level == -1 || $this->trace_level == $countTrace){
                    foreach($trace["args"] as $arg){
                        array_push($arg_items, is_array($arg) ? 'array['.sizeof($arg).']' : $arg);
                    }
                    $funciton_name = '';
                    if (!empty($trace)) {
                        $impArgs = !empty($args['items']) ? implode(',',$arg_items) : '';
                        $function_name = (!empty($trace['file']) ? basename($trace["file"]) : '')
                                        .(!empty($trace['line']) ? ' ['.$trace["line"].'] ':'')
                                        .(!empty($trace['function']) ? '<i>'.$trace["function"].'('.$impArgs.')</i>' : '');
                    }
                    
                    $q_info = '<a href="#query_'.$stop_time.'" class="label label-'.($time > 80 || $time < 0 ? 'danger' : ($time > 40 ? 'warning' : 'default disabled')).'" style="color:#222">'.$time.' ms</a> <span class="label label-default disabled" style="background:#111;color:#999">'.$function_name.'</span>';

                }

                $countTrace++;
            }


            $describe_str = '';
            if ($time > 40 && $this->slowquerycount < 2){

                $stmt_dbg = $this->db->query('DESCRIBE '.$real_query);

                if ($stmt_dbg === false){
                    //print_r($this->db->errorInfo());
                    $describe_str = '';

                } else {
                    $describe_str .= '<table border="1" bordercolor="#202020" cellpadding="4" style="font-size:11px;margin-top:12px;background:#181818;border:1px solid #282828;color:#999;border-collapse:collapse;width:100%;"><thead style="color:#fff"><tr><th>select_type</th><th>table</th><th>type</th><th>possible_keys</th><th>key</th><th>key_len</th><th>ref</th><th>rows</th><th>Extra</th></tr></thead><tbody>';
                    $rs = $stmt_dbg->fetchAll(PDO::FETCH_ASSOC);
                    if (sizeof($rs) > 0){

                        foreach ($rs as $p) {
                            $describe_str .= '<tr><td>'.$p['select_type'].'</td><td>'.$p['table'].'</td><td>'.$p['type'].'</td><td>'.$p['possible_keys'].'</td><td>'.$p['key'].'</td><td>'.$p['key_len'].'</td><td>'.$p['ref'].'</td><td>'.$p['rows'].'</td><td>'.$p['Extra'].'</td></tr>';
                        }
                    }
                    $describe_str .= '</tbody></table>';
                }



                $this->slowquerycount++;
                if(!$this->debug_plaintext && isset($site)){
                    $site->add_footerhtml('<div style="bottom:'.($this->slowquerycount*24).'px;position:fixed;height:22px;text-align:right;right:20px;">'.$q_info.'</div>');
                }else{

                    $dbg = '<div style="bottom:'.($this->slowquerycount*24).'px;position:fixed;height:22px;text-align:right;right:20px;">'.$q_info.'</div>';
                    print $this->debug_plaintext ? "\n\n".htmlspecialchars_decode(strip_tags($dbg)) : $dbg;
                }


            }

            if(!$this->debug_plaintext && isset($site)){
                $site->add_footerhtml('<a name="query_'.$stop_time.'"></a> <pre class="well gfx">'.$q_info."\n".$dbg_query.$describe_str.'</pre>');
            }else{

                $dbg = '<a name="query_'.$stop_time.'"></a> <pre class="well gfx">'.$q_info."\n".$dbg_query.$describe_str.'</pre>';
                print $this->debug_plaintext ? "\n\n".htmlspecialchars_decode(strip_tags($dbg)) : $dbg;
            }

        }

        if ($this->debug){
            if(!$this->debug_plaintext && isset($site)){
                $site->add_footerhtml("</div>");
            }
        }

        if ($fetch){
            if($fetchall){
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $stmt;
        }

    }

    public function insert_id(){

        return $this->db->lastInsertId();

    }

    

    public function bind($param,$value,$data_type = PDO::PARAM_STR,$dbo_length = 0){

            if($dbo_length>0){
               return $this->stmt->bindParam($param,  $value, $this->getDataType($data_type), $dbo_length);
            }else{
               return $this->stmt->bindParam($param,  $value, $this->getDataType($data_type));
            }

    }

    # ritorna il numero di righe ritornate da una query
    public function row_count() {
            return  $this->stmt->rowCount();
    }
    # ritorna true se la query ha prodotto risultati
    public function row_exists() {
            if($this->row_count()>0){
                return 1;
            }

        return 0;
    }

    #type indica il tipo di dato che si vuole ritornare
    public function ex($return = "null"){

        $this->stmt->execute();

        if($return == "rows"){
            return $this->get_rows();
        }
    }

    #ritorna le rows della query
    public function get_rows(){
            if(!$this->rows){
                 $this->rows = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
            }
       return $this->rows;
    }
}
?>