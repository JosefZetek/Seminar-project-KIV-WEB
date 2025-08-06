<?php

namespace kivweb\Controllers;

use kivweb\Models\DatabaseConnection;
use kivweb\Others\MyLogin;

class ResultController
{
    public function showView(bool $result, string $message): void
    {
        $loader = new \Twig\Loader\FilesystemLoader('app/Templates');
        $twig = new \Twig\Environment($loader);

        $template = 'result_view.twig';

        $attributes = array(
            "result" => $result,
            "message" => $message
        );

        echo $twig->render($template, $attributes);
    }
}