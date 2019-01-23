<?php

namespace tiolib;


    class outbox {

        private $data = array();
        private $target_url;

        public function __construct($target_url, $account, $password, $commit_key = ''){

            $this->target_url = $target_url;
            $this->data['account'] = $account;
            $this->data['password'] = $password;
            $this->data['messages'] = array();
            $this->data['files'] = array();

            if (!empty($commit_key)){
                $this->data['commit_key'] = $commit_key;
            } else {
                $this->generate_key();
            }

        }

        public function generate_key(){
            $this->data['commit_key'] = substr(sha1($this->data['account'].microtime()), 0, 12);
            return $this->data['commit_key'];
        }

        public function commit($command = 'commit'){

            $this->data['action'] = $command;

            $post = array();
            foreach($this->data['files'] as $k => $f){

                if (file_exists($f)){
                    $post[$k] = new CurlFile($f);
                    $this->data['files'][$k] = basename($f);
                }

            }

            $post['data'] = json_encode($this->data);

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->target_url);
            curl_setopt($curl, CURLOPT_USERAGENT, 'OUTBOX API CLIENT');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // no ssl checkc
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // no check name
            $status_response = curl_exec($curl);
            curl_close($curl);
            return $status_response;

        }

        public function output($command = 'commit'){

            $this->data['action'] = $command;
            unset($this->data['password']);

            header('Content-type: application/json');
            echo json_encode($this->data);

        }

        public function message($type, $recipient, $head, $body = '', $sender = '', $files = array(), $date_schedule = '', $priority = 0, $archive = 0){

            if (!empty($files)){

                for ($i = 0; $i < sizeof($files); $i++){

                     if (!empty($files[$i]['tmp_name'])) {
                        $file_id = sha1($files[$i]['tmp_name']);
                        if (!array_key_exists($file_id, $this->data['files'])){
                            $this->data['files'][$file_id] = $files[$i]['tmp_name'];
                            $this->data['filesNames'][$file_id] = $files[$i]['name'];
                        }
                        
                     } else {
                        $file_id = sha1($files[$i]);
                        if (!array_key_exists($file_id, $this->data['files'])){
                            $this->data['files'][$file_id] = $files[$i];
                        }
                     }       
               
                    //$file_id = sha1($files[$i]['name']);



                    $files[$i] = $file_id;
                }

            }
            //pre($files);die();

            if (empty($sender)){
                $sender = 'noreply@'.$_SERVER['SERVER_NAME'];
            }

            /* ASSEGNAZIONE DI UNA DATA DI PIANIFICA SE NON SPECIFICATA */
            if (empty($date_schedule)){
                $date_schedule = date("Y-m-d H:i").':00';
            }

            $message = array('type' => $type, 'sender' => $sender, 'recipient' => $recipient, 'head' => $head, 'body' => $body, 'files' => $files, 'date_schedule' => $date_schedule, 'priority' => $priority, 'archive' => $archive);

            array_push($this->data['messages'], $message);

        }


    }

?>