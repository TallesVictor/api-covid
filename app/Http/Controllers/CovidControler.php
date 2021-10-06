<?php

namespace App\Http\Controllers;

use App\Models\Covid;
use Illuminate\Http\Request;

class CovidControler extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $param)
    {
        $covid = new Covid();
        $covid = $covid->ranking($param);
        if($covid['id']==1){
            return response($covid['Status'], 401);
        }
        return response()->json($covid);
    }

}
