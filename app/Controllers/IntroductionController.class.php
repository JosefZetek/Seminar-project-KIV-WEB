<?php

namespace kivweb\Controllers;

use kivweb\Models\DatabaseConnection;
use kivweb\Others\MyLogin;

class IntroductionController implements IController
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
        $articles = $this->connection->getArticles();
        $template = 'articles_overview.twig';

        $loader = new \Twig\Loader\FilesystemLoader('app/Templates');
        $twig = new \Twig\Environment($loader);

        $articles_array = array("articles_array" => $articles);
        echo $twig->render($template, $articles_array);
    }
}