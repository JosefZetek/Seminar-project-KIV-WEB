<?php

namespace kivweb\Others;

class MyLogin
{
    private $ses;

    private const SESSION_KEY = "usr";
    private const KEY_NAME = "jm";

    public function __construct()
    {
        require_once("MySessions.class.php");
        $this->ses = new MySession;
    }

    public function isUserLogged(): bool
    {
        return $this->ses->isSessionSetUp(self::SESSION_KEY);
    }

    public function login(string $userName)
    {
        $data = [self::KEY_NAME => $userName];
        $this->ses->setUpSession(self::SESSION_KEY, $data);
    }

    public function logout()
    {
        $this->ses->removeSession(self::SESSION_KEY);
    }

    public function getUserEmail()
    {
        if (!$this->isUserLogged()) {
            return null;
        }

        $d = $this->ses->readSession(self::SESSION_KEY);
        return $d[self::KEY_NAME];
    }
}

?>
