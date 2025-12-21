<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSiteBuild;
use App\Models\BuildGroup;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BuildGroupController extends Controller
{
    public function index()
    {
        $groups = BuildGroup::with(['sites', 'user'])
            ->withCount('sites')
            ->orderByDesc('created_at')
            ->paginate(15);
            
        $allSites = \App\Models\MySite::select('id', 'site_name')->orderBy('site_name')->get();

        return Inertia::render('BuildGroups/Index', [
            'groups' => $groups,
            'allSites' => $allSites,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'site_ids' => 'nullable|array',
            'site_ids.*' => 'exists:my_site,id',
        ]);

        $group = BuildGroup::create([
            'name' => $request->name,
            'description' => $request->description,
            'user_id' => auth()->id(),
        ]);

        if ($request->has('site_ids')) {
            $group->sites()->sync($request->site_ids);
        }

        return redirect()->back()->with('success', 'Build Group created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'site_ids' => 'nullable|array',
            'site_ids.*' => 'exists:my_site,id',
        ]);

        $group = BuildGroup::findOrFail($id);
        
        // Authorization check could go here (e.g. only owner or admin)
        
        $group->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->has('site_ids')) {
            $group->sites()->sync($request->site_ids);
        }

        return redirect()->back()->with('success', 'Build Group updated successfully.');
    }

    public function destroy($id)
    {
        $group = BuildGroup::findOrFail($id);
        $group->delete();

        return redirect()->back()->with('success', 'Build Group deleted successfully.');
    }

    public function build(Request $request, $id)
    {
        $group = BuildGroup::with('sites')->findOrFail($id);

        if ($group->sites->isEmpty()) {
            return redirect()->back()->with('warning', 'No sites in this group to build.');
        }

        $triggeredCount = 0;
        foreach ($group->sites as $site) {
            ProcessSiteBuild::dispatch($site->id, auth()->id());
            $triggeredCount++;
        }

        return redirect()->back()->with('success', "Triggered build for {$triggeredCount} sites.");
    }
}
