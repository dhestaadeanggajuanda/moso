<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MenuLocation;
use App\Http\Controllers\Controller;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MenuController extends Controller
{
    public function show(MenuLocation $location): AnonymousResourceCollection
    {
        return MenuResource::collection(Menu::treeFor($location, publicOnly: true));
    }
}
