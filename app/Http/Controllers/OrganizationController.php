<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrganizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    /**
     * Show the organization settings form.
     */
    public function edit(): Response
    {
        $organization = auth()->user()->organization;

        return Inertia::render('Organization/Settings', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'description' => $organization->description,
                'logo' => $organization->logo,
            ],
        ]);
    }

    /**
     * Update the organization information.
     */
    public function update(UpdateOrganizationRequest $request): RedirectResponse
    {
        $organization = auth()->user()->organization;

        $validated = $request->validated();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($organization->logo) {
                Storage::disk('public')->delete($organization->logo);
            }

            // Store new logo
            $logoPath = $request->file('logo')->store('logos', 'public');
            $validated['logo'] = $logoPath;
        }

        $organization->update($validated);

        return back()->with('success', 'Organization updated successfully.');
    }
}
