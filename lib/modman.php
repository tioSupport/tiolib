<?php

namespace tiolib;

class modman {

    private $name;
    private $site_config;
    private $web_root;
    private $public_root;
    private $data;

    public function __construct($name){
        global $config, $site;

        $this->name = $name;
        $this->site_config = $config;
        $this->site_config['app_prefix'] = $config['app_prefix'];
        $this->web_root = rtrim($this->site_config['web_root'], '/');
        $this->public_root = rtrim($this->site_config['public_path'], '/');

        if (file_exists($this->web_root.'/'.$this->site_config['app_prefix'].'/'.$this->name.'/inc/inc.'.$this->name.'.php'))
            include($this->web_root.'/'.$this->site_config['app_prefix'].'/'.$this->name.'/inc/inc.'.$this->name.'.php');


        if (file_exists($this->web_root.'/'.$this->site_config['app_prefix'].'/'.$this->name.'/css/std.css'))
            if(isset($site)){
                $site->add_css($this->public_root.'/'.$this->site_config['app_prefix'].'/'.$this->name.'/css/std.css?v='.$config['v']);
            }

        if (file_exists($this->web_root.'/'.$this->site_config['app_prefix'].'/'.$this->name.'/js/std.js'))
            if(isset($site)){
                $site->add_js($this->public_root.'/'.$this->site_config['app_prefix'].'/'.$this->name.'/js/std.js?v='.$config['v']);
            }
      }

    public function main_file_url($realpath = false) {
        if ($realpath){
            return $this->web_root.'/'.$this->site_config['app_prefix'].'/'.$this->name.'/'.$this->name.'.php';
        } else {
            return $this->public_root.'/'.$this->site_config['app_prefix'].'/'.$this->name.'/'.$this->name.'.php';
        }
    }

    public function base_path_url() {
        return'?'.$this->site_config['app_prefix'].'='.$this->name;
    }

    public function relative_path() {
        return '/'.$this->site_config['app_prefix'].'/'.$this->name;
    }

    public function files_path() {
        return $this->site_config['files_root'].$this->name;
    }

    public function files_public_path() {
        return $this->site_config['files_public_root'].'/'.$this->name;
    }

    public function absolute_path() {
        return $this->site_config['web_root'].'/'.$this->site_config['app_prefix'].'/'.$this->name;
    }

    public function name() {
        return $this->name;
    }

    public function description() {
        return $this->data['description'];
    }

    public function getIdDomain() {
        return $this->get_session('working_domain_id');
    }

    public function param($param, $len = 255, $int = false, $method = 'all'){

        $all_params = $_REQUEST;
        switch ($method) {
            case 'get':
                $all_params = $_GET;
            case 'post':
                $all_params = $_POST;
        }
        foreach ($all_params as $p => $v){
            if ($p == $param && isset($v)){
                if ($int){
                    if (is_numeric($v)){
                        return $v;
                    }
                } else {
                    if (strlen($v) <= $len){
                        return $v;
                    }
                }
            }
        }
        return false;
    }

    public function set_session($var, $val){
        $_SESSION['intranet_app_'.$this->name.'_'.$var] = $val;
    }

    public function unset_session($var){
        unset($_SESSION['intranet_app_'.$this->name.'_'.$var]);
    }

    public function get_session($var){
        if (!empty($_SESSION['intranet_app_'.$this->name.'_'.$var])){
            return $_SESSION['intranet_app_'.$this->name.'_'.$var];
        } else {
            return '';
        }
    }

    public function set_data($array_data){
        $this->data = $array_data;
    }

    public function set_var($varname, $value){
        $this->data[$varname] = $value;
    }

    public function get_data($name = ''){
        if (!empty($name)){
            return isset($this->data[$name]) ? $this->data[$name] : '';
        } else {
            return $this->data;
        }
    }


    private function check_settings($setting){
            foreach($this->setting_result as $key => $val){
                // Controllo se esiste già un default
                // il setting con il dominio ha la precedenza
                  if (
                        $setting['id_apps'] == $val['id_apps'] &&
                        $setting['key_1'] == $val['key_1'] &&
                        $setting['val_1'] == $val['val_1'] &&
                        $setting['key_2'] == $val['key_2'] &&
                        $setting['key_3'] == $val['key_3'] &&
                        $setting['key_4'] == $val['key_4'] &&
                        $setting['key_5'] == $val['key_5'] &&
                        $setting['key_6'] == $val['key_6'] ){

                        if(!empty($val['id_domains']) && empty($setting['id_domains'])){
                            //se ha la precedenza
                            return true;
                        }

                        unset($this->setting_result[$key]);
                 }
           }

           return false;
    }

    private $setting_result = array();
    public function get_settings($name = '', $id_domains = 0){
        $this->setting_result = array();

        if (!empty($name)){

            if (!empty($this->data['settings'])){

                foreach ($this->data['settings'] as $setting){
                    if ($setting['name'] == $name){
                        if (empty($id_domains) || empty($setting['id_domains']) ||  $id_domains == $setting['id_domains']){
                            // I settings con dominio 0 sono default per tutti i domini
                           if(!$this->check_settings($setting)){
                                array_push($this->setting_result, $setting);
                            }
                        }
                    }
                }
            }
        }
        return $this->setting_result;
    }

    public function get_settingsVal($name,$key='',$nameVal='val_1') {
        $rs = $this->get_settings($name,$this->getIdDomain());
        foreach ($rs as $row) {
            if (trim($row[$nameVal]) == trim($key)) {
                return true;
            }
        }
        return false;
    }

}
?>