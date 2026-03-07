<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrganizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $settings = $organization->settings ?? [];

        return Inertia::render('Organization/Settings', [
            'organization' => [
                'id'          => $organization->id,
                'name'        => $organization->name,
                'description' => $organization->description,
                'logo'        => $organization->logo,
            ],
            'smtp' => [
                'host'       => $settings['smtp_host'] ?? '',
                'port'       => $settings['smtp_port'] ?? '587',
                'username'   => $settings['smtp_username'] ?? '',
                'password'   => $settings['smtp_password'] ?? '',
                'encryption' => $settings['smtp_encryption'] ?? 'tls',
                'from_address' => $settings['smtp_from_address'] ?? '',
                'from_name'  => $settings['smtp_from_name'] ?? '',
            ],
        ]);
    }

    /**
     * Update SMTP settings.
     */
    public function updateSmtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'host'         => 'nullable|string|max:255',
            'port'         => 'nullable|integer|min:1|max:65535',
            'username'     => 'nullable|string|max:255',
            'password'     => 'nullable|string|max:255',
            'encryption'   => 'nullable|in:tls,ssl,none',
            'from_address' => 'nullable|email|max:255',
            'from_name'    => 'nullable|string|max:255',
        ]);

        $organization = auth()->user()->organization;
        $settings = $organization->settings ?? [];

        // Don't overwrite password if left blank
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $organization->update([
            'settings' => array_merge($settings, [
                'smtp_host'         => $validated['host'] ?? '',
                'smtp_port'         => $validated['port'] ?? '587',
                'smtp_username'     => $validated['username'] ?? '',
                'smtp_password'     => $validated['password'] ?? ($settings['smtp_password'] ?? ''),
                'smtp_encryption'   => $validated['encryption'] ?? 'tls',
                'smtp_from_address' => $validated['from_address'] ?? '',
                'smtp_from_name'    => $validated['from_name'] ?? '',
            ]),
        ]);

        return back()->with('smtp_success', 'SMTP settings saved successfully.');
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
