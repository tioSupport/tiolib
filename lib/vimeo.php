<?php

namespace tiolib;


if(isset($config)){
	require_once($config['web_root']. '/ext/vimeo/vendor/autoload.php');
}else{
	  throw new Exception('config file nont loaded.');
}

function fetch_ip() { if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])) { return $_SERVER['HTTP_CF_CONNECTING_IP']; } return $_SERVER['REMOTE_ADDR']; }


function mod_form() {
	$md = '<html><header>
		<script type="text/javascript">
			function filechange(el) {
				if (el.files[0].size > 1024*1024*6)
				alert("File troppo grande"+el.files[0].size);
			}
		</script>
	</header><body>';
	$md .= '<form action="upload.php" method="post" enctype="multipart/form-data">';
	$md .= '<label for="file">Filename:</label>';
	$md .= '<input type="file" name="f" id="f" onchange="filechange(this)"><br>';
	$md .= '<input type="submit" name="submit" value="Submit">';
	$md .= '</form>';
	$md .= '</body></html>';
	return $md;
}



function mod_vimeo_authentication() {
	global $config;

	$config_path = $config['web_root'].'/ext/vimeo/config.'.$config['site'].'.json';

	$config_vimeo = json_decode(file_get_contents($config_path), true);
	return new \Vimeo\Vimeo($config_vimeo['client_id'], $config_vimeo['client_secret'], $config_vimeo['access_token']);
}


function mod_vimeo_upload($lib,$filename) {
	global $this_video;
	try {
		//$this_video['filename'] = $filename;
		//$this_video['api_video_uri']  = $lib->upload($this_video['filename']); // upload video

		$videotitle = 'Video: '.date('YmdHin');

		if (!empty($_GET['id_contents'])) {
			$videotitle = $_GET['id_contents'];
		}

		if (!empty($_GET['title'])) {
			$videotitle .= ' '.$_GET['title'];
		}

		$this_video['filename'] = $filename;
		$this_video['api_video_uri']  = $lib->upload($this_video['filename'], true); // upload video

		$this_video['responsetitle'] =  $lib->request($this_video['api_video_uri'], array(
				'name' => $videotitle
			),'PATCH');

		//mod_vimeo_request($lib,$this_video['api_video_uri'] ); // verify status
		$this_video['cmd'] = 'upload';
	}
	catch (VimeoUploadException $e) {
		$this_video['error'] = $e->getMessage();
	}

	if (!empty($this_video['api_video_uri'])) {
		return $this_video['api_video_uri'];
	} else {
		//$this_video['error'] = 'api_video_uri not found';
		return '';
	}
}


function checkVimeoStatus($status, $timediff){

	switch($status){
			case "208" : //sta effettuando la modifica
						if($timediff > 300){
							return  true;
						}
						break;
			case "200" : //vimeo risponde correttamente ma il video non e' ancora stato convertito, aspetta 1 minuto
						if($timediff > 60){
							return  true;
						}
						break;
			case "429" : //raggiunto il limite di connessioni, aspetta un'ora
						if($timediff > 3600){
							return  true;
						}
						break;
			case "408" : //vimeo in timeout, riprova tra 10 minuti
						if($timediff > 600){
							return  true;
						}
						break;
			case "404" : //errore sconosciuto, riprova tra 5 minuti
						if($timediff > 300){
							return  true;
						}
						break;
			default :
						if($timediff > 300){
							return  true;
						}
						break;
	}

	return false;

}

