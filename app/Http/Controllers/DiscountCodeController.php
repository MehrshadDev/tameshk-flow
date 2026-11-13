<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DiscountCodeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', DiscountCode::class);

        return view('pages.discounts.index');
    }
}
