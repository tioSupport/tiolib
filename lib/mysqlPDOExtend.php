<?php
namespace tiolib;


	class mysqlPDOExtend
	{
	    protected $_instance;

	    public function myMethod() {
	        return strtoupper( $this->_instance->someMethod() );
	    }

	    public function __construct(mysqlPDO $instance) {
	        $this->_instance = $instance;
	    }

	    public function __call($method, $args) {
	        return call_user_func_array(array($this->_instance, $method), $args); 
	    }

	    public function __get($key) {
	        return $this->_instance->$key;
	    }

	    public function __set($key, $val) {
	        return $this->_instance->$key = $val;
	    }

 



	    public function q($qry,$params=array(),$fetch = true, $fetchall = true) {
	        /* v.pfi.20150925.1 */
	        switch (strtolower(substr($qry,0,6))) {
	            case 'insert': 
	                $this->queryex($qry,$params,false);

	                return $this->insert_id();
	                break;
	            case 'update': 
	                $this->queryex($qry,$params,false);
	                return $this->insert_id();
	                break;
	            case 'delete': 
	                $this->queryex($qry,$params,false);
	                return true;
	                break;                
	            case 'select':
	            		return $this->queryex($qry,$params,$fetch,$fetchall);
	                break;
	            default: 
	                return false;
	                break;
	        }        
	    } 
	  
	}

?>