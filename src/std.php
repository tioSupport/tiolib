<?php 
namespace tiolib;


function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

// salvo e recupero le variabili (get)
function checkSearch($var, $default = '') {
    $res = $default;
    
    // GET
    $app = !empty($_GET['app']) ? $_GET['app'] : '';
    $nav = !empty($_GET['nav']) ? $_GET['nav'] : '';
    $val = isset($_GET[$var]) ? $_GET[$var] : '##$$##'; // ##$$## = tag controllo empty

    // POST
    $app = !empty($_POST['app']) ? $_POST['app'] : $app;
    $nav = !empty($_POST['nav']) ? $_POST['nav'] : $nav;
    $val = isset($_POST[$var]) ? $_POST[$var] : $val;


    if (empty($_GET['resetSearch'])) { //se reseatSearch = 1 non tirare fuori da sessione
        $searchSession = !empty($_SESSION['search'][$app][$nav]) ? $_SESSION['search'][$app][$nav] : array();
        if(isset($searchSession[$var])){
            $res = $searchSession[$var];
        }
    }

    if($val != '##$$##'){ //se diverso da tag empty setta il nuovo valore
        $res = $val;
    }

    $_SESSION['search'][$app][$nav][$var] = $res;

    return $res;
}



function send_email($toemail,$subject,$cosa,$fromEmail='',$priority=1) {
    global $config,$this_app;
    
    $fromEmail = empty($fromEmail) ? $config['outbox']['email_send_from'] : $fromEmail;

    $body = '';
    $body .= '<div style="background-color:#293248;width:100%;height:60px;padding-top:15px;padding-bottom:15px;">
                <table style="margin-left:50px;">
                    <tr><td><img alt="" src="https://media.tio.ch/static/img/loghi/tio_logo_sito.png" height="44"></td></tr>
                </table>
            </div>
            <div style="background-color:#EEEEEE;padding:5px;">
                <div style="background-color:#FFFFFF;padding:15px;">'.$cosa.'</div>
                <div style="color:#2E5A92;text-align:center;margin-top:15px;font-size:12px;">&copy; Tutti i diritti riservati <a href="'.$config['server_protocol'].'://'.$config['server_hostname'].'/" target="_new">'.$config['server_protocol'].'://'.$config['server_hostname'].'</a></div>
            </div>';  

    $outbox = new outbox($config['outbox_api'], $config['outbox']['usr'], $config['outbox']['pwd']);
    $to_arr = explode(';',$toemail);
    $email_recipient = array();
    if (empty($to_arr)) {
        if (!empty($toemail)) {
            array_push($email_recipient, array('name' => $toemail, 'addr' => $toemail));
        }
    } else {
        foreach ($to_arr as $send_to) {
            if (!empty($send_to)) {
                array_push($email_recipient, array('name' => $send_to, 'addr' => $send_to));
            }
        }
    }
    // prioritario
    $outbox->message($config['outbox']['service'], $email_recipient, $subject, $body,$fromEmail,array(),'',$priority);
    
    $this_app['outbox']['status'] = $outbox->commit();  
    if (empty($this_app['outbox']['status']) || $this_app['outbox']['status'] <> 'error') {
        return true;
    } else {
        return false;
    }    
    
}


function send_sms($originator, $number, $text, $priority=1) {

    global $config,$this_app;
    
    $body='';
    $fromEmail = empty($originator) ? 'ticinonline' : $originator;

    $subject = $text;

    $outbox = new outbox($config['outbox_api'], $config['outbox']['usr'], $config['outbox']['pwd']);
    $to_arr = explode(';',$number);
    $number_recipient = array();
    if (empty($to_arr)) {
        if (!empty($tonumber)) {
            array_push($number_recipient, array('name' => $tonumber, 'addr' => $tonumber));
        }
    } else {
        foreach ($to_arr as $send_to) {
            if (!empty($send_to)) {
                array_push($number_recipient, array('name' => $send_to, 'addr' => $send_to));
            }
        }
    }
    // prioritario
    $outbox->message("sms", $number_recipient, $subject, $body,$fromEmail,array(),'',$priority);
    
    $this_app['outbox']['status'] = $outbox->commit();  
    if (empty($this_app['outbox']['status']) || $this_app['outbox']['status'] <> 'error') {
        return true;
    } else {
        return false;
    }    
    
}



function arrayOrderById($oldArr,$keyName='id') {

  
  $arr = array();
  foreach ($oldArr as $row) {
    if (isset($row[$keyName])) {
      $arr[$row[$keyName]] = $row;
    }
  }

  return $arr;

}

function ajaxEchojson($dati) {
    
    $jsonp = json_encode($dati);
    header('Content-Type: application/json');
    echo $jsonp;      
    die();
}

function hex2rgb($hex) {
   $hex = str_replace("#", "", $hex);

   if(strlen($hex) == 3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
   } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
   }
   $rgb = array($r, $g, $b);
   //return implode(",", $rgb); // returns the rgb values separated by commas
   return $rgb; // returns an array with the rgb values
}

function urlExists($file) {
    $file_headers = @get_headers($file);
    
    if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
        return false;
    }
    return true;
}

function is_url_exist($url){
    $ch = curl_init($url);    
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // no ssl check
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // no ssl check
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if($code == 200){
       
       $status = true;
    }else{

      $status = false;
    }
    curl_close($ch);
   return $status;
}
 

function formatcurrency($num,$len=2,$round=false) {
    if ($round) {

        return roundscurrency(number_format((float)$num, $len, '.', ''));
    } else {
        return number_format((float)$num, $len, '.', '');
    }
}

function createsignature($hashstring='',$secretkey='ADBpfi52') {
    $signature = hash_hmac("sha1",$hashstring,$secretkey,true);
    return str_replace('+','q',base64_encode($signature)); // encode 64 binary - senza +
}

function generateRandomString($length = 10) {
  $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
  }
  return $randomString;
}

/*File get contents accetta https*/
function url_get_contents($Url,$dataPost=array()) {
  global $curl;
  if (!function_exists('curl_init')){ 
      die('CURL is not installed!');
  }
  
  //$Url = 'http://404.php.net/';

  $curl = array();
  $curl['url'] = $Url;
  $curl['error'] = '';
  $curl['output'] = '';

  $ch = curl_init();
  
  curl_setopt($ch, CURLOPT_URL, $Url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // no ssl check
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // no ssl check
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

  if (!empty($dataPost)) {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataPost);
  }
  $curl['output'] = curl_exec($ch);
  if($curl['output'] === false) {
    $curl['error'] = 'Curl error: ' . curl_error($ch);
  }


  ////$output = curl_exec($ch);

  curl_close($ch);
  return $curl['output'];
}



function timeAgo($time, $shorthand = true){

        /*
            http://stackoverflow.com/questions/2915864
            Adattato per multilingua [LC]

        */

        $time = time() - $time;
        $time = ($time<1)? 1 : $time;

        if($shorthand){
            $tokens = array (
                31536000 => array('anno','anni'),
                2592000 => array('mese','mesi'),
                604800 => array('sett','sett'),
                86400 => array('gior','gior'),
                3600 => array('ora','ore'),
                60 => array('min','min'),
                1 => array('sec','sec')
            );

        }else{
            $tokens = array (
                31536000 => array('anno','anni'),
                2592000 => array('mese','mesi'),
                604800 => array('settimana','settimane'),
                86400 => array('giorno','giorni'),
                3600 => array('ora','ore'),
                60 => array('minuto','minuti'),
                1 => array('secondo','secondi')
            );
        }

        foreach ($tokens as $unit => $text) {

            if ($time < $unit){
                continue;
            }
            $numberOfUnits = floor($time / $unit);

            return $numberOfUnits.' '.($numberOfUnits > 1 ? $text[1] : $text[0]);


        }

}

function save_to($url, $path){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if($httpCode == 200) {
       if (file_exists($path)) {
            unlink($path);
        }
        $fp = fopen($path, 'x');
        fwrite($fp, $raw);
        fclose($fp);
    }


 return $httpCode;
}

