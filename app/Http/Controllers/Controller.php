<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public static function paginate_array($array, $per_page=10, $page=null, $options=[]){
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        $page = intval($page);
        $per_page = intval($per_page);
        $items = $array instanceof Collection ? $array : Collection::make($array);
        return new LengthAwarePaginator($items->forPage($page, $per_page), $items->count(), $per_page, $page, $options);
    }
}
