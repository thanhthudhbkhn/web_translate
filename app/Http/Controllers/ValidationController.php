<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Keyword;
use Illuminate\Support\Facades\DB;

class ValidationController extends Controller
{
    public function checkUniqueKeyword(Request $request)
    {
        $keyword = DB::select('select * from wt_keyword where keyword REGEXP BINARY ?', ['^'.$request->keyword]);
        $result = (count($keyword) >= 1) ? true : false;
        return $result;
    }
}