function cms_curl($json_data, $url, $timeout = 30000, $headers = null, $method = 'get'){

    if (!empty($url)){
        $ch = curl_init();


        if($method == 'post'){
            curl_setopt($ch, CURLOPT_POST, 1);
            if(!empty($json_data)){
                curl_setopt($ch, CURLOPT_POSTFIELDS,$json_data);
            }
        }

        if($method == 'get' && !empty($json_data)){
                $query = http_build_query($json_data);
                if(strpos('?',$url) !== FALSE){
                    $url .= '&'.$query;
                }else{
                    $url .= '?'.$query;
                }
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING , "gzip");
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // no ssl check
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // no ssl check

        if(!empty($headers)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $server_output = curl_exec($ch);

        curl_close($ch);
    }

    return $server_output;
}


function json_post($json_data, $url, $timeout = 30000, $headers = null){

    $server_output = '';

    if (!empty($json_data) && !empty($url)){
       $server_output = cms_curl($json_data, $url, $timeout, $headers, 'post');
    }
    return $server_output;
}

function std_page($max_items, $buttons = false, $items_count = -1){
    $out  = '';
    if ($buttons == false){
        $page = !empty($_REQUEST['limit_start']) ? $_REQUEST['limit_start'] : 0;
        $page = is_numeric($page) && $page > 0 ? $page : 0;
        $out = $page.','.$max_items;
        $GLOBALS['std_page_limit_start'] = $page;
    } else {
        $page = 0;
        if (!empty($GLOBALS['std_page_limit_start'])){
            $page = $GLOBALS['std_page_limit_start'];
        }
        /*if (!empty($GLOBALS['std_page_limit'])){
            $out = input_hidden('limit_start', $page, 'id="limit_start"');
        }*/

        $left_params = '';
        $right_params = '';
        $center_params = '';
        if ($items_count >= 0){
            if ($items_count < $max_items){
                $right_params = 'disabled="disabled"';
            }

        }
        if ($page == 0){
            $center_params = 'disabled="disabled"';
            $left_params = 'disabled="disabled"';
        }

        $out = input_hidden('limit_start', $page, 'id="limit_start"');
        $out .= input_button('&lt;&lt;', $left_params.' onclick="gd(\'limit_start\').value=parseInt(gd(\'limit_start\').value)-'.$max_items.';this.form.submit();"');
        $out .= input_button(round($page / $max_items)+1, $center_params.' onclick="gd(\'limit_start\').value=0;this.form.submit();"');
        $out .= input_button('&gt;&gt;', $right_params.' onclick="gd(\'limit_start\').value=parseInt(gd(\'limit_start\').value)+'.$max_items.';this.form.submit();"');
        $GLOBALS['std_page_limit'] = true;
    }
    return $out;
}

// Elemento Grafico - Contenitore standard
function std_box($title = '', $content = '',$class='' , $titleClass='', $boxClass=''){
    $i = '<div class="std_box '.$boxClass.'">';
    if ($title != ''){
        $i .= '<div class="std_box_title '.$titleClass.'">';
        $i .= $title; 
        $i .= '</div>';
    }
    if ($content != ''){
        $i .= '<div class="std_box_content '.$class.'">';
        $i .= $content;
        $i .= '</div>';
    }
    $i .= '</div>';

    return $i;
}

function hash_color($seed, $lighten = 100){
    $r = substr(preg_replace("/[^0-9]+/","",sha1($seed)), 0, 2); 
    $g = substr(preg_replace("/[^0-9]+/","",sha1($seed)), 3, 2); 
    $b = substr(preg_replace("/[^0-9]+/","",sha1($seed)), 5, 2); 
    $c = $lighten;
    $clr = RGBToHex($r+$c, $g+$c, $b+$c);
    return $clr;
}

function RGBToHex($r, $g, $b) {
    //String padding bug found and the solution put forth by Pete Williams (http://snipplr.com/users/PeteW)
    $hex = "#";
    $hex.= str_pad(dechex($r), 2, "0", STR_PAD_LEFT);
    $hex.= str_pad(dechex($g), 2, "0", STR_PAD_LEFT);
    $hex.= str_pad(dechex($b), 2, "0", STR_PAD_LEFT);
 
return $hex;
}

function random_color(){
    mt_srand((double)microtime()*1000000);
    $c = '';
    while(strlen($c)<6){
        $c .= sprintf("%02X", mt_rand(0, 255));
    }
    return $c;
}

function call_api($method, $url, $data = false , $timeout = 5000)
{
    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    //curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    //curl_setopt($curl, CURLOPT_USERPWD, "username:password");

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // no ssl check
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // no ssl check
 
    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}

// Messaggio standard
// Passare il testo e il tipo di messaggio: 'error' (default), 'info', 'warning'
function std_error($content = '', $type = 'error'){
    // type = error | info | warning
    $rid = random_code(8);
    $i = '<div id="std_'.$type.'_'.$rid.'" class="std_'.$type.'">';
    if ($content != ''){
        $i .= '<div class="std_'.$type.'_content">';
        $i .= $content;
        $i .= '<a href="javascript:void(0);" onclick="document.getElementById(\'std_'.$type.'_'.$rid.'\').style.display = \'none\'">X</a>';
        $i .= '</div>';
    }
    $i .= '</div>';

    return $i;
}

// Trasformazione dei BBCodes
function bbparse($s){
    if (stristr($s,'[cod]') && stristr($s,'[/cod]')){
        $s = str_replace('[cod]','<div class="cod">',$s);
        $s = str_replace('[/cod]','</div>',$s);
    }
    if (stristr($s,'[hi]') && stristr($s,'[/hi]')){
        $s = str_replace('[hi]','<div class="hi">',$s);
        $s = str_replace('[/hi]','</div>',$s);
    }
    if (stristr($s,'[b]') && stristr($s,'[/b]')){
        $s = str_replace('[b]','<b>',$s);
        $s = str_replace('[/b]','</b>',$s);
    }
    return $s;
}

// Rende attivi i link del dato testo
function hyperlink($text, $blank = false) {
    $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";

    if(preg_match($reg_exUrl, $text, $url)) {
           return preg_replace($reg_exUrl, "<a href=\"{$url[0]}\" ".($blank ? 'target="_blank"' : '').">{$url[0]}</a> ", $text);
    } else {
           return $text;
    }
}

function hypermail($text)
{
    $regex = '/(\S+@\S+\.\S+)/';
    $replace = '<a href="mailto:$1">$1</a>';

    return preg_replace($regex, $replace, $text);
}

// Elemento Grafico doppia riga
function std_topline($color = '#214156'){
    $i = '<div style="height:3px;border-top:8px solid '.$color.';border-bottom:1px solid '.$color.'"><!-- ## --></div>';
    return $i;
}

// Interfaccia base per le applicazioni (esclude header e footer)
function std_interface($title, $content, $menu = '', $pre_menu = '', $post_menu = ''){
    $i = '<table style="margin-top:16px;width:100%;border-collapse:collapse;"><tr>';
    if (!empty($menu) && is_array($menu)){
        $i .= '<td style="width:200px;padding:0px;">'.$pre_menu;

        $i .= '<div style="border-top:1px solid #B2ACA0;"><div class="std_menu_title">'.$title.'</div></div>';
        $i .= '<div class="nav_static">';
        foreach ($menu as $m => $l){

            if ($_SERVER['REQUEST_URI'] == html_entity_decode($l)){
                $i .= '<a style="color:#445566;background-image:url(/img/square_color2.gif)" href="'.$l.'">'.$m.'</a>';
            } else {
                $i .= '<a href="'.$l.'">'.$m.'</a>';
            }
        }
        $i .= '</div>'.$post_menu;
        $i .= '</td><td style="width:16px;"><!-- ## --></td>';
    }
    $i .= '<td style="padding:0px;">'.std_topline('#a9a9a9').'<div style="margin-top:3px;">'.$content.'</div></td>';
    $i .= '</tr></table>';

    return $i;
}


// Campo HTML Color TextBox
function input_colorbox($name, $val, $params = ''){
     $out = '<input type="text" name="'.$name.'"  value="'.$val.'" style="width: 80px; margin-right: 20px; padding: 4px; '. (!empty($val) ? 'border-color: '.$val: '') .';" '.$params.' />';

     $out .= '<input type="color" name="'.$name.'_picker" value="'.$val.'"   />';

     $out .='<script>
            $("input[name=\''.$name.'_picker\']").on("input", function(event) {
                $("input[name=\''.$name.'\']").val(this.value);
                $("input[name=\''.$name.'\']").css("border-color",this.value);
            } );
            </script>';


     return $out;
}

// Campo HTML Input Text
function input_text($name, $val, $params = ''){
    return '<input type="text" name="'.$name.'" value="'.$val.'" '.$params.' />';
}

// Campo HTML Input Text con suggerimenti automatici dinamici
function input_text_autosuggest($name, $array_val, $val, $params = ''){
    if (!is_array($array_val)){
        return '';
    } else {
        $rand_dictname = 'autosuggest_'.random_code(8);
        $keywords = '';
        foreach ($array_val as $v){
            $keywords .= $v.',';
        }
        $input = input_hidden($rand_dictname, $keywords, 'id="'.$rand_dictname.'"');
        return $input.'<input onkeyup="autosuggest(event, false, \''.$rand_dictname.'\');" onkeydown="return autosuggest(event,true, \''.$rand_dictname.'\');" onclick="autosuggest(event, false, \''.$rand_dictname.'\');" autocomplete="off" type="text" name="'.$name.'" value="'.$val.'" '.$params.' />';
    }

}

// Campo HTML Input Text speciale per le date (richiede calendar.js, calendar.jpg, calendar.css)
function input_date($name, $val, $format = 'ymd', $separator = '.', $params = ''){
    return '<input type="text" name="'.$name.'" value="'.$val.'" '.$params.' /><img alt="<" style="cursor: pointer; vertical-align: bottom; margin-bottom:2px" src="/img/calendar.jpg" onclick="displayDatePicker(\''.$name.'\', false,\''.$format.'\',\''.$separator.'\');" />';
}

// Campo per immissione ORA in formato mysql "time"
function input_hour_sql($name, $val = '00:00:00', $min_divider = 1){

    $sec = '00';
    $min = '00';
    $hour = '00';

    if ($val != '00:00:00'){
        if (strlen($val) == 8){
            $test = str_replace(':','',$val);
            if (is_numeric($test)){
                $hour = str_pad($test{0}.$test{1}, 2, "0", STR_PAD_LEFT);
                $min = str_pad($test{2}.$test{3}, 2, "0", STR_PAD_LEFT);
            }
        }
    }

    $hours = array();
    $mins = array();

    for ($i = 0; $i < 24; $i++){
        $h = str_pad($i, 2, "0", STR_PAD_LEFT);
        $hours[$h] = $h;
    }
    for ($i = 0; $i < 60; $i++){
        if ($i % $min_divider == 0){
            $m = str_pad($i, 2, "0", STR_PAD_LEFT);
            $mins[$m] = $m;
        }
    }

    $id = random_code(8);
    $out = input_hidden($name, $hour.':'.$min.':'.$sec, 'id="ihs_'.$id.'"');
    $out .= input_select('ihsh_'.$id, $hours, $hour,'id="ihsh_'.$id.'" onchange="document.getElementById(\'ihs_'.$id.'\').value=document.getElementById(\'ihsh_'.$id.'\').value+\':\'+document.getElementById(\'ihsm_'.$id.'\').value+\':00\'" style="width:50px;"');
    $out .= ' '.input_select('ihsm_'.$id, $mins, $min,'id="ihsm_'.$id.'" onchange="document.getElementById(\'ihs_'.$id.'\').value=document.getElementById(\'ihsh_'.$id.'\').value+\':\'+document.getElementById(\'ihsm_'.$id.'\').value+\':00\'" style="width:50px;"');

    return $out;
}

// Campo per immissione date e ora (necessita jquery e plugin datetimepicker)
function input_datetime($name, $format = '', $default_time = '', $date_picker = true, $time_picker = true, $params = '', $rimuovi = false){

    if (empty($format)){
        $format = 'Y-m-d H:i:00';
    }

    $id = random_code(8);

    $out = input_text($name, $default_time, 'id="'.$id.'" '.$params);

    $out .= std_javascript('$(\'#'.$id.'\').datetimepicker({
                    mask:true,
                    defaultDate: '.(!empty($default_time) ? 'new Date('.(strtotime($default_time)*1000).')' : '\'\'').',
                    datepicker:'.($date_picker ? 'true' : 'false').',
                    timepicker:'.($time_picker ? 'true' : 'false').',
                    defaultSelect: false,
                    validateOnBlur: false,
                    scrollMonth : false,
                    scrollInput : false,
                    lang:"it",
                     i18n:{
                      it:{
                        months:["Gennaio","Febbraio","Marzo","Aprile","Maggio","Giugno","Luglio","Agosto","Settembre","Ottobre","Novembre","Dicembre"],
                        dayOfWeek:["Do", "Lu", "Ma", "Me", "Gio", "Ve", "Sa"]
                      }
                     },
                     format:"'.$format.'"
                    })'.(empty($default_time) ? '.val(\'\')' : '.val(\''.$default_time.'\')').';');

    if($rimuovi){
        $out .= " ".input_button('Rimuovi','onclick="$(\'#'.$id.'\').val(\'\')"');
    }

    return $out;
}




// Campo per immissione ORA e DATA in formato mysql "datetime"
function input_datetime_sql($name, $val = '2000-01-01 00:00:00', $min_divider = 1, $hourtext = ' '){

    $year = '2000';
    $month = '01';
    $day = '01';

    $sec = '00';
    $min = '00';
    $hour = '00';

    if ($val != '2000-01-01 00:00:00'){
        // Validazione
        $test = preg_replace('/[^\d]/','',$val);

        if (strlen($test) == 14){


            if (is_numeric($test)){
                $year = $test{0}.$test{1}.$test{2}.$test{3};
                $month = $test{4}.$test{5};
                $day = str_pad($test{6}.$test{7}, 2, "0", STR_PAD_LEFT);
                $hour = str_pad($test{8}.$test{9}, 2, "0", STR_PAD_LEFT);
                $min = str_pad($test{10}.$test{11}, 2, "0", STR_PAD_LEFT);
            }
        }
    }

    $hours = array();
    $mins = array();
    $years = array();
    $months = array();
    $days = array();

    for ($i = 1; $i <= 31; $i++){
        $h = str_pad($i, 2, "0", STR_PAD_LEFT);
        $days[$h] = $h;
    }

    for ($i = 1970; $i < 2050; $i++){
        $years[$i] = $i;
    }

    for ($i = 1; $i <= 12; $i++){
        $h = str_pad($i, 2, "0", STR_PAD_LEFT);
        $months[$h] = $h;
    }

    for ($i = 0; $i < 24; $i++){
        $h = str_pad($i, 2, "0", STR_PAD_LEFT);
        $hours[$h] = $h;
    }
    for ($i = 0; $i < 60; $i++){
        if ($i % $min_divider == 0){
            $m = str_pad($i, 2, "0", STR_PAD_LEFT);
            $mins[$m] = $m;
        }
    }

    $id = random_code(8);
    $out = input_hidden($name, $year.'-'.$month.'-'.$day.' '.$hour.':'.$min.':'.$sec, 'id="dttm_'.$id.'"');
    $js_update = 'document.getElementById(\'dttm_'.$id.'\').value=document.getElementById(\'dtsye_'.$id.'\').value+\'-\'+document.getElementById(\'dtsmo_'.$id.'\').value+\'-\'+document.getElementById(\'dtsda_'.$id.'\').value+\' \'+document.getElementById(\'dtsh_'.$id.'\').value+\':\'+document.getElementById(\'dtsm_'.$id.'\').value+\':00\'';

    $out .= ' '.input_select('dtsda_'.$id, $days, $day,'id="dtsda_'.$id.'" onchange="'.$js_update.'" style="width:48px;"');
    $out .= input_select('dtsmo_'.$id, $months, $month,'id="dtsmo_'.$id.'" onchange="'.$js_update.'" style="width:48px;"');
    $out .= input_select('dtsye_'.$id, $years, $year,'id="dtsye_'.$id.'" onchange="'.$js_update.'" style="width:60px;"');

    $out .= $hourtext.input_select('dtsh_'.$id, $hours, $hour,'id="dtsh_'.$id.'" onchange="'.$js_update.'" style="width:48px;"');
    $out .= input_select('dtsm_'.$id, $mins, $min,'id="dtsm_'.$id.'" onchange="'.$js_update.'" style="width:48px;"');

    return $out;
}

// Campo HTML Input Password
function input_password($name, $val, $params = ''){
    return '<input type="password" name="'.$name.'" value="'.$val.'" '.$params.' />';
}

// Campo HTML Input Checkbox
function input_checkbox($name, $checked = false, $value = '', $params = ''){
    return '<input type="checkbox" name="'.$name.'"'.(!empty($value) ? ' value="'.$value.'"' : '').(!empty($checked) ? ' checked="checked"' : '').' '.$params.' />';
}

// Campo HTML Input Checkbox Modificato, invia sempre e comunque zero o uno
// Richiede checkbox_*.jpg, ideale per l'inserimento automatico sql
function input_checkbool($name, $checked = false, $disabled = false, $params = '', $onclick = ''){

    $id = random_code(8);
    if ($disabled){
        $js = '';
    } else {
        $js = 'var ch_'.$id.'=document.getElementById(\'ch_'.$id.'\');var im_'.$id.'=document.getElementById(\'im_'.$id.'\');if(im_'.$id.'.src.indexOf(\'_0\')>=0){im_'.$id.'.src = im_'.$id.'.src.replace(\'_0\', \'_1\');im_'.$id.'.checked=\'checked\';ch_'.$id.'.value=1;} else {im_'.$id.'.src = im_'.$id.'.src.replace(\'1\', \'0\');im_'.$id.'.checked=\'\';ch_'.$id.'.value=0;};';
    }
    $i = input_hidden($name, $checked ? 1 : 0, 'id="ch_'.$id.'" '.$params );
    $i .= '<img checked="checked" style="margin-top:4px;" id="im_'.$id.'" onclick="'.$js.$onclick.'" src="/img/checkbox_'.($checked ? '1' : '0').($disabled ? '_disabled' : '').'.jpg" alt="0" />';
    return $i;
}

// Campo HTML Input Select
function input_select($name, $array_val, $current = '', $params = ''){
    $i = '<select name="'.$name.'" '.$params.'>';
    foreach($array_val as $key => $val){
        if ($key == $current){
            $sel = ' selected="selected"';
        } else {
            $sel = '';
        }
        $i .= '<option value="'.$key.'"'.$sel.'>'.$val.'</option>';
    }
    $i .= '</select>';

    return $i;
}

// Campo HTML Textarea
function input_textarea($name, $val, $params = ''){
    return '<textarea name="'.$name.'" '.$params.'>'.$val.'</textarea>';
}