function vimeo_limit_check(){
	global $sqlPDO;
	$sqlPDO->queryex("DELETE FROM vimeo_error_limit WHERE created < date_sub(now(), INTERVAL 2 DAY)", array(), false);

	$rs = $sqlPDO->queryex("SELECT
		rate_limit,
		rate_limit_remaining,
		last_status,
		DATE_ADD(
			rate_limit_reset,
			INTERVAL 1 HOUR
		),
		created
	FROM
		vimeo_error_limit
	WHERE
		id = (
			SELECT
				max(id)
			FROM
				vimeo_error_limit
		)
	AND rate_limit_reset IS NOT NULL
	AND NOW() > DATE_ADD(
		rate_limit_reset,
		INTERVAL 1 HOUR
	)", array(), true);


	if(!empty($rs)){
			if($rs[0]["rate_limit_remaining"] < 10){
				return true;
			}
	}


	return false;


}

function vimeo_limit_update($video_data){
	global $sqlPDO;

	$params = [];
	$params["called_by"] ='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$params["called_ip"] = fetch_ip();

	$params["last_status"] =  !empty($video_data["status"]) ? (string)$video_data["status"] : "404";

	if(!empty($video_data["headers"]) && !empty($video_data["X-RateLimit-Limit"])){
		$headers = $video_data["headers"];
		$params["rate_limit_remaining"] = (string)$headers["X-RateLimit-Remaining"];
		$params["rate_limit_reset"] = (string)$headers["X-RateLimit-Reset"];
		$params["rate_limit"] = (string)$headers["X-RateLimit-Limit"];

	}else{
		$params["rate_limit_remaining"] = NULL;
		$params["rate_limit_reset"] = NULL;
		$params["rate_limit"] = NULL;
	}

	$sqlPDO->queryex("INSERT INTO vimeo_error_limit (created,called_by,called_ip,rate_limit_remaining,rate_limit_reset,rate_limit, last_status) VALUES (NOW(), :called_by, :called_ip, :rate_limit_remaining, :rate_limit_reset, :rate_limit, :last_status)", $params, false);

}

function mod_vimeo_request($lib,$cmd) {
	global $this_video, $sqlPDO, $config;

	$src = !empty($_GET['r']) ? $_GET['r'] : "";

	if(empty($src)){
		$src = !empty($_GET['f']) ? $_GET['f'] : "";
	}

	if(!empty($src)){
		try {
			$video_data = $lib->request($cmd); // request protocol
			vimeo_limit_update($video_data);

			if (!empty($video_data['body']['status']) && $video_data['body']['status'] == 'transcoding_error') {

				$rs = $sqlPDO->queryex('SELECT cms_objects.id, cms_objects.param, cms_objects.modified, cms_rel_contents_objects.id_contents as id_content FROM cms_objects INNER JOIN cms_rel_contents_objects ON cms_rel_contents_objects.id_objects = cms_objects.id WHERE cms_objects.id_object_types = 7 AND cms_objects.src = :src', array('src' => 'vimeo:'.$src), true);

				$id = 0;
				if(!empty($rs)){
					$sqlPDO->queryex('DELETE FROM cms_objects WHERE src = :src', array('src' => 'vimeo:'.$src), false);
					url_get_contents($config["public_path"]."/ext/api.php?post=content&id=".$rs[0]["id_content"]);
					$id = $rs[0]["id_content"];
				}

				$outbox = new outbox($config['outbox_api'], $config['outbox']['usr'], $config['outbox']['pwd']);
				$recipients = [];
				$recipients[] = ["addr" => "cronaca@tio.ch"];
	            $recipients[] = ["addr" => "support@tio.ch"];

	            $body = "Il video <a href='".$video_data['body']['link_secure']."'>".$video_data['body']['link_secure']."</a>";

	            if(!empty($id)){
	            	$body .=" dell'articolo <a href='http://www.tio.ch/".$id."'>http://www.tio.ch/".$id."</a> è stato rimosso a causa di";
	            }else{
	            	$body .= " ha riscontrato un errore";
	            }

	            $body .="  <strong>".$video_data['body']['status']."</strong>.";

				$outbox->message("email", $recipients, "ERRORE VIDEO VIMEO [".$video_data['body']['name']."]", $body, "support@tio.ch", '', '', 1, 0);
				$outbox->commit();
			}

			if (empty($video_data['body']['status']) || $video_data['status']!='200') {
				$this_video['status'] = empty($video_data['body']['status']) ? "status not found" : $video_data['status'];
				return;
			}
			$this_video['uri'] = $video_data['body']['uri'];
			$this_video['status' ] = $video_data['status'];
			$this_video['link'] = $video_data['status']==200  && !empty($video_data['body']['link_secure']) ? $video_data['body']['link_secure'] :''; //  Pull the link out of successful data responses.
			$this_video['files'] = !empty($video_data['body']['files']) ? $video_data['body']['files'] :'';
			$this_video['cmd'] = 'request';
		}
		catch (VimeoUploadException $e) {
				$this_video['error'] = $e->getMessage();
		}
	}
	return;
}


?>