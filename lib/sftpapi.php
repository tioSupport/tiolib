<?php

namespace tiolib;

class sftpapi {
	private $connection 	= '';
	private $sftp    			= '';
	private $server 			= '';
	private $port					= 22;
	private $username 		= '';
	private $password 		= '';
	public 	$isconnected 	= false;
	public 	$isauth  		 	= false;
	public 	$log 					= '';
	public 	$v						= '1.20160701.1';

	public function __costruct() {}

	public function log($msg) {
		$this->log .= $msg."\r\n";
	}

	public function close() {
		ssh2_exec($this->connection, 'exit');
		$this->log('END - close connection');
		unset($this->connection);
	}

	public function connection($server='',$port='',$username='',$password='') {
		if (empty($server) || empty($port)) {$this->log('CONN - operation fail: server or port missing');return false;}
		$this->server = $server;
		$this->port = $port;
		if ($this->connection = @ssh2_connect($this->server,$this->port)) {
			$this->log('CONN - connection ready');	
			$this->isconnected = $this->auth($username,$password);
		} else {
			$this->log('CONN - connection failed');	
			$this->isconnected = false;
		}
		return $this->isconnected;
	}

	public function auth($username='',$password='') {
		//if (!$this->isconnected) {$this->log('AUTH - operation fail: no connection');return false;}
		if (empty($username) || empty($password)) {$this->log('AUTH - operation fail: usr or pwd missing');return false;}
		$this->username = $username;
		$this->password = $password;
		if (ssh2_auth_password($this->connection,$this->username,$this->password)) {
			$this->log('AUTH - authentication successful');
			$this->isauth = true;
		} else {
			$this->log('AUTH - authentication failed');
			$this->isauth = false;
		}
		$this->sftp = ssh2_sftp($this->connection);
		return $this->isauth;
	}
 
 	public function mkdir($remotedir) {
 		//ssh2_sftp_mkdir($this->sftp,$remotedir);
 		$this->log('MKDIR - try create dir '.$remotedir);
 		if (!$this->isauth) {$this->log('MKDIR - operation fail: no auth');return false;}
 		if (empty($remotedir)) {$this->log('MKDIR - operation fail: remotedir');return false;}
 		
		$fileExists = file_exists('ssh2.sftp://'.$this->sftp.$remotedir);

		if ($fileExists) {$this->log('MKDIR - remotedir already exist');return true;}

 		$sftpMkdir = @mkdir("ssh2.sftp://".$this->sftp.$remotedir,0777,true);
 		if ($sftpMkdir) {
 			$this->log('MKDIR - created');
 			return true;
 		} else {
 			$this->log('MKDIR - failed');
 			return false;
 		}
 	}
	
 	public function send($srcFile ,$dstFile) {
 		//ssh2_scp_send($this->connection,$srcFile,$dstFile,0777);
 		$this->log('SEND - try send file '.$srcFile.' to '.$dstFile);
 		if (!$this->isauth) {$this->log('SEND - operation fail: no auth');return false;}
 		if (empty($srcFile) || empty($dstFile)) {$this->log('SEND - operation fail: srcFile or dstFile missing');return false;}
 		
		$sftpStream = @fopen('ssh2.sftp://'.$this->sftp.$dstFile, 'w');

		try {
		    if (!$sftpStream) {
		        throw new Exception("Could not open remote file: $dstFile");
		    }
		   
		    $data_to_send = @file_get_contents($srcFile);
		   
		    if ($data_to_send === false) {
		        throw new Exception("Could not open local file: $srcFile.");
		    }
		   
		    if (@fwrite($sftpStream, $data_to_send) === false) {
		        throw new Exception("Could not send data from file: $srcFile.");
		    }
		   
		    fclose($sftpStream);
				$this->log('SEND - file trasmited');
		    return true;              
		} catch (Exception $e) {
		    $this->log('SEND - exception: ' . $e->getMessage());
		    fclose($sftpStream);
		    return false;
		} 		
 	}

 	public function del($remoteFile) {
 		//ssh2_sftp_unlink($this->sftp, $remoteFile);
 		$this->log('DEL - try delete file '.$remotedir);
 		if (!$this->isauth) {$this->log('DEL - operation fail: no auth');return false;}
 		if (empty($remotedir)) {$this->log('DEL - operation fail: remoteFile missing');return false;}

 		$sftpUnlink = @unlink('ssh2.sftp://'.$this->sftp.$remoteFile);
 		try {
 			if (!$sftpUnlink) {
		  	throw new Exception("Could not delete file: $remoteFile");
		  }
		  $this->log('DEL - file delete');
		  return true;
 		} catch (Exception $e) {
		    $this->log('DEL - exception: ' . $e->getMessage());
		    return false;
		} 	
 	}

 	public function rmdir($remoteDir) {
 		//ssh2_sftp_mkdir($this->sftp,$remotedir);
 		$this->log('RMDIR - try delete dir '.$remotedir);
 		if (!$this->isauth) {$this->log('RMDIR - operation fail: no auth');return false;}
 		if (empty($remotedir)) {$this->log('RMDIR - operation fail: remoteDir missing');return false;}
 		
		$fileExists = file_exists('ssh2.sftp://'.$this->sftp.$remotedir);

		if (!$fileExists) {$this->log('RMDIR - remotedir not exist');return true;}

 		$sftpRmdir = @rmdir("ssh2.sftp://".$this->sftp.$remotedir,0777,true);
 		if ($sftpRmdir) {
 			$this->log('RMDIR - created');
 			return true;
 		} else {
 			$this->log('RMDIR - failed');
 			return false;
 		}
 	} 	
}

?>