// Campo HTML RichTextarea (Necessita TinyMCE >= 4.0)
// $site->add_js($config['public_path'].'/lib/tinymce/tinymce.min.js');
function input_richtextarea($name, $val, $params = '', $statusbar = false){

    $id = random_code(8);

    $out = '<textarea id="'.$id.'" name="'.$name.'" '.$params.'>'.$val.'</textarea>';
    $out .= std_javascript('
        tinyMCE.init({
            selector: "#'.$id.'",
            theme: "modern",
            menubar:false,
            statusbar: '.($statusbar ? 'true' : 'false').',
            width: $("#'.$id.'").width(),
            plugins: ["code","paste","link","table"],
            paste_as_text: true,
            valid_children : "+body[link|style]",
            toolbar: "undo redo | bold italic underline | bullist numlist | link table | code | pastetext | nanospell",
            setup : function(ed) {
              ed.on("init", function(e) {
                ed.getContainer().className += " form-border";
              });
            },
        });
    ');

    return $out;
}
/*
$type:
    1 = immagini (.jpg;.jpeg;.gif;.png;)
    2 = audio (.mp3)
    4 = pdf (.pdf)
    7 = video (.mp4)
*/
function input_file($name, $val, $type = 1, $params = ''){
    global $this_app;
        $out = '<input type="hidden" id="'.$name.'" name="'.$name.'" value="'.$val.'" />';
        $out .= '<a href="'.$val.'" id="a_'.$name.'" target="_blank" style="word-break: break-all; text-decoration: underline;">'.$val.'</a> <a id="close_'.$name.'" onclick="baseRemoveFile(\''.$name.'\');  return false;" href="javascript:void(0);" style="'.(empty($val) ? 'display: none;' : '').'">(x)<br /><br /></a>';
        $out .='<div id="base_progress_'.$name.'" class="base_progressbar"></div>';
        $out .='<input id="f_'.$name.'" type="file" name="f" onchange="base_startUpload(\''.$name.'\', \''.$type.'\');"  value="'.$val.'" '.$params.' />';
        $out .='<div id="base_complete_'.$name.'"></div>';
    return $out;
}

// Campo HTML Input Submit
function input_submit($val, $params = ''){
    return '<input class="button" type="submit" value="'.$val.'" '.$params.' />';
}

// Campo HTML Input Hidden
function input_hidden($name, $val = '', $params = ''){
    return '<input type="hidden" name="'.$name.'" value="'.$val.'" '.$params.' />';
}

// Campo HTML Input Button
function input_button($name, $params = ''){
    return '<input class="button" type="button" value="'.$name.'" '.$params.' />';
}

function std_iframe($src, $w, $h, $params = '', $scrolling = true){
    $s1 = '';
    $s2 = '';
    if ($scrolling == false){
        $s1 = 'scrolling="no"';
        $s2 = 'overflow: hidden;';
    }

    return '<iframe '.$params.' '.$s1.' src="'.$src.'" frameborder="0" border="0" cellspacing="0" style="border-style:none;width:'.$w.'; height:'.$h.';'.$s2.'" ><!-- ## --></iframe>';
}

// Form tab standard
function std_tab_menu($tabs, $selected_id = '', $tools = '', $title = '', $click_callback = '', $styles = array()){
    $callback = '';
    if (!empty($click_callback)){
        $callback = $click_callback;
    }

    $left = '<div class="tabs">';
    $first = 1;
    $i = '';
    foreach($tabs as $tab_id => $tab_text){

        $act = '';
        if ($selected_id == $tab_id || (empty($selected_id) && $first)){
            $act = 'class="act"';
        }
        $left .= '<a '.$callback.' style="'.(!empty($styles[$tab_id]) ? $styles[$tab_id] : '').'" href="#'.$tab_id.'">'.$tab_text.'</a>';
        $first = 0;
    }
    $left .= '</div>';
    $right = $tools;

    $i .= std_box($title, '<div style="float:left">'.$left.'</div><div style="float:right">'.$right.'</div>');

    return $i;
}

// Tab standard
function std_tab($id, $content){
    $i = '<div class="tabs_contents" id="'.$id.'">'.$content.'</div>';
    return $i;
}


// Tabella HTML standard per i form - Apertura
function std_edit_opentable($id = '', $add_form_data = '', $add_toolbar_item = '', $use_std_buttons = true, $action = "", $formparams = "", $overflow = false,$close=true){
    if($action === ""){
        $action = $_SERVER['REQUEST_URI'];
    }
    $buttons = '';
    if ($use_std_buttons || $add_toolbar_item != ''){
        $buttons = '<div style="text-align:right;">';
        $buttons .= $add_toolbar_item;
        if ($use_std_buttons){
            $btnSaveTxt = $close==true ? 'Salva e chiudi' : 'Salva';
            $buttons .= input_submit($btnSaveTxt);
            $buttons .= input_button('Annulla', 'onclick="goback();return false;"');
        }
        $buttons .= '</div>';
    }

    $i = '<form id="form_'.$id.'" method="post" action="'.htmlentities($action).'" '.$formparams.' >'.$add_form_data;
    if ($buttons != ''){
        $i .= std_box('', $buttons);
    }
    if($overflow){
        $i.='<div  style="height: calc(100% - 36px); overflow: auto;">';
    }
    $i .= '<table class="tbl_edit" id="'.$id.'">';
    return $i;
}

// Tabella HTML standard per i form - Riga
function std_edit_row($name, $data = '', $params = ''){
    if (!empty($data)){
        return '<tr '.$params.'><td class="field">'.$name.'</td><td>'.$data.'</td></tr>';
    } else {
        return '<tr '.$params.'><td class="field" colspan="2">'.$name.'</td></tr>';
    }
}

// Tabella HTML standard per i form - Chiusura
function std_edit_closetable($add_toolbar_item = '', $overflow = false){
    $i = '</table>';
     if($overflow){
        $i.='</div>';
    }
    if ($add_toolbar_item != ''){
        $i .= std_box('', $add_toolbar_item);
    }
    $i .= '</form>';
    return $i;
}

// Tabella HTML standard per le liste - Apertura
function std_list_opentable($id, $field_names, $thead = true, $add_class="", $params = ''){
    $i = '<table class="tbl '.$add_class.'" id="'.$id.'" '.$params.'>';
    if ($thead){
        $i .= '<thead><tr>';
        foreach ($field_names as $f){
            $i .= '<th>'.$f.'</th>';
        }
        $i .= '</tr></thead>';
    }
    $i .= '<tbody>';
    return $i;
}

// Tabella HTML standard per le liste - Riga
function std_list_row($id = '', $fields_data, $params = ''){
    $id = $id != '' ? $id : random_code(8);

    $i = '<tr id="'.$id.'" '.$params.'>';
    foreach ($fields_data as $d){
        /*if (strlen($d) > 82){
            $d = substr($d, 0, 80).'..';
        }*/
        $i .= '<td>'.$d.'</td>';
    }
    $i .= '</tr>';
    return $i;
}

// Tabella HTML standard per le liste - Chiusura
function std_list_closetable(){
    return '</tbody></table>';
}

// Inserimento Javascript in pagina
function std_javascript($code){
    return '<script type="text/javascript">'.$code.'</script>';
}

// Ritorna i campi di una tabella mysql
function get_table_fields($sql_inst, $table){
    return $sql_inst->queryex('desc '.$table);
}

// Ritorna un record vuoto basandosi sui campi di una tabella mysql
function generate_empty_record($sql_inst, $table){
    $item = array();
    $fieldnames = get_table_fields($sql_inst, $table);
    foreach ($fieldnames as $f){
        $item[$f['Field']] = '';
    }
    return $item;
}

// Ritorna l'elemento di un array di dimensione 1
function get_unique_record($rs){
    if (sizeof($rs) == 1){
        return $rs[0];
    }
    return false;
}

// Validazione boolean
function istrue($val){
    if (!empty($val) && ($val == '1' || $val == 'on' || $val == 'true' || $val == 'yes' || $val == 'checked')){
        return true;
    } else {
        return false;
    }
}

// FPDF - Generazione pdf standard
function std_output_pdf($title, $item_name, $content, $drawlines = false){

    global $config;

    //include($config['web_root']."/lib/pdf/fpdf.php");

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFillColor(255,255,255);

    $c1 = 20;
    // I Box

    $pdf->SetXY(10, 20);
    $pdf->Cell(190, 9, '', 1);
    $pdf->SetXY(10, 29);
    $pdf->Cell(190, 230, '', 1);

    if ($drawlines){
        $start = 25;
        $r = 5;
        $color = 245;
        for ($row = 1; $row < 46; $row++){
            $pdf->SetFillColor($color,$color,$color);
            $pdf->SetXY(11, $start+$r*$row);
            $pdf->Cell(188, $r, '', 0, 0, 'L', 1);
            $color = $color == 255 ? 245 : 255;
        }

    }

    $pdf->SetFillColor(255,255,255);

    // Testata
    $pdf->SetFont('Arial','B',18);
    $pdf->SetXY(10, 10);
    $pdf->Cell(40,10,'TicinOnline');

    // Footer
    $pdf->SetFont('Arial','',10);
    $pdf->SetXY($c1, 260);
    $pdf->Cell(190, 10, 'Stampato da '.$_SESSION['auth_user']['full_name'].' - '.date('d.m.Y h:i'));

    // Titolo, nome
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial','',13);
    $pdf->SetXY($c1, 23);
    $pdf->Cell(40, 5,$title);

    $pdf->SetTextColor(230, 0, 0);
    $pdf->SetXY(150, 23);
    $pdf->Cell(40, 5, $item_name, 0, 1, 'R', 1);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('courier','',10);
    $pdf->SetXY($c1, 40);
    $pdf->MultiCell(175,5, $content);

    $pdf->Output();
}

// Aggiornamento automatico della banca dati in base al post
function sql_auto_insert($sql_inst, $table, $key = 0, $redirect_url = '', $apicall = ''){
    global $config;

    if (strtolower($_SERVER['REQUEST_METHOD']) == 'post'){

        // Validazione dei campi
        if (trim($key) == ''){
            $key = 0;
        }
        $fieldnames = get_table_fields($sql_inst, $table);
        $insert_fields = array();

        foreach ($fieldnames as $f){

            if (strtolower($f['Key']) == 'pri'){
                $keyfield = (string)$f['Field'];
            } else {

                unset($fdata);
                if (isset($_POST[(string)$f['Field']])){
                    $fdata = $_POST[(string)$f['Field']];

                    if (stristr($f['Type'], 'tinyint') && !is_numeric($fdata)){
                        if ($fdata == 'on' || $fdata == 'true' || $fdata == 'yes' || $fdata == 'checked'){
                            $fdata = 1;
                        }
                    }
                } else {
                    if (stristr($f['Type'], 'tinyint')){
                        $fdata = 0;
                    }

                }

                if (isset($_POST[(string)$f['Field']])){
                    if (isset($fdata) && strlen($fdata) > 0){
                        if (stristr($f['Type'], 'int')){
                            $val = $fdata;
                        } else {
                            $val = '\''.(!empty($GLOBALS['config']['magic_quotes']) && $GLOBALS['config']['magic_quotes'] == true ? $sql_inst->escape($fdata) : $fdata).'\'';
                        }
                    } else {
                        $val = 'null';
                    }
                    $insert_fields[(string)$f['Field']] = $val;
                }
            }
        }

        // Insert / Update
        if (sizeof($insert_fields) > 0){
            if ($key == 0){
                $q = 'INSERT into '.$table.' (';
                foreach ($insert_fields as $f => $v){
                    $q .= $f.',';
                }
                $q = substr($q,0,-1).') values (';
                foreach ($insert_fields as $f => $v){
                    $q .= $v.',';
                }
                $q = substr($q,0,-1).')';
                $sql_inst->query($q);
                $key = $sql_inst->insert_id();
            } else {
                $q = 'UPDATE '.$table.' set ';
                foreach ($insert_fields as $f => $v){
                    $q .= $f.'='.$v.',';
                }
                $q = substr($q,0,-1);
                $q .= ' where '.$keyfield.'='.$key.';';

                $sql_inst->query($q);
            }
            
               // echo $q;

        }


        if(!empty($apicall)){
           url_get_contents('https://'.$config['server_hostname'].'/ext/api.php?'.$apicall);
        }

        if ($redirect_url != ''){
            if ($redirect_url == 'auto'){
                $current_url = $_SERVER['REQUEST_URI'];
                if ($current_url{strlen($current_url)-1} == '0'){
                    $target_url = substr($current_url,0,-1).$sql_inst->insert_id();
                    header('Location: '.$target_url);
                }
            } else {
                header('Location: '.$redirect_url);
            }
        }

        return $key;
    }
}

// Nazioni in lingua italiana (ristrette)
function lista_nazioni_ristrette(){
    return array("ch" => "Svizzera", "it" => "Italia");
}

function lista_nazioni_eu(){
    return array('at' => 'Austria', 'be' => 'Belgio', "dk" => "Danimarca", 'fr' => 'Francia', 'de' => 'Germania', 'uk' => 'Ighilterra', 'it' => 'Italia', "li" => "Liechtenstein", "nl" => "Olanda" ,'es' => 'Spagna', 'ch' => 'Svizzera');
}

// Nazioni in lingua italiana
function lista_nazioni(){
    return array("it" => "Italia", "af" => "Afghanistan", "ax" => "Isole &Aring;land", "al" => "Albania", "dz" => "Algeria", "as" => "Samoa Americane", "ad" => "Andorra", "ao" => "Angola", "ai" => "Anguilla", "aq" => "Antartide", "ag" => "Antigua e Barbuda", "ar" => "Argentina", "am" => "Armenia", "aw" => "Aruba", "au" => "Australia", "at" => "Austria", "az" => "Azerbaigian", "bs" => "Bahamas", "bh" => "Bahrein", "bd" => "Bangladesh", "bb" => "Barbados", "by" => "Bielorussia", "be" => "Belgio", "bz" => "Belize", "bj" => "Benin", "bm" => "Bermuda", "bt" => "Bhutan", "bo" => "Bolivia", "ba" => "Bosnia Erzegovina", "bw" => "Botswana", "bv" => "Isola Bouvet", "br" => "Brasile", "io" => "Territorio Britannico dell'Oceano Indiano (BIOT)", "vg" => "Isole Vergini Britanniche", "bn" => "Brunei", "bg" => "Bulgaria", "bf" => "Burkina Faso", "bi" => "Burundi", "kh" => "Cambogia", "cm" => "Camerun", "ca" => "Canada", "cv" => "Capo Verde", "ky" => "Isole Cayman", "cf" => "Repubblica Centrafricana", "td" => "Ciad", "cl" => "Cile", "cn" => "Cina", "cx" => "Isola di Natale", "cc" => "Isole Cocos (Keeling)", "co" => "Colombia", "km" => "Comore", "cg" => "Congo", "ck" => "Isole Cook", "cr" => "Costa Rica", "hr" => "Croazia", "cy" => "Cipro", "cz" => "Repubblica Ceca", "cd" => "Repubblica Democratica del Congo", "dk" => "Danimarca", "xx" => "Territorio conteso", "dj" => "Gibuti", "dm" => "Dominica", "do" => "Repubblica Dominicana", "tl" => "Timor Est", "ec" => "Ecuador", "eg" => "Egitto", "sv" => "El Salvador", "gq" => "Guinea Equatoriale", "er" => "Eritrea", "ee" => "Estonia", "et" => "Etiopia", "fk" => "Isole Falkland", "fo" => "Isole Faroe", "fm" => "Micronesia, Stati Federati della", "fj" => "Isole Figi", "fi" => "Finlandia", "fr" => "Francia", "gf" => "Guiana Francese", "pf" => "Polinesia Francese", "tf" => "Territori Australi Francesi", "ga" => "Gabon", "gm" => "Gambia", "ge" => "Georgia", "de" => "Germania", "gh" => "Ghana", "gi" => "Gibilterra", "gr" => "Grecia", "gl" => "Groenlandia", "gd" => "Grenada", "gp" => "Guadalupa", "gu" => "Guam", "gt" => "Guatemala", "gn" => "Guinea", "gw" => "Guinea-Bissau", "gy" => "Guyana", "ht" => "Haiti", "hm" => "Isole Heard e McDonald", "hn" => "Honduras", "hk" => "Hong Kong", "hu" => "Ungheria", "is" => "Islanda", "in" => "India", "id" => "Indonesia", "iq" => "Iraq", "xe" => "Zona neutra Iraq-Arabia Saudita", "ie" => "Irlanda", "il" => "Israele", "it" => "Italia", "ci" => "Costa d'Avorio", "jm" => "Giamaica", "jp" => "Giappone", "jo" => "Giordania", "kz" => "Kazakistan", "ke" => "Kenya", "ki" => "Kiribati", "kw" => "Kuwait", "kg" => "Kirghizistan", "la" => "Laos", "lv" => "Lettonia", "lb" => "Libano", "ls" => "Lesotho", "lr" => "Liberia", "ly" => "Libia", "li" => "Liechtenstein", "lt" => "Lituania", "lu" => "Lussemburgo", "mo" => "Macau", "mk" => "Macedonia", "mg" => "Madagascar", "mw" => "Malawi", "my" => "Malesia", "mv" => "Maldive", "ml" => "Mali", "mt" => "Malta", "mh" => "Isole Marshall", "mq" => "Martinica", "mr" => "Mauritania", "mu" => "Mauritius", "yt" => "Mayotte", "mx" => "Messico", "md" => "Moldova", "mc" => "Monaco", "mn" => "Mongolia", "ms" => "Montserrat", "ma" => "Marocco", "mz" => "Mozambico", "mm" => "Myanmar (Birmania)", "na" => "Namibia", "nr" => "Nauru", "np" => "Nepal", "nl" => "Paesi Bassi", "an" => "Antille Olandesi", "nc" => "Nuova Caledonia", "nz" => "Nuova Zelanda", "ni" => "Nicaragua", "ne" => "Niger", "ng" => "Nigeria", "nu" => "Niue", "nf" => "Isola Norfolk", "kp" => "Corea del Nord", "mp" => "Isole Marianne Settentrionali", "no" => "Norvegia", "om" => "Oman", "pk" => "Pakistan", "pw" => "Palau", "ps" => "Territori Occupati della Palestina",
"pa" => "Panama", "pg" => "Papua Nuova Guinea", "py" => "Paraguay", "pe" => "Peru", "ph" => "Filippine", "pn" => "Isola Pitcairn", "pl" => "Polonia", "pt" => "Portogallo", "pr" => "Puerto Rico", "qa" => "Qatar", "re" => "Reunion", "ro" => "Romania", "ru" => "Russia", "rw" => "Ruanda", "sh" => "Sant'Elena", "kn" => "St. Kitts e Nevis", "lc" => "St. Lucia", "pm" => "St. Pierre e Miquelon", "vc" => "St. Vincent e Grenadine", "ws" => "Samoa", "sm" => "San Marino", "st" => "Sao Tome e Principe", "sa" => "Arabia Saudita", "sn" => "Senegal", "cs" => "Serbia e Montenegro", "sc" => "Seychelles", "sl" => "Sierra Leone", "sg" => "Singapore", "sk" => "Slovacchia", "si" => "Slovenia", "sb" => "Isole Solomon", "so" => "Somalia", "za" => "Sud Africa", "gs" => "Isole Georgia del Sud e Sandwich meridionali", "kr" => "Corea del Sud", "es" => "Spagna", "pi" => "Isole Spratly", "lk" => "Sri Lanka", "sr" => "Suriname", "sj" => "Isole Svalbard e Jan Mayen", "sz" => "Swaziland", "se" => "Svezia", "ch" => "Svizzera", "sy" => "Siria", "tw" => "Taiwan", "tj" => "Tagikistan", "tz" => "Tanzania", "th" => "Thailandia", "tg" => "Togo", "tk" => "Isole Tokelau", "to" => "Tonga", "tt" => "Trinidad e Tobago", "tn" => "Tunisia", "tr" => "Turchia", "tm" => "Turkmenistan", "tc" => "Isole Turks e Caicos", "tv" => "Tuvalu", "ug" => "Uganda", "ua" => "Ucraina", "ae" => "Emirati Arabi Uniti", "uk" => "Regno Unito", "xd" => "Zona neutra ONU", "us" => "Stati Uniti", "um" => "Isole minori degli Stati Uniti", "uy" => "Uruguay", "vi" => "Isole Vergini Statunitensi", "uz" => "Uzbekistan", "vu" => "Vanuatu", "va" => "Citta&agrave; del Vaticano", "ve" => "Venezuela", "vn" => "Vietnam", "wf" => "Isole Wallis e Futuna", "eh" => "Sahara Occidentale", "ye" => "Yemen", "zm" => "Zambia", "zw" => "Zimbabwe");
}

// Nome della nazione in italiano in base al prefisso (ch)
function nome_nazione($prefix){
    $nazioni = lista_nazioni();
    foreach ($nazioni as $p => $n){
        if ($p == $prefix){
            return $n;
        }
    }
    return '';
}

// Nazioni in lingua inglese
function country_list(){
    return array("us"=> "United States", "uk"=> "United Kingdom", "af"=> "Afghanistan", "ax"=> "Aland Islands", "al"=> "Albania", "dz"=> "Algeria", "as"=> "American Samoa", "ad"=> "Andorra", "ao"=> "Angola", "ai"=> "Anguilla", "aq"=> "Antarctica", "ag"=> "Antigua and Barbuda", "ar"=> "Argentina", "am"=> "Armenia", "aw"=> "Aruba", "au"=> "Australia", "at"=> "Austria", "az"=> "Azerbaijan", "bs"=> "Bahamas", "bh"=> "Bahrain", "bd"=> "Bangladesh", "bb"=> "Barbados", "by"=> "Belarus", "be"=> "Belgium", "bz"=> "Belize", "bj"=> "Benin", "bm"=> "Bermuda", "bt"=> "Bhutan", "bo"=> "Bolivia", "ba"=> "Bosnia and Herzegovina", "bw"=> "Botswana", "bv"=> "Bouvet Island", "br"=> "Brazil", "io"=> "British Indian Ocean Territory", "vg"=> "British Virgin Islands", "bn"=> "Brunei", "bg"=> "Bulgaria", "bf"=> "Burkina Faso", "bi"=> "Burundi", "kh"=> "Cambodia", "cm"=> "Cameroon", "ca"=> "Canada", "cv"=> "Cape Verde", "ky"=> "Cayman Islands", "cf"=> "Central African Republic", "td"=> "Chad", "cl"=> "Chile", "cn"=> "China", "cx"=> "Christmas Island", "cc"=> "Cocos (Keeling) Islands", "co"=> "Colombia", "km"=> "Comoros", "cg"=> "Congo", "ck"=> "Cook Islands", "cr"=> "Costa Rica", "hr"=> "Croatia", "cy"=> "Cyprus", "cz"=> "Czech Republic", "cd"=> "Democratic Republic of Congo", "dk"=> "Denmark", "xx"=> "Disputed Territory", "dj"=> "Djibouti", "dm"=> "Dominica", "do"=> "Dominican Republic", "tl"=> "East Timor", "ec"=> "Ecuador", "eg"=> "Egypt", "sv"=> "El Salvador", "gq"=> "Equatorial Guinea", "er"=> "Eritrea", "ee"=> "Estonia", "et"=> "Ethiopia", "fk"=> "Falkland Islands", "fo"=> "Faroe Islands", "fm"=> "Federated States of Micronesia", "fj"=> "Fiji", "fi"=> "Finland", "fr"=> "France", "gf"=> "French Guyana", "pf"=> "French Polynesia", "tf"=> "French Southern Territories", "ga"=> "Gabon", "gm"=> "Gambia", "ge"=> "Georgia", "de"=> "Germany", "gh"=> "Ghana", "gi"=> "Gibraltar", "gr"=> "Greece", "gl"=> "Greenland", "gd"=> "Grenada", "gp"=> "Guadeloupe", "gu"=> "Guam", "gt"=> "Guatemala", "gn"=> "Guinea", "gw"=> "Guinea-Bissau", "gy"=> "Guyana", "ht"=> "Haiti", "hm"=> "Heard Island and Mcdonald Islands", "hn"=> "Honduras", "hk"=> "Hong Kong", "hu"=> "Hungary", "is"=> "Iceland", "in"=> "India", "id"=> "Indonesia", "iq"=> "Iraq", "xe"=> "Iraq-Saudi Arabia Neutral Zone", "ie"=> "Ireland", "il"=> "Israel", "it" => "Italy", "ci"=> "Ivory Coast", "jm"=> "Jamaica", "jp"=> "Japan", "jo"=> "Jordan", "kz"=> "Kazakhstan", "ke"=> "Kenya", "ki"=> "Kiribati", "kw"=> "Kuwait", "kg"=> "Kyrgyzstan", "la"=> "Laos", "lv"=> "Latvia", "lb"=> "Lebanon", "ls"=> "Lesotho", "lr"=> "Liberia", "ly"=> "Libya", "li"=> "Liechtenstein", "lt"=> "Lithuania", "lu"=> "Luxembourg", "mo"=> "Macau", "mk"=> "Macedonia", "mg"=> "Madagascar", "mw"=> "Malawi", "my"=> "Malaysia", "mv"=> "Maldives", "ml"=> "Mali", "mt"=> "Malta", "mh"=> "Marshall Islands", "mq"=> "Martinique", "mr"=> "Mauritania", "mu"=> "Mauritius", "yt"=> "Mayotte", "mx"=> "Mexico", "md"=> "Moldova", "mc"=> "Monaco", "mn"=> "Mongolia", "ms"=> "Montserrat", "ma"=> "Morocco", "mz"=> "Mozambique", "mm"=> "Myanmar", "na"=> "Namibia", "nr"=> "Nauru", "np"=> "Nepal", "nl"=> "Netherlands", "an"=> "Netherlands Antilles", "nc"=> "New Caledonia", "nz"=> "New Zealand", "ni"=> "Nicaragua", "ne"=> "Niger", "ng"=> "Nigeria", "nu"=> "Niue", "nf"=> "Norfolk Island", "kp"=> "North Korea", "mp"=> "Northern Mariana Islands", "no"=> "Norway", "om"=> "Oman", "pk"=> "Pakistan", "pw"=> "Palau", "ps"=> "Palestinian Occupied Territories", "pa"=> "Panama", "pg"=> "Papua New Guinea", "py"=> "Paraguay", "pe"=> "Peru", "ph"=> "Philippines", "pn"=> "Pitcairn Islands", "pl"=> "Poland", "pt"=> "Portugal", "pr"=> "Puerto Rico",
"qa"=> "Qatar", "re"=> "Reunion", "ro"=> "Romania", "ru"=> "Russia", "rw"=> "Rwanda", "sh"=> "Saint Helena and Dependencies", "kn"=> "Saint Kitts and Nevis", "lc"=> "Saint Lucia", "pm"=> "Saint Pierre and Miquelon", "vc"=> "Saint Vincent and the Grenadines", "ws"=> "Samoa", "sm"=> "San Marino", "st"=> "Sao Tome and Principe", "sa"=> "Saudi Arabia", "sn"=> "Senegal", "cs"=> "Serbia and Montenegro", "sc"=> "Seychelles", "sl"=> "Sierra Leone", "sg"=> "Singapore", "sk"=> "Slovakia", "si"=> "Slovenia", "sb"=> "Solomon Islands", "so"=> "Somalia", "za"=> "South Africa", "gs"=> "South Georgia and South Sandwich Islands", "kr"=> "South Korea", "es"=> "Spain", "pi"=> "Spratly Islands", "lk"=> "Sri Lanka", "sr"=> "Suriname", "sj"=> "Svalbard and Jan Mayen", "sz"=> "Swaziland", "se"=> "Sweden", "ch"=> "Switzerland", "sy"=> "Syria", "tw"=> "Taiwan", "tj"=> "Tajikistan", "tz"=> "Tanzania", "th"=> "Thailand", "tg"=> "Togo", "tk"=> "Tokelau", "to"=> "Tonga", "tt"=> "Trinidad and Tobago", "tn"=> "Tunisia", "tr"=> "Turkey", "tm"=> "Turkmenistan", "tc"=> "Turks And Caicos Islands", "tv"=> "Tuvalu", "ug"=> "Uganda", "ua"=> "Ukraine", "ae"=> "United Arab Emirates", "uk"=> "United Kingdom", "xd"=> "United Nations Neutral Zone", "us"=> "United States", "um"=> "United States Minor Outlying Islands", "uy"=> "Uruguay", "vi"=> "US Virgin Islands", "uz"=> "Uzbekistan", "vu"=> "Vanuatu", "va"=> "Vatican City", "ve"=> "Venezuela", "vn"=> "Vietnam", "wf"=> "Wallis and Futuna", "eh"=> "Western Sahara", "ye"=> "Yemen", "zm"=> "Zambia", "zw"=> "Zimbabwe");
}

// Nome della nazione in inglese in base al prefisso (ch)
function country_name($prefix){
    $countries = lista_nazioni();
    foreach ($countries as $p => $n){
        if ($p == $prefix){
            return $n;
        }
    }
    return '';
}

function mssqlHumanDate($dd) {
    if ($dd>'') {
        $dd = trim(substr($dd,0,11));
        $da = split('[-]',$dd);
        $dd = $da[2].'.'.$da[1].'.'.$da[0];

    }
    return $dd;
}

function mssqlLogicDate($dd) {
    if ($dd>'') {
        $dd = trim(substr($dd,0,11));
        $da = split('[-]',$dd);

        $dd = $da[0].$da[1].$da[2];

    }
    return $dd;
}

function sqlTextDate($mysqldate, $show_time = false, $show_date = true, $forcefull = false){
    $sec_diff = (time() - strtotime($mysqldate));

    if (!$show_time && $show_date){
        $dd = date("j",strtotime($mysqldate));
        $mm = strtolower(mmtostring(date("n",strtotime($mysqldate)), true));
        $yy = date("Y",strtotime($mysqldate));

        return $dd.' '.$mm.' '.$yy;

    } else {

        if ($sec_diff >= 3600 or $forcefull){

            $hs = date("H:i",strtotime($mysqldate));
            $dd = date("j",strtotime($mysqldate));
            $mm = strtolower(mmtostring(date("n",strtotime($mysqldate)), true));
            $yy = date("Y",strtotime($mysqldate));

            $dayconf = date('Ymd',strtotime($mysqldate));
            $today = date('Ymd');

            //changelog: 20100204 - if ($dd != date("j")){
            if ($dayconf != $today) {
                return $dd.' '.$mm.' '.$yy .($show_time ? ' '.$hs : '');
            } else {
                return ($show_date ? $dd.' '.$mm.' '.$yy .' ' : '').$hs;
            }

        } else {
            $min_ago = round($sec_diff/60);
            if ($min_ago < 1){
                $min_ago = 1;
            }
            $min_ago = $min_ago.' min';
            return $min_ago;

        }

    }
}

function get_file_date($file_name){
    $last_modified = filemtime($file_name);
    $filedate = date("Y-m-d H:i:s", $last_modified);
    $filedate = sqlTextDate($filedate, true);
    return $filedate;

}

function perms_info($fname){
    $perms = fileperms($fname);

    if (($perms & 0xC000) == 0xC000) {
        // Socket
        $info = 's';
    } elseif (($perms & 0xA000) == 0xA000) {
        // Symbolic Link
        $info = 'l';
    } elseif (($perms & 0x8000) == 0x8000) {
        // Regular
        $info = '-';
    } elseif (($perms & 0x6000) == 0x6000) {
        // Block special
        $info = 'b';
    } elseif (($perms & 0x4000) == 0x4000) {
        // Directory
        $info = 'd';
    } elseif (($perms & 0x2000) == 0x2000) {
        // Character special
        $info = 'c';
    } elseif (($perms & 0x1000) == 0x1000) {
        // FIFO pipe
        $info = 'p';
    } else {
        // Unknown
        $info = 'u';
    }

    // Owner
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ?
                (($perms & 0x0800) ? 's' : 'x' ) :
                (($perms & 0x0800) ? 'S' : '-'));

    // Group
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ?
                (($perms & 0x0400) ? 's' : 'x' ) :
                (($perms & 0x0400) ? 'S' : '-'));

    // World
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ?
                (($perms & 0x0200) ? 't' : 'x' ) :
                (($perms & 0x0200) ? 'T' : '-'));
    return $info;
}

