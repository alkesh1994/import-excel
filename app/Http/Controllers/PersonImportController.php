<?php

namespace App\Http\Controllers;

use App\Imports\PersonImport;
use Excel;
use Illuminate\Http\Request;

class PersonImportController extends Controller
{
    // This function will import rows and throw validation
    public function import()
    {
        try {
            request()->validate([
                'file' => ['required', 'mimes:xlsx'],
            ]);
            $id = now()->unix();
            session(['import' => $id]);
            Excel::queueImport(new PersonImport($id), request()->file('file')->store('temp'));
            return redirect()->back();
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            //throw validation errors
            $failures = $e->failures();
            return view('home', compact('failures'));
        }

    }
    // send the current status of import
    public function status()
    {
        $id = session('import');
        //sending data to progress which are stored from event
        $data = [
            'started'     => filled(cache("start_date_$id")),
            'finished'    => filled(cache("end_date_$id")),
            'current_row' => (int) cache("current_row_$id"),
            'total_rows'  => (int) cache("total_rows_$id"),
        ];

        return response()->json($data, 200);
    }
}
