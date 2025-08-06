<?php

namespace kivweb\Controllers;

use kivweb\Models\DatabaseConnection;
use kivweb\Others\MyLogin;

class SigninController implements IController
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
        $template = 'sign_in.twig';

        $loader = new \Twig\Loader\FilesystemLoader('app/Templates');
        $twig = new \Twig\Environment($loader);

        // render vrati kompletni vyplnenou sablonu pro vypis
        echo $twig->render($template);
    }
}