function list_files($fold, $sort = false){

    function cmp($a, $b) {
      if (!isset($_GET['sort']) && !isset($_GET['inv'])){
          return strcmp($b["date"], $a["date"]);
      } else {
        if ($_GET['sort'] == "date"){
            if ($_GET['inv'] == "0")
                return strcmp($a["date"], $b["date"]);
            else
                return strcmp($b["date"], $a["date"]);
        } else {
            if ($_GET['inv'] == "0")
                return strcmp($a["name"], $b["name"]);
            else
                return strcmp($b["name"], $a["name"]);
        }
      }
    }

    $filearray = array();
    if ($handle = opendir($fold)) {
        $file_count = 0;
        while (false !== ($file_name = readdir($handle))) {
            if ($file_name != '.' && $file_name != '..'){
              $filearray[$file_count]['name'] = $file_name;
              $filearray[$file_count]['date'] = filemtime($fold.$file_name);
              $filearray[$file_count]['ext'] = strtolower(substr(strrchr($file_name,"."),1));
              $filearray[$file_count]['time'] = get_file_date($fold.$file_name);
              $filearray[$file_count]['size'] = filesize($fold.$file_name);
              $filearray[$file_count]['type'] = is_dir($fold.$file_name) ? 'dir' : 'file';

              if ($filearray[$file_count]['size'] > 1024){
                  if ($filearray[$file_count]['size'] > 1048576){
                      $bytes_size = ceil($filearray[$file_count]['size'] / 1048576).' MB';
                  } else {
                      $bytes_size = ceil($filearray[$file_count]['size'] / 1024).' KB';
                  }
              } else {
                  $bytes_size = $filearray[$file_count]['size'].' bytes';
              }
              $filearray[$file_count]['sizemb'] = $bytes_size;

              $file_count = $file_count + 1;
          }
        }
        closedir($handle);
        return $filearray;
    }
    if ($sort){
        usort($filearray, "cmp");
    }
    return $filearray;

}

