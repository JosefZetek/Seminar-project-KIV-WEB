<?php

namespace kivweb\Others;


class MySession {

    public function __construct(){
        session_start();
    }

    public function setUpSession(string $key, $value){
        $_SESSION[$key] = $value;
    }

    public function isSessionSetUp(string $key):bool {
        return isset($_SESSION[$key]);
    }

    public function readSession(string $key){
        if(!$this->isSessionSetUp($key)){
            return null;
        }
        return $_SESSION[$key];
    }

    public function removeSession(string $key){
        unset($_SESSION[$key]);
    }
}
?>
