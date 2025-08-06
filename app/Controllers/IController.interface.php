<?php

namespace kivweb\Controllers;

interface IController
{
    /**
     * Shows the view that is linked to the controller
     */
    public function showView(): void;
}