function pre($array, $float = false, $dump = false){

    if($float){
        echo "<div style='position: absolute; padding: 15px;border: 1px solid #000000;background-color: #FFFFFF; z-index: 999999;'><i class='fa fa-2x fa-times-circle' style='cursor: pointer;float: right;' aria-hidden='true' onclick='$(this).parent().hide();'></i>";
    }

    echo '<pre>';
    if(!$dump){
        print_r($array);
    }else{
        var_dump($array);
    }
    echo '</pre>';

    if($float){
         echo "</div>";
    }


}

function header_current_date(){

        # (c)2010 Arcaweb - L.Conti

        return ddtostring(date("N")).' '.date("j").' '.mmtostring(date("m"));

    }

    function ext($filename){
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

function cut($str, $len, $cut_chars = '...', $exact = false){

         if (strlen($str) > $len+strlen($cut_chars)){
             if ($exact){
                 $str =  mb_substr($str, 0, $len-strlen($cut_chars),'utf-8').$cut_chars;
            } else {
                 $str = wordwrap($str, $len, "\n");
                 $str = substr($str, 0, strpos($str, "\n")).$cut_chars;
             }
         }
         return $str;
}

    function b36($val, $rev = false){
        # Conversione in base 36 per creare mini hash da un integer
        return $rev ? intval($val,36) : base_convert($val, 10, 36);
    }

    function valid_ext($filename, $exts = ''){

        # (c)2010 Arcaweb - L.Conti

           $ext = pathinfo($filename, PATHINFO_EXTENSION);
        return stristr($exts, '.'.substr($filename, 0-strlen($ext)).';');

    }

    function check_file_uploaded_length ($filename){
        return (bool) ((mb_strlen($filename,"UTF-8") > 225) ? true : false);
    }


    function upload_error_codes($error_code){
        $codes = array(
            0=>"File caricato con successo",
            1=>"La dimensione del file eccede la direttiva upload_max_filesize in php.ini",
            2=>"La dimensione del file eccede la direttiva MAX_FILE_SIZE nel form html",
            3=>"Il file risulta caricato solo parzialmente",
            4=>"Nessun file caricato",
            6=>"Cartella temporanea inesistente"
        );
        return $codes[$error_code];
    }

    function mmtostring($month, $short = false){

        # (c)2010 Arcaweb - L.Conti

        switch ($month) {

            case 1: return $short ? 'Gen' : 'Gennaio'; break;
            case 2: return $short ? 'Feb' : 'Febbraio'; break;
            case 3: return $short ? 'Mar' : 'Marzo'; break;
            case 4: return $short ? 'Apr' : 'Aprile'; break;
            case 5: return $short ? 'Mag' : 'Maggio'; break;
            case 6: return $short ? 'Giu' : 'Giugno'; break;
            case 7: return $short ? 'Lug' : 'Luglio'; break;
            case 8: return $short ? 'Ago' : 'Agosto'; break;
            case 9: return $short ? 'Set' : 'Settembre'; break;
            case 10: return $short ? 'Ott' : 'Ottobre'; break;
            case 11: return $short ? 'Nov' : 'Novembre'; break;
            case 12: return $short ? 'Dic' : 'Dicembre'; break;

        }

        return false;

    }

    function ddtostring($day,$short=false, $spchars = false, $entities = false){
        if ($short) {
            switch ($day) {
              case ($day==1 or $day==8): return 'Lu'; break;
              case ($day==2 or $day==9): return 'Ma'; break;
              case ($day==3 or $day==10): return 'Me'; break;
              case ($day==4 or $day==11): return 'Gi'; break;
              case ($day==5 or $day==12): return 'Ve'; break;
              case ($day==6 or $day==13): return 'Sa'; break;
              case ($day==7 or $day==14): return 'Do'; break;
            }
        }
        else {
            $i = $spchars ? ($entities ? '&igrave;' : 'ì') : 'i';
            switch ($day) {
              case ($day==1 or $day==8): return 'Luned'.$i; break;
              case ($day==2 or $day==9): return 'Marted'.$i; break;
              case ($day==3 or $day==10): return 'Mercoled'.$i; break;
              case ($day==4 or $day==11): return 'Gioved'.$i; break;
              case ($day==5 or $day==12): return 'Venerd'.$i; break;
              case ($day==6 or $day==13): return 'Sabato'; break;
              case ($day==7 or $day==14): return 'Domenica'; break;
            }
        }
    }

    function curl_post($data = '', $url, $timeout = 3000){

        $output = '';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING , "gzip");
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // no ssl check
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // no ssl check

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    function httpPost($host, $port, $path, $data, $charset = 'UTF-8') {

        # (c)Arcaweb - L.Conti

        if ($fp = fsockopen($host, $port, $errno, $errstr)){
            stream_set_timeout($fp, 2);
            fputs($fp, "POST $path HTTP/1.1\n");
            fputs($fp, "Host: $host\n");
            fputs($fp, "User-Agent: L.C. HTTPTools 1.0\n");
            fputs($fp, "Content-type: text/xml; charset=\"".$charset."\"\n");
            fputs($fp, "Content-length: ". strlen($data) ."\n\n");
            fputs($fp, $data);
            fclose($fp);
            return true;
        }
        return false;
    }

    function random_code($len, $readable = false){

        # (c)Arcaweb - L.Conti

        $code = '';
        while (strlen($code) < $len){
            if (mt_rand()&1 == 1){
                $chr = chr((mt_rand(0, 25))+65);
            } else {
                $chr = chr((mt_rand(0, 9))+48);
            }
            if (!$readable || ($readable && !stristr('10ilo', $chr))){
                $code .= $chr;
            }
        }
        return $code;

    }

    function random_hash($extra_seed = ''){

        # (c)2009 Arcaweb - L.Conti

        return sha1(md5('$ä*+#{a}rand@&89y'.$extra_seed).((double)microtime()*1000000));

    }

    function ftp_write($ftp_server, $ftp_username, $ftp_pass, $ftp_filepath, $ftp_filecontent){

        # (c)2010 Arcaweb - L.Conti
        # Funzione non testata!

        try {

            $fp = fopen('ftp://'.$ftp_username.':'.$ftp_pass.'@'.$ftp_server.'/'.$ftp_filepath, 'w');
            fwrite($fp, $ftp_filecontent);
            fclose($fp);

            return true;

        } catch (Exception $e) {
               return false;
        }

    }

    function ext_to_mime($ext){

        # (c)2010 Arcaweb - L.Conti

        $ext = strtolower(str_replace('.','',$ext));
        $default_mime = 'application/octet-stream';

        $ext_mime_list = array('323' => 'text/h323','acx' => 'application/internet-property-stream','ai' => 'application/postscript','aif' => 'audio/x-aiff','aifc' => 'audio/x-aiff','aiff' => 'audio/x-aiff','asf' => 'video/x-ms-asf','asr' => 'video/x-ms-asf','asx' => 'video/x-ms-asf','au' => 'audio/basic','avi' => 'video/x-msvideo','axs' => 'application/olescript','bas' => 'text/plain','bcpio' => 'application/x-bcpio','bin' => 'application/octet-stream','bmp' => 'image/bmp','c' => 'text/plain','cat' => 'application/vnd.ms-pkiseccat','cdf' => 'application/x-cdf','cer' => 'application/x-x509-ca-cert','class' => 'application/octet-stream','clp' => 'application/x-msclip','cmx' => 'image/x-cmx','cod' => 'image/cis-cod','cpio' => 'application/x-cpio','crd' => 'application/x-mscardfile','crl' => 'application/pkix-crl','crt' => 'application/x-x509-ca-cert','csh' => 'application/x-csh','css' => 'text/css','csv' => 'application/vnd.ms-excel','dcr' => 'application/x-director','der' => 'application/x-x509-ca-cert','dir' => 'application/x-director','dll' => 'application/x-msdownload','dms' => 'application/octet-stream','doc' => 'application/msword','dot' => 'application/msword','dvi' => 'application/x-dvi','dxr' => 'application/x-director','eps' => 'application/postscript','etx' => 'text/x-setext','evy' => 'application/envoy','exe' => 'application/octet-stream','fif' => 'application/fractals','flr' => 'x-world/x-vrml','gif' => 'image/gif','gtar' => 'application/x-gtar','gz' => 'application/x-gzip',
        'h' => 'text/plain','hdf' => 'application/x-hdf','hlp' => 'application/winhlp','hqx' => 'application/mac-binhex40','hta' => 'application/hta','htc' => 'text/x-component','htm' => 'text/html','html' => 'text/html','htt' => 'text/webviewhtml','ico' => 'image/x-icon','ief' => 'image/ief','iii' => 'application/x-iphone','ins' => 'application/x-internet-signup','isp' => 'application/x-internet-signup','jfif' => 'image/pipeg','jpe' => 'image/jpeg','jpeg' => 'image/jpeg','jpg' => 'image/jpeg','js' => 'application/x-javascript','latex' => 'application/x-latex','lha' => 'application/octet-stream','lsf' => 'video/x-la-asf','lsx' => 'video/x-la-asf','lzh' => 'application/octet-stream','m13' => 'application/x-msmediaview','m14' => 'application/x-msmediaview','m3u' => 'audio/x-mpegurl','man' => 'application/x-troff-man','mdb' => 'application/x-msaccess','me' => 'application/x-troff-me','mht' => 'message/rfc822','mhtml' => 'message/rfc822','mid' => 'audio/mid','mny' => 'application/x-msmoney','mov' => 'video/quicktime','movie' => 'video/x-sgi-movie','mp2' => 'video/mpeg','mp3' => 'audio/mpeg','mpa' => 'video/mpeg','mpe' => 'video/mpeg','mpeg' => 'video/mpeg','mpg' => 'video/mpeg','mpp' => 'application/vnd.ms-project','mpv2' => 'video/mpeg','ms' => 'application/x-troff-ms','mvb' => 'application/x-msmediaview','nws' => 'message/rfc822','oda' => 'application/oda','p10' => 'application/pkcs10','p12' => 'application/x-pkcs12','p7b' => 'application/x-pkcs7-certificates','p7c' => 'application/x-pkcs7-mime',
        'p7m' => 'application/x-pkcs7-mime','p7r' => 'application/x-pkcs7-certreqresp','p7s' => 'application/x-pkcs7-signature','pbm' => 'image/x-portable-bitmap','pdf' => 'application/pdf','pfx' => 'application/x-pkcs12','pgm' => 'image/x-portable-graymap','pko' => 'application/ynd.ms-pkipko','pma' => 'application/x-perfmon','pmc' => 'application/x-perfmon','pml' => 'application/x-perfmon','pmr' => 'application/x-perfmon','pmw' => 'application/x-perfmon','pnm' => 'image/x-portable-anymap','pot,' => 'application/vnd.ms-powerpoint','ppm' => 'image/x-portable-pixmap','pps' => 'application/vnd.ms-powerpoint','ppt' => 'application/vnd.ms-powerpoint','prf' => 'application/pics-rules','ps' => 'application/postscript','pub' => 'application/x-mspublisher','qt' => 'video/quicktime','ra' => 'audio/x-pn-realaudio','ram' => 'audio/x-pn-realaudio','ras' => 'image/x-cmu-raster','rgb' => 'image/x-rgb','rmi' => 'audio/mid','roff' => 'application/x-troff','rtf' => 'application/rtf','rtx' => 'text/richtext','scd' => 'application/x-msschedule','sct' => 'text/scriptlet','setpay' => 'application/set-payment-initiation','setreg' => 'application/set-registration-initiation','sh' => 'application/x-sh','shar' => 'application/x-shar','sit' => 'application/x-stuffit','snd' => 'audio/basic','spc' => 'application/x-pkcs7-certificates','spl' => 'application/futuresplash','src' => 'application/x-wais-source','sst' => 'application/vnd.ms-pkicertstore','stl' => 'application/vnd.ms-pkistl','stm' => 'text/html',
        'svg' => 'image/svg+xml','sv4cpio' => 'application/x-sv4cpio','sv4crc' => 'application/x-sv4crc','swf' => 'application/x-shockwave-flash','t' => 'application/x-troff','tar' => 'application/x-tar','tcl' => 'application/x-tcl','tex' => 'application/x-tex','texi' => 'application/x-texinfo','texinfo' => 'application/x-texinfo','tgz' => 'application/x-compressed','tif' => 'image/tiff','tiff' => 'image/tiff','tr' => 'application/x-troff','trm' => 'application/x-msterminal','tsv' => 'text/tab-separated-values','txt' => 'text/plain','uls' => 'text/iuls','ustar' => 'application/x-ustar','vcf' => 'text/x-vcard','vrml' => 'x-world/x-vrml','wav' => 'audio/x-wav','wcm' => 'application/vnd.ms-works','wdb' => 'application/vnd.ms-works','wks' => 'application/vnd.ms-works','wmf' => 'application/x-msmetafile','wps' => 'application/vnd.ms-works','wri' => 'application/x-mswrite','wrl' => 'x-world/x-vrml','wrz' => 'x-world/x-vrml','xaf' => 'x-world/x-vrml','xbm' => 'image/x-xbitmap','xla' => 'application/vnd.ms-excel','xlc' => 'application/vnd.ms-excel','xlm' => 'application/vnd.ms-excel','xls' => 'application/vnd.ms-excel','xlt' => 'application/vnd.ms-excel','xlw' => 'application/vnd.ms-excel','xof' => 'x-world/x-vrml','xpm' => 'image/x-xpixmap','xwd' => 'image/x-xwindowdump','z' => 'application/x-compress','zip' => 'application/zip');

        foreach ($ext_mime_list as $a => $v){
            if ($a == $ext){
                return $v;
            }
        }

        return $default_mime;

    }

    function self_name(){

        return basename($_SERVER['PHP_SELF'], ".php");

    }

    function ftp_put_file_new($xfile,$xpath,$ftp_server,$ftp_user_name,$ftp_user_pass)
    {
        $result = true;
        $protocol_ftp = '';
        $local_file = $xfile; // Defines Name of Local File to be Uploaded

    $destination_file = $xpath.basename($xfile);  // Path for File Upload (relative to your login dir)

    // Connect to FTP Server
    $conn_id = ftp_connect($ftp_server);
    // Login to FTP Server
    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

    // Verify Log In Status
    if ((!$conn_id) || (!$login_result)) {
      $protocol_ftp .= "\nFTP connection has failed! \n";
      $protocol_ftp .= "Attempted to connect to $ftp_server for user $ftp_user_name\n";
            $result = false;
    } else {
            $protocol_ftp .=  "\nConnected to $ftp_server, for user $ftp_user_name \n";
          $upload = ftp_put($conn_id, $destination_file, $local_file, FTP_BINARY);  // Upload the File

        // Verify Upload Status
          if (!$upload)
            {$result =  false;}
            else
            {$result =  true;}

          ftp_close($conn_id); // Close the FTP Connection
        }

        return $result;
    }


    function array_found($wh,$array) {
   foreach($array as $key) {
            if (in_array($wh,$key)) {
                return true;
            }
   }
   return false;
}

    function is_ip($ip){
        return filter_var($ip, FILTER_VALIDATE_IP);
    }

  function dir_list($dir,$ext='')
  {
      if ($dir[strlen($dir)-1] != '/') $dir .= '/';

      if (!is_dir($dir)) return array();

      $dir_handle  = opendir($dir);
      $dir_objects = array();
      while ($object = readdir($dir_handle))
          if (!in_array($object, array('.','..')))
          {
              $filename    = $dir . $object;
              $file_object = array(
                                      'name' => $object,
                                      'size' => filesize($filename),
                                      'type' => filetype($filename),
                                      'time' => date("YmdHis", filemtime($filename))
                                  );
              if ($ext>'') {

                  if (strpos($object,$ext,1))
                  {$dir_objects[] = $file_object;}
                }
              else
              {$dir_objects[] = $file_object;}
          }

      return $dir_objects;
  }


    function is_date($value, $format = 'mm.dd.yyyy'){

        if(strlen($value) == 10 && strlen($format) == 10){
            // find separator. Remove all other characters from $format
            $separator_only = str_replace(array('m','d','y'),'', $format);
            $separator = $separator_only[0]; // separator is first character

            if($separator && strlen($separator_only) == 2){
                // make regex
                $regexp = str_replace('mm', '[0-1][0-9]', $value);
                $regexp = str_replace('dd', '[0-3][0-9]', $value);
                $regexp = str_replace('yyyy', '[0-9]{4}', $value);
                $regexp = str_replace($separator, "\\" . $separator, $value);

                if($regexp != $value && preg_match('/'.$regexp.'/', $value)){

                    // check date
                    $day   = substr($value,strpos($format, 'd'),2);
                    $month = substr($value,strpos($format, 'm'),2);
                    $year  = substr($value,strpos($format, 'y'),4);
                    if (is_numeric($day)&& is_numeric($month) && is_numeric($year)) {
                        if(@checkdate($month, $day, $year))
                          return true;
                    }
                }
            }
        }
        return false;
    }


function wd_resize($source,$dest,$new_width,$new_quality) {

    # (c) L.Conti - Arcaweb

    list($width, $height) = getimagesize($source);
    $w = $new_width;
    $h = ceil($height*$new_width/$width);
    $x = 0;
    $y = 0;

    $new_im = ImageCreatetruecolor($w,$h);
    $im = imagecreatefrom_any($source);

    imagecopyresampled($new_im,$im,$x,$y,0,0,$w,$h,$width,$height);
    imagejpeg($new_im,$dest,$new_quality);

}

function file_ext($file){

    // L.C. 08.03.2016
    // DATO IL NOME DI UN FILE, RESTITUISCE L'ESTENSIONE

    return strtolower(pathinfo($file, PATHINFO_EXTENSION));

}

function imagecreatefrom_any($img){

    // L.C. 08.03.2016
    // CREA L'OGGETTO IMMAGINE PER PIÙ TIPI DI FILE

    $ext = file_ext($img);
    
    switch ($ext) {
        case 'jpg':
            return imagecreatefromjpeg($img);
            break;
        case 'jpeg':
            return imagecreatefromjpeg($img);
            break;
        case 'png':
            return imagecreatefrompng($img);
            break;
        case 'gif':
            return imagecreatefromgif($img);
            break;
        default:
            return imagecreatefromjpeg($img);
    }
}

function resize_max($inputfile, $outputfile, $max_size, $quality = 100){

    # (c) L.Conti - Arcaweb

    list($width, $height) = getimagesize($inputfile);

    if ($width > $height){
        // Landscape
        $new_width = $max_size;
        $new_height = ceil($height * $new_width / $width);
    }
    elseif ($width < $height) {
        // Portrait
        $new_height = $max_size;
        $new_width = ceil($width * $new_height / $height);
    }
    else {
        // Square
        $new_height = $max_size;
        $new_width = $max_size;
    }

    $image_tmp = imagecreatetruecolor($new_width, $new_height);
    $image_original = imagecreatefrom_any($inputfile);
    imagecopyresampled($image_tmp, $image_original, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    imagejpeg($image_tmp,$outputfile,$quality);

    return true;

}

function img_watermark($inputfile, $outputfile, $watermark_image){
    $ext = strtolower(pathinfo($watermark_image, PATHINFO_EXTENSION));
    $im_watermark = imagecreatefrom_any($watermark_image);
    $im = imagecreatefrom_any($inputfile);
    $marge_right = 10;
    $marge_bottom = 10;
    $sx = imagesx($im_watermark);
    $sy = imagesy($im_watermark);

    imagecopy($im, $im_watermark, imagesx($im) - $sx - $marge_right, imagesy($im) - $sy - $marge_bottom, 0, 0, imagesx($im_watermark), imagesy($im_watermark));
    imagejpeg($im,$outputfile,100);

}

function resize_to_canvas($filename, $dest_filename,$canvas_w=100,$canvas_h=225, $bgcolor = '#000000', $quality = 100, $text = ''){
    //$bgcolor = str_replace('','',$bgcolor);

    $b = preg_replace("/[^0-9a-fA-F]/", "", $bgcolor);

    $b = strlen($b) == 6 ? $b : '000000';

    list($width, $height, $type) = getimagesize($filename);

    $original_overcanvas_w = $width/$canvas_w;
    $original_overcanvas_h = $height/$canvas_h;

    $dst_w = round($width/max($original_overcanvas_w,$original_overcanvas_h),0);
    $dst_h = round($height/max($original_overcanvas_w,$original_overcanvas_h),0);

    $dst_image = imagecreatetruecolor($canvas_w, $canvas_h);

    $background = imagecolorallocate($dst_image, hexdec('0x' . $b{0} . $b{1}), hexdec('0x' . $b{2} . $b{3}), hexdec('0x' . $b{4} . $b{5}));
    imagefill($dst_image, 0, 0, $background);

    $src_image = imagecreatefrom_any($filename);
    imagecopyresampled($dst_image, $src_image, ($canvas_w-$dst_w)/2, ($canvas_h-$dst_h)/2, 0, 0, $dst_w, $dst_h, $width, $height);

    if (!empty($text)){
        $font_dir = dirname(__FILE__).'/fonts/';
        $textcolor = imagecolorallocatealpha($dst_image, 0, 0, 0, 50);
        imagettftext($dst_image, 9, 0, 10, $dst_h-12, $textcolor, $font_dir.'ARIAL.TTF', $text);
        $textcolor = imagecolorallocatealpha($dst_image, 255, 255, 255, 50);
        imagettftext($dst_image, 9, 0, 11, $dst_h-11, $textcolor, $font_dir.'ARIAL.TTF', $text);
    }

    imagejpeg($dst_image, $dest_filename, $quality);
    imagedestroy($dst_image);

    return 1;
}

function crop($inputfile, $outputfile, $x, $y, $w, $h, $resize_to_w = 0, $resize_to_h = 0){

    # (c)Arcaweb - L.Conti

    $image_target = imagecreatetruecolor($resize_to_w > 0 ? $resize_to_w : $w, $resize_to_h ? $resize_to_h : $h);
    $image_original = imagecreatefrom_any($inputfile);
    imagecopyresampled($image_target, $image_original, 0, 0, $x, $y, $resize_to_w > 0 ? $resize_to_w : $w, $resize_to_h ? $resize_to_h : $h, $w, $h);

    imagejpeg($image_target,$outputfile,100);

}

function crop_resize($filename, $width, $height, $outputfile, $quality, $text = ''){

    $wc = $width;
    $hc = $height;
    $w = $width;
    $h = $height;

    list($f_width, $f_height) = getimagesize($filename);

       $f_ratio = round($f_width/$f_height, 2);

    if ($w/$h > $f_ratio) {
      $h = $w/$f_ratio;
    } else {
        $w = $h*$f_ratio;
    }

    $image_target = imagecreatetruecolor($wc, $hc);
    $image_original = imagecreatefrom_any($filename);
    imagecopyresampled($image_target, $image_original, (ceil($w/2)-ceil($wc/2))*-1, (ceil($h/2)-ceil($hc/2))*-1, 0, 0, $w, $h, $f_width, $f_height);

    if (!empty($text)){
        $font_dir = dirname(__FILE__).'/fonts/';
        $textcolor = imagecolorallocatealpha($dst_image, 0, 0, 0, 50);
        imagettftext($dst_image, 9, 0, 10, $dst_h-12, $textcolor, $font_dir.'ARIAL.TTF', $text);
        $textcolor = imagecolorallocatealpha($dst_image, 255, 255, 255, 50);
        imagettftext($dst_image, 9, 0, 11, $dst_h-11, $textcolor, $font_dir.'ARIAL.TTF', $text);
    }

    imagejpeg($image_target,$outputfile,$quality);
    return 1;

}


function delfile($xfile) {
    if (file_exists($xfile)) {
        if (unlink($xfile))
        {return true;}
        else
        {return false;}
    }
    else
    {return false;}
}

function delcontent_dir($xdir) {
    if (is_dir($xdir)) {
        $xreturn = true;
        $d = dir($xdir);
        while($entry = $d->read()) {
             if ($entry!= "." && $entry!= "..") {
                 if (!unlink($xdir.$entry)) {
                     $d->close();
                     return false;
                 }
             }
        }
        $d->close();
        return true;
    }
    else
    {return false;}
}


function utf8_to_html ($data)
{return preg_replace("/([\\xC0-\\xF7]{1,1}[\\x80-\\xBF]+)/e", '_utf8_to_html("\\1")', $data);}

function _utf8_to_html ($data) {
    $ret = 0;
  foreach((str_split(strrev(chr((ord($data{0}) % 252 % 248 % 240 % 224 % 192) + 128) . substr($data, 1)))) as $k => $v)
      $ret += (ord($v) % 128) * pow(64, $k);
  return "&#$ret;";
}

function checkmail($email)
{
     if(preg_match("/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})/", $email)) {
         //echo 'email valido';
    return true;
  } else {
      //echo 'email non valido';
    return false;
  }
}

function rand_chars($c, $l, $u = FALSE) {
    // es: rand_chars('abcdefghilmntuvz',10); // quali caratteri - quanto lunga
    if (!$u) for ($s = '', $i = 0, $z = strlen($c)-1; $i < $l; $x = rand(0,$z), $s .= $c{$x}, $i++);
     else for ($i = 0, $z = strlen($c)-1, $s = $c{rand(0,$z)}, $i = 1; $i != $l; $x = rand(0,$z), $s .= $c{$x}, $s = ($s{$i} == $s{$i-1} ? substr($s,0,-1) : $s), $i=strlen($s));
     return $s;
}

function clean_url($str, $spaces = false, $entities = true){

    $ed = remove_accents($str);
    $ed = trim($ed);
    $ed = utf8_decode($ed);
    $ed = str_replace('\'','-',$ed);
    $ed = str_replace(' ','-',$ed);
    $ed = html_entity_decode($ed);
    $ed = preg_replace('/[^A-Za-z0-9\-_\.]/','',$ed);
    $ed = str_replace('..','.',$ed);
    $ed = str_replace('---','-',$ed);
    $ed = str_replace('--','-',$ed);
    $ed = str_replace('--','-',$ed);
    $ed = strtolower($ed);
    if ($spaces) $ed = str_replace('-',' ',$ed);
    //if ($entities) $ed = htmlentities($ed);

    $ed = utf8_encode($ed);

    return $ed;
}

function remove_accents($str, $enc = "UTF-8"){

    $accents = array(
        'A' => '/&Agrave;|&Aacute;|&Acirc;|&Atilde;|&Auml;|&Aring;/',
        'a' => '/&agrave;|&aacute;|&acirc;|&atilde;|&auml;|&aring;/',
        'C' => '/&Ccedil;/',
        'c' => '/&ccedil;/',
        'E' => '/&Egrave;|&Eacute;|&Ecirc;|&Euml;/',
        'e' => '/&egrave;|&eacute;|&ecirc;|&euml;/',
        'I' => '/&Igrave;|&Iacute;|&Icirc;|&Iuml;/',
        'i' => '/&igrave;|&iacute;|&icirc;|&iuml;/',
        'N' => '/&Ntilde;/',
        'n' => '/&ntilde;/',
        'O' => '/&Ograve;|&Oacute;|&Ocirc;|&Otilde;|&Ouml;/',
        'o' => '/&ograve;|&oacute;|&ocirc;|&otilde;|&ouml;/',
        'U' => '/&Ugrave;|&Uacute;|&Ucirc;|&Uuml;/',
        'u' => '/&ugrave;|&uacute;|&ucirc;|&uuml;/',
        'Y' => '/&Yacute;/',
        'y' => '/&yacute;|&yuml;/',
        'a.' => '/&ordf;/',
        'o.' => '/&ordm;/'
    );
    return preg_replace($accents, array_keys($accents), htmlentities($str,ENT_NOQUOTES, $enc));
}

function GetFolderSize($d =".", $human = false) {

    $h = @opendir($d);
    if($h==0)return 0;

        if (!isset($sf)) $sf = 0;
    while ($f = readdir($h)){
      if ($f != "..") {
        $sf += filesize($nd=$d."/".$f);
        if($f != "." && is_dir($nd)){
            $sf+=GetFolderSize($nd);
        }
      }
    }
    closedir($h);

    if (!$human){

        return $sf;

    } else {

        $unit = 'Bytes';
        if ($sf > 1073741824){
        $sf = round($sf / 1073741824, 2);
        $unit = 'GB';
        } elseif ($sf > 1048576){
        $sf = round($sf / 1048576, 2);
        $unit = 'MB';
        } elseif ($sf > 1024){
        $sf = round($sf / 1024, 2);
        $unit = 'KB';
        }

        return $sf.$unit;
    }
}

function humanBytes($sf){

    if (!ctype_digit($sf)){
        return '';
    }

    $unit = 'bytes';
    if ($sf > 1073741824){
        $sf = round($sf / 1073741824, 2);
        $unit = 'GB';
    } elseif ($sf > 1048576){
        $sf = round($sf / 1048576, 2);
        $unit = 'MB';
    } elseif ($sf > 1024){
        $sf = round($sf / 1024, 2);
        $unit = 'KB';
    }
    return $sf.' '.$unit;

}

function get_ip_address(){

    $ip = $_SERVER["REMOTE_ADDR"];

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
            if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)){
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        }
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)){
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
            }
        }
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            if (filter_var(@$_SERVER["HTTP_CF_CONNECTING_IP"], FILTER_VALIDATE_IP)) {
                $ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
            }
        }

    return $ip;

}


