<?php

namespace kivweb\Controllers;

use kivweb\Models\DatabaseConnection;
use kivweb\Others\MyLogin;

class SignoutController implements IController
{

    private DatabaseConnection $connection;
    private MyLogin $login;

    public function __construct(DatabaseConnection $connection, MyLogin $login)
    {
        $this->connection = $connection;
        $this->login = $login;
    }

    public function showView(): void
    {
        $controller = new ResultController();

        if(!$this->login->isUserLogged()) {
            $controller->showView(false, "Nelze odhlásit, nejste přihlášen.");
            return;
        }

        $this->login->logout();
        $controller->showView(true, "Jste úspěšně odhlásen.");
    }
}