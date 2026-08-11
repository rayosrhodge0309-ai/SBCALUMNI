<?php

namespace App\Http\Controllers;

use App\Services\AlumniImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AlumniImportController extends Controller
{
    public function store(Request $request, AlumniImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240|extensions:csv,txt,tsv,xlsx',
            'replace_existing' => 'nullable|boolean',
        ]);

        try {
            $replaceExisting = $request->has('replace_existing')
                ? $request->boolean('replace_existing')
                : true;

            $summary = $importService->import($request->file('file'), $replaceExisting);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'file' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('alumni.index')->with(
            'success',
            "Import completed. Created: {$summary['created']}, Updated: {$summary['updated']}, Removed: {$summary['deleted']}, Skipped: {$summary['skipped']}."
        );
    }
}