function ip_info($ip = NULL, $purpose = "location", $deep_detect = TRUE) {
    $output = NULL;
    if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
        $ip = get_ip_address();
    }
    $purpose    = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));

    $support    = array("country", "countrycode", "state", "city", "location", "address");


    if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
        $ipdat = @json_decode(url_get_contents("https://analytics.tio.ch/?ip=" . $ip));
        if (@strlen(trim($ipdat->country->iso_code)) == 2) {
            switch ($purpose) {
                case "location":
                    $output = array(
                        "city"           => @(!empty($ipdat->city) ? $ipdat->city->names->en : ''),
                        "country"        => @$ipdat->country->names->en,
                        "country_code"   => @$ipdat->country->iso_code,
                        "continent"      => @$ipdat->continent->names->en,
                        "continent_code" => @$ipdat->continent->code
                    );
                    break;
                case "address":
                    $address = array($ipdat->country->names->en);
                    if (!empty($ipdat->city))
                        $address[] = $ipdat->city->names->en;
                    $output = implode(", ", array_reverse($address));
                    break;
                case "city":
                    $output = @(!empty($ipdat->city->names->en) ? $ipdat->city->names->en : '');
                    break;
                case "country":
                    $output = @$ipdat->country->names->en;
                    break;
                case "countrycode":
                    $output = @$ipdat->country->iso_code;
                    break;
            }
        }
    }

    return $output;
}

