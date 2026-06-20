<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomInstructionController extends Controller
{
    public function edit()
    {
        $instruction = auth()->user()->customInstruction;

        return Inertia::render('settings/CustomInstructions', [
            'instruction' => $instruction ? [
                'about_you' => $instruction->about_you,
                'behavior' => $instruction->behavior,
                'is_enabled' => $instruction->is_enabled,
            ] : null,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'about_you' => 'nullable|string|max:2000',
            'behavior' => 'nullable|string|max:2000',
            'is_enabled' => 'boolean',
        ]);

        auth()->user()->customInstruction()->updateOrCreate([], $validated);

        return back();
    }
}