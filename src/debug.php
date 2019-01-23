<?php

namespace tiolib;


$debug_err_count = 0;

function eH($n, $m, $f, $l, $ee){
    global $debug_err_count;
    header("HTTP/1.1 500 Internal Server Error");
    $debug_err_count = $debug_err_count + 1;
    $error = debug_backtrace();

      echo '<div id="debugboxx'.$debug_err_count.'" style="zindex:99;-webkit-border-radius: 0 0 0 5px;-moz-border-radius: 0 0 0 5px;border-radius: 0 0 0 5px;-webkit-box-shadow: 0px 0px 10px rgba(0, 0, 0, 1);-moz-box-shadow:0px 0px 10px rgba(0, 0, 0, 1);box-shadow:0px 0px 10px rgba(0, 0, 0, 1);position:fixed;top:'.(20*$debug_err_count).'px;right:'.(20*$debug_err_count).'px;display:block;height:300px;width:640px;text-align:left;color:#000000;font-family:courier new;font-size:12px;border:1px solid #666666;background-color:#FFFFFF;overflow:auto;">';
      echo '<div style="background-color:#667788;color:#FFFFFF;padding:3px;padding-left:4px;"><b>PHP Debugger beta v0.99 [<b style="cursor:pointer;" onclick="document.getElementById(\'debugboxx'.$debug_err_count.'\').style.display = \'none\';"><u>close</u></b>] [<b style="cursor:pointer;" onclick="window.location.reload()"><u>reload</u></b>] [<b style="cursor:pointer;" onclick="var d'.$debug_err_count.' = document.getElementById(\'debugboxx'.$debug_err_count.'\');d'.$debug_err_count.'.style.width = \'\';d'.$debug_err_count.'.style.height = \'\';d'.$debug_err_count.'.style.right = \'10px\';d'.$debug_err_count.'.style.top = \'10px\';d'.$debug_err_count.'.style.left = \'10px\';d'.$debug_err_count.'.style.bottom = \'10px\';"><u>full screen</u></b>]</b></div>';
    echo '<div style="padding:20px;">';

    echo '<div style="border-bottom:1px dotted #000000;overflow:auto;width:100%;height:80px;">';
      if ($error[0]['args'][1] != 'debug') {
            echo '<b style="font-size:18px;display:block;background:url(/img/warnings.png) 0 0 no-repeat; height:24px;padding:6px;padding-left:44px;">Error: <span style="color:#CC0000;">'.$error[0]['args'][1].'</span></b><br />';
            echo !empty($error[0]['file']) ? '<b>File: </b>'.$error[0]['file'].'<br />' : '';
          echo !empty($error[0]['line']) ? '<b>Line: </b>'.$error[0]['line'].'<br />' : '';
       }
    echo '</div>';
    foreach ($error as $call){
        if (isset($call['file']) && basename($call['file']) != 'class.sql.php'){

            echo '<br />function <b>'.$call['function'].'</b>(<span style="color:#0055BB">'.(!empty($call['args'][0]) ? $call['args'][0] : '').'</span>)<br />';
            echo !empty($call['file']) ? '<div style="font-size:11px;font-family:arial;color:#999999;">&nbsp;&nbsp;&nbsp;&nbsp;<b>File: </b>'.$call['file'].'<br />' : '';
            echo !empty($call['line']) ? '&nbsp;&nbsp;&nbsp;&nbsp;<b>Line: </b>'.$call['line'].'</div >' : '';
        }
    }
    echo '<br /><br /><div id="debug_details_link"><a href="javascript:document.getElementById(\'debug_details_link\').style.display = \'none\';document.getElementById(\'debug_details\').style.display = \'block\';void 0;">dettagli</a></div>';
    echo '<div id="debug_details" style="display:none"><pre>';
    print_r($error);
    echo '</pre></div>';
    echo '</div></div>';
    die();
}

function eHbasic($n, $m, $f, $l, $ee){
    global $debug_err_count;

    header("HTTP/1.1 500 Internal Server Error");
    $debug_err_count = $debug_err_count + 1;
    $error = debug_backtrace();

    echo "<pre style=\"background:#FFF;color:#2D4864;\">\n\nPHP Error \n";

      if ($error[0]['args'][1] != 'debug') {
        echo "\nError: ".$error[0]['args'][1];
            echo !empty($error[0]['file']) ? "\nFile: ".$error[0]['file'] : '';
          echo !empty($error[0]['line']) ? "\nLine: ".$error[0]['line'] : '';
       }

    echo "\n\nCall history \n";

    $count = 0;
    foreach ($error as $call){
        $count++;
        if (isset($call['file']) && $count > 1){ //basename($call['file']) != 'class.sql.php'

            echo "\n  Function ".$call['function'].' ('.(!empty($call['args'][0]) ? $call['args'][0] : '').')';
            //echo (!empty($call['file']) ? "\n  File: ".$call['file'] : '').(!empty($call['line']) ? " Line ".$call['line'].'' : '');


        }
    }

    echo '</pre>';
    die();
}

class errorHandlerBasic {

    public function __construct() {
      set_error_handler('eHbasic');
    }
}

class errorHandler {

    public function __construct() {
      set_error_handler('eH');
    }
}


?>