$nltk = [];
$nltk["italian_stopwords"]  =['ad', 'al', 'allo', 'ai', 'agli', 'all', 'agl', 'alla', 'alle', 'con', 'col', 'coi', 'da', 'dal', 'dallo', 'dai', 'dagli', 'dall', 'dagl', 'dalla', 'dalle', 'di', 'del', 'dello', 'dei', 'degli', 'dell', 'degl', 'della', 'delle', 'in', 'nel', 'nello', 'nei', 'negli', 'nell', 'negl', 'nella', 'nelle', 's', 'sul', 'sullo', 'sui', 'sugli', 'sull', 'sugl', 'sulla', 'sulle', 'per', 'tra', 'contro', 'io', 't', 'lui', 'lei', 'noi', 'voi', 'loro', 'mio', 'mia', 'miei', 'mie', 'tuo', 'tua', 'tuoi', 'tue', 'suo', 'sua', 'suoi', 'sue', 'nostro', 'nostra', 'nostri', 'nostre', 'vostro', 'vostra', 'vostri', 'vostre', 'mi', 'ti', 'ci', 'vi', 'lo', 'la', 'li', 'le', 'gli', 'ne', 'il', 'un', 'uno', 'una', 'ma', 'ed', 'se', 'perche', 'anche', 'come', 'dov', 'dove', 'che', 'chi', 'cui', 'non', 'piu', 'quale', 'quanto', 'quanti', 'quanta', 'quante', 'quello', 'quelli', 'quella', 'quelle', 'questo', 'questi', 'questa', 'queste', 'si', 'tutto', 'tutti', 'a', 'c', 'e', 'i', 'l', 'o', 'ho', 'hai', 'ha', 'abbiamo', 'avete', 'hanno', 'abbia', 'abbiate', 'abbiano', 'avra', 'avrai', 'avro', 'avremo', 'avrete', 'avranno', 'avrei', 'avresti', 'avrebbe', 'avremmo', 'avreste', 'avrebbero', 'avevo', 'avevi', 'aveva', 'avevamo', 'avevate', 'avevano', 'ebbi', 'avesti', 'ebbe', 'avemmo', 'aveste', 'ebbero', 'avessi', 'avesse', 'avessimo', 'avessero', 'avendo', 'avuto', 'avuta', 'avuti', 'avute', 'sono', 'sei', 'e', 'siamo', 'siete', 'sia', 'siate', 'siano', 'sara', 'sarai', 'saro', 'saremo', 'sarete', 'saranno', 'sarei', 'saresti', 'sarebbe', 'saremmo', 'sareste', 'sarebbero', 'ero', 'eri', 'era', 'eravamo', 'eravate', 'erano', 'fui', 'fosti', 'f', 'fummo', 'foste', 'furono', 'fossi', 'fosse', 'fossimo', 'fossero', 'essendo', 'faccio', 'fai', 'facciamo', 'fanno', 'faccia', 'facciate', 'facciano', 'fara', 'farai', 'faro', 'faremo', 'farete', 'faranno', 'farei', 'faresti', 'farebbe', 'faremmo', 'fareste', 'farebbero', 'facevo', 'facevi', 'faceva', 'facevamo', 'facevate', 'facevano', 'feci', 'facesti', 'fece', 'facemmo', 'faceste', 'fecero', 'facessi', 'facesse', 'facessimo', 'facessero', 'facendo', 'sto', 'stai', 'sta', 'stiamo', 'stanno', 'stia', 'stiate', 'stiano', 'stara', 'starai', 'staro', 'staremo', 'starete', 'staranno', 'starei', 'staresti', 'starebbe', 'staremmo', 'stareste', 'starebbero', 'stavo', 'stavi', 'stava', 'stavamo', 'stavate', 'stavano', 'stetti', 'stesti', 'stette', 'stemmo', 'steste', 'stettero', 'stessi', 'stesse', 'stessimo', 'stessero', 'stando'];

$nltk["punctuation_regex"] = '/\!|\"|\#|\$|\%|\&|\\|\'|\(|\)|\*|\+|\.|\/|\<|\>|\?|\@|\[|\\|\]|\^|\_|\`|\{|\||\}|\~/';

function getNltkWords($string){
        global $nltk;

        $string = html_entity_decode($string);
        $search_params = preg_split( "/ |\'|\"|\_|\,|\-|\:|\;|\=/", $string);
        $search_params = preg_replace($nltk["punctuation_regex"], '', $search_params);
        setlocale(LC_CTYPE, 'en_US.UTF-8');

        $valid_words = [];

        foreach($search_params as $param){
            $test_param  = iconv('UTF-8', 'ASCII//TRANSLIT', $param);
           if(!empty($test_param)  && !in_array(strtolower($test_param), $nltk["italian_stopwords"] )){ //da sistemare i diatrics
                $valid_words[] = $param;
            }
        }
    return $valid_words;
}

