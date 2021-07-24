<?php 

use Coolpraz\PhpBlade\Application;

if (! function_exists('view')) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param  string   $view
     * @param  array    $data
     * @param  array    $mergeData
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    function view($view = null, $data = [], $mergeData = [])
    {
        $app = Application::getInstance();

        return $app['view']->make($view, $data, $mergeData);
    }
}