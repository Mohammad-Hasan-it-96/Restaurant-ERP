<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;

class DeliveryZoneController extends Controller
{
    public function index(Request $request)
    {
        $query = DeliveryZone::query();

        if ($search = $request->input('search')) {
            $query->where('area_name', 'like', '%' . $search . '%');
        }

        $zones = $query->orderBy('sort_order')->orderBy('id')->paginate(15)->withQueryString();

        return view('admin.delivery_zones.index', compact('zones'));
    }

    public function create()
    {
        return view('admin.delivery_zones.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'area_name'    => 'required|string|max:255',
            'estimated_fee'=> 'required|numeric|min:0',
            'sort_order'   => 'nullable|integer|min:0',
            'is_active'    => 'nullable|boolean',
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active']  = $request->boolean('is_active');

        DeliveryZone::create($validated);

        return redirect()->route('admin.delivery-zones.index')
            ->with('success', __('app.delivery_zone_created'));
    }

    public function edit(DeliveryZone $deliveryZone)
    {
        return view('admin.delivery_zones.edit', compact('deliveryZone'));
    }

    public function update(Request $request, DeliveryZone $deliveryZone)
    {
        $validated = $request->validate([
            'area_name'    => 'required|string|max:255',
            'estimated_fee'=> 'required|numeric|min:0',
            'sort_order'   => 'nullable|integer|min:0',
            'is_active'    => 'nullable|boolean',
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active']  = $request->boolean('is_active');

        $deliveryZone->update($validated);

        return redirect()->route('admin.delivery-zones.index')
            ->with('success', __('app.delivery_zone_updated'));
    }

    public function destroy(DeliveryZone $deliveryZone)
    {
        $deliveryZone->delete();

        return redirect()->route('admin.delivery-zones.index')
            ->with('success', __('app.delivery_zone_deleted'));
    }
}