function startsWith($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}


function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

function sitelink($type, $data){

        $uri = '';

        switch ($type){

            case 'content':

                if (!empty($data['id_parent'])){
                    if ($parent_section = get_section_by_id($data['id_parent'], $data['id_domains'])){
                        $uri .= '/'.$parent_section['name'];
                    }
                }
                if (!empty($data['section_name'])){
                    $uri .= '/'.$data['section_name'];
                }
                if (!empty($data['id'])){
                    $uri .= '/'.$data['id'];
                }

                if(!empty($data['title'])){
                    $uri .= '/'.$data['title'];
                }

                break;

            case 'section':

                if (!empty($data['id_parent'])){
                    if ($parent_section = get_section_by_id($data['id_parent'], $data['id_domains'])){
                        $uri .= '/'.$parent_section['name'];
                    }
                }
                $uri .= '/'.$data['name'];
                break;

        }

        return clean_site_url($uri);
}

function json_section_list($id_domain){

    global $sqlPDO;


    $rs = $sqlPDO->queryex('SELECT
                cms_sections.name_1 AS name,
                cms_sections.id,
                cms_sections.id_parent,
                cms_sections.mobile,
                cms_sections.explode,
                cms_sections.aperture,
                cms_sections.selezionati,
                cms_sections.news,
                cms_sections.grouper,
                cms_sections.color_hex,
                cms_sections.redirect_url,
                cms_sections.sort,
                cms_sections.target,
                cms_sections.id_layout,
                cms_sections.pubblicitario,
                cms_sections_addon.section_picture,
                cms_sections_addon.section_picture_mobile,
                cms_sections_addon.description,
                cms_sections_addon.url,
                cms_sections_addon.advanced_html

            FROM
                cms_sections
                LEFT OUTER JOIN
                    cms_sections_addon on cms_sections_addon.id_sections = cms_sections.id
            WHERE
                cms_sections.id_domains = :id_domain
            AND
                cms_sections.active = 1
            AND (
                cms_sections.date_start IS NULL
                OR cms_sections.date_start < NOW()
            )
            AND (
                cms_sections.date_end IS NULL
                OR cms_sections.date_end > NOW()
            )
            ORDER BY cms_sections.id_parent,  cms_sections.sort ASC', array("id_domain" => $id_domain), true);

    return json_encode($rs);
}


function clean_site_url($url,$nodashes = false){

    $unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
        'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
        'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
        'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y');

    $url = strtr($url, $unwanted_array);

    $url  = str_replace(' ', '-', $url);
    $url  = preg_replace('/[^a-zA-Z 0-9\-\/]+/', '-', $url);

    if($nodashes){
        $url  = preg_replace('/\-/', '', $url);
    }
    return strtolower($url);
}


function get_section_by_id($id, $id_domain){
    global $sqlPDO;


    $section_list = json_decode(json_section_list($id_domain), true);


    foreach($section_list as $section){
        if($section['id'] == $id){
            return $section;
        }
    }

    return false;
}



function getSocialTexts($id_content, $maxTextLength = 0){
    global $sqlPDO, $config;


    $socialText = [];

    $params = [];
    $params["id_content"] = $id_content;
    $rs = $sqlPDO->queryex("SELECT title_1,subtitle_1,id_sections FROM cms_contents WHERE id = :id_content", $params, true);
    if(!empty($rs)){
        $tokens = $sqlPDO->queryex("
            SELECT
                tokens.id,
                tokens.token,
                tokens_contents_rel.`value`
            FROM
                tokens
            INNER JOIN tokens_contents_rel ON tokens_contents_rel.id_token = tokens.id
            WHERE
                tokens_contents_rel.id_content = :id_content
                AND
                tokens.active = 1
                AND
                tokens_contents_rel.active = 1
                AND
                tokens_contents_rel.`value` >= 4
            ORDER BY
                tokens_contents_rel.`value` DESC
               LIMIT 5
            ", array("id_content" => $id_content), true);

        $socialText['title'] = $rs[0]["title_1"];
        $socialText['subtitle'] = $rs[0]["subtitle_1"];

        if($maxTextLength && $socialText['title'] > $maxTextLength){
            $socialText['txt']  = cut($socialText['title'], $maxTextLength,'...',false);
        }else{
            $socialText['txt'] = $socialText['title'];
        }

        $socialText['hashtag'] = "";
        foreach($tokens as $token){
            $hash =  " #".cleanHashtag($token["token"]);
            if(!$maxTextLength || strlen($hash)+strlen($socialText['txt'].$socialText['hashtag']) < $maxTextLength){
                $socialText['hashtag'] .= $hash;
            }
        }

        $section_name = $sqlPDO->queryex("SELECT name_1 FROM cms_sections WHERE id = :id_sections", array("id_sections" =>  $rs[0]["id_sections"]), true);
        if(!empty($section_name)){
            $hash =  " #".cleanHashtag(strtolower($section_name[0]["name_1"]));
            if(!$maxTextLength || strlen($hash)+strlen($socialText['txt'].$socialText['hashtag']) < $maxTextLength){
                $socialText['hashtag'] .= $hash;
            }
        }

        $socialText['msg']  = $socialText['txt'].$socialText['hashtag']." ".$config['sitelink']."/".$id_content."/";
    }


    $socialText['fullLink'] = getFullLink($id_content, true);


    return $socialText;

}

function getFullLink($id, $mobile = false){
    global $sqlPDO, $config;

    $article = $sqlPDO->queryex("SELECT  s.name_1 as s_name, p.name_1 as p_name,cms_contents.id, title_1 FROM cms_contents
                            INNER JOIN cms_sections as s ON s.id = cms_contents.id_sections
                            LEFT OUTER JOIN cms_sections as p ON p.id = s.id_parent
                            WHERE cms_contents.id = :id", array("id" => $id),true);

    $uri = $config['sitelink'];

    if($mobile){
        $uri = str_replace('www.','m.', $uri);
    }

    if(!empty($article[0]['p_name'])){
        $uri .= '/'.clean_url_web($article[0]['p_name']);
    }
    if(!empty($article[0]['s_name'])){
        $uri .= '/'.clean_url_web($article[0]['s_name']);
    }
    $uri .= '/'.$article[0]['id'];

    if(!empty($article[0]['title_1'])){
        $uri .= '/'.clean_url_web($article[0]['title_1']);
    }


    return $uri;
}


function cleanHashtag($string){

    $cleanstring = array();
    preg_match_all('/[0-9_\p{L}]/u', $string, $cleanstring);

    if(!empty($cleanstring)){
        return implode('',$cleanstring[0]);
    }

    return '';

}

function clean_url_web($url,$nodashes = false){

    $unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
        'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
        'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
        'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y');

    $url = strtr($url, $unwanted_array);

    $url  = str_replace(' ', '-', $url);
    $url  = preg_replace('/[^a-zA-Z 0-9\-\/]+/', '-', $url);

    if($nodashes){
        $url  = preg_replace('/\-/', '', $url);
    }
    return strtolower($url);
}


function object_url($object,$prefix = ''){
    global $config, $sql, $this_app;


    if (!empty($object['file_name'])) {

        $d = $this_app->get_session('working_domain');
        if(empty($d)){
           $d = [];
           $d['name'] = 'tio.ch';
           $d['files_public_root'] = 'https://media.tio.ch/files/domains/tio.ch';
           $d['files_root'] = '/data/www/media.tio.ch/public/files/domains/tio.ch';
        }


        $base_domain = stristr($d['files_public_root'], 'https://') ? '' : 'https://'.$d['name'];
        return $base_domain.$d['files_public_root'].'/'.(!empty($object['object_types_path']) ? $object['object_types_path'].'/' : '').(!empty($object['file_path']) ? $object['file_path'].'/': '').$prefix.$object['file_name'];

    }

    if (!empty($object['src'])){
        return 'https://img.tio.ch/tio_common/multimedia/'.substr($object['id_contents'], -5, 2).'/'.substr($object['id_contents'], -3).'/'.$object['src'];
    }
    return '';

}

function get_content_diff($content_from, $content_to){
    global $this_app, $config;
    $dmp = new DiffMatchPatch\DiffMatchPatch();
    $count = 0;


    $diff = [];

    $diff['count'] = 0;
    $diff['out'] = '<div class="textdiff">';


    $d = $this_app->get_session('working_domain');
    if(empty($d)){
        $d = [];
        $d['id'] =$config['domain_id'];
    }


     if(!empty($content_from['id_sections']) && !empty($content_to['id_sections']) && $content_from['id_sections'] != $content_to['id_sections']){
        $sections_from = get_section_by_id($content_from['id_sections'],$d['id']);
        $sections_to = get_section_by_id($content_to['id_sections'],$d['id']);
        $diff['out']     .= '<span class="section diff">La sezione è stata modificata da <del style="background-color: #FC6E51;">'.$sections_from['name'].'</del> a <ins style="background-color:#48CFAD;">'.$sections_to['name'].'</ins></span><br /><br />';
        $diff['count']++;
    }

    if(!empty($content_from['top_title']) || !empty($content_to['top_title'])){
        $toptitle_diff = $dmp->diff_main(
                                empty($content_from['top_title']) ? '' : $content_from['top_title'],
                                empty($content_to['top_title']) ? '' : $content_to['top_title'],
                                false);


        $dmp->diff_cleanupSemantic($toptitle_diff);
        $diff['out']    .= '<span class="editor_toptitle diff">'.$dmp->diff_prettyHtml($toptitle_diff).'</span><br />';
        $diff['count']  += $dmp->diff_levenshtein($toptitle_diff);
    }

    if(!empty($content_from['highlight_text']) || !empty($content_to['highlight_text'])){
        $highlight_diff = $dmp->diff_main(
                                empty($content_from['highlight_text']) ? '' : $content_from['highlight_text'],
                                empty($content_to['highlight_text']) ? '' : $content_to['highlight_text'],
                                false);

        $dmp->diff_cleanupSemantic($highlight_diff);
        $diff['out']    .= '<span class="editor_highlight diff">'.$dmp->diff_prettyHtml($highlight_diff).'</span><br />';
        $diff['count']  += $dmp->diff_levenshtein($highlight_diff);
    }


    if(!empty($content_from['title']) || !empty($content_to['title'])){
        $title_diff = $dmp->diff_main(
                            empty($content_from['title']) ? '' : $content_from['title'],
                            empty($content_to['title']) ? '' : $content_to['title'],
                            false);
        $dmp->diff_cleanupSemantic($title_diff);
        $diff['out']    .= '<span class="editor_title diff">'.$dmp->diff_prettyHtml($title_diff).'</span><br />';
        $diff['count']  += $dmp->diff_levenshtein($title_diff);
    }

    if(!empty($content_from['subtitle']) || !empty($content_to['subtitle'])){
        $subtitle_diff = $dmp->diff_main(
                               empty($content_from['subtitle']) ? '' : $content_from['subtitle'],
                               empty($content_to['subtitle']) ? '' : $content_to['subtitle'],
                               false);
        $dmp->diff_cleanupSemantic($subtitle_diff);
        $diff['out']    .= '<span class="editor_subtitle diff">'.$dmp->diff_prettyHtml($subtitle_diff).'</span><br />';
        $diff['count']  += $dmp->diff_levenshtein($subtitle_diff);
    }

    if(!empty($content_from['content']) || !empty($content_to['content'])){
        $content_diff = $dmp->diff_main(
                              strip_tags(html_entity_decode(empty($content_from['content']) ? '' : $content_from['content'])),
                              strip_tags(html_entity_decode(empty($content_to['content']) ? '' : $content_to['content'])),
                              false);
        $dmp->diff_cleanupSemantic($content_diff);
        $diff['out']    .= '<br /><span class="editor_content diff">'.$dmp->diff_prettyHtml($content_diff).'</span><br />';
        $diff['count']  += $dmp->diff_levenshtein($content_diff);
    }

    if($diff['count'] === 0){
        $diff['out'] = '<div class="textdiff"><span class="editor_content diff">Sono state apportate modifiche di layout al testo</span>';
        $diff['count']++;
    }
    $diff['out'] .= '</div>';


    $diff['out'] = str_replace("&para;","",$diff['out']);

    return $diff;



}

function clean_version_string($string){

    $string = preg_replace("/\r\n|\r|\n/","\r\n",$string);

    return  $string;


}

function percentage_to_rgb($i) {
    // as the function expects a value between 0 and 1, and red = 0° and green = 120°
    // we convert the input to the appropriate hue value
    $hue = $i * 1.2 / 360;
    // we convert hsl to rgb (saturation 100%, lightness 50%)
    $rgb = hslToRgb($hue, 1, 0.5);

    // we format to css value and return
    return 'rgb('.$rgb[0].','.$rgb[1].','.$rgb[2].')'; 
}
function hue2rgb($p, $q, $t){
    if($t < 0){ $t += 1; }
    if($t > 1){ $t -= 1; }
    if($t < 1/6) {
        return $p + ($q - $p) * 6 * $t;
    }
    if($t < 1/2) {
        return $q;
    }
    if($t < 2/3) {
        return $p + ($q - $p) * (2/3 - $t) * 6;
    }
    return $p;
}
function hslToRgb($h, $s, $l){

    if($s == 0){
        $r = $g = $b = $l; // achromatic
    }else{
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        $r = hue2rgb($p, $q, $h + 1/3);
        $g = hue2rgb($p, $q, $h);
        $b = hue2rgb($p, $q, $h - 1/3);
    }

    return [floor($r * 255), floor($g * 255), floor($b * 255)];
}

?>