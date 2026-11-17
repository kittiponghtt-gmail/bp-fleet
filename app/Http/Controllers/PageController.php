<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class PageController extends Controller
{
    public function fleet(): Response
    {
        $path = base_path('BP_Fleet_Management_System_TH_EN.html');
        $html = file_get_contents($path);
        $token = '<script>window.CSRF='.json_encode(csrf_token()).';</script>';
        $html = preg_replace('/<body>/', '<body>'.$token, $html, 1);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
