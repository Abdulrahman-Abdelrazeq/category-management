<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Requests\CategoryRequest;
use App\Traits\Response;

class CategoryController extends Controller
{
    use Response;

    /**
     * Display a page listing all categories.
     */
    public function index() {
        return view('categories.index');
    }
}
