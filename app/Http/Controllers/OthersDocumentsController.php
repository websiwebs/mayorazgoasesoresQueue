<?php

namespace App\Http\Controllers;

use Smalot\PdfParser\Parser;
use Spatie\PdfToText\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;
use App\Models\OtherDocument;
use App\Models\User;
use DB;
use ZipArchive;
use Illuminate\Support\Facades\Auth;

class OthersDocumentsController extends Controller
{

    public function uploadForm()
    {
        return view('othersdocuments.uploadForm');
    }

    public function uploadOthersDocuments(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
        $nif = $request->input('nif');
        $userid = DB::Table('users')->where('nif', $nif)->select('id');

        if (User::where('nif', $nif)->exists()) {

            if ($request->hasFile('othersdocuments')) {

                $path = public_path('/storage/media/othersDocuments/' . $year);

                if (!File::exists($path)) {
                    File::makeDirectory($path, 0777, true);
                    $path = public_path('/storage/media/othersDocuments/' . $year . '/' . $month);
                    File::makeDirectory($path, 0777, true);
                    $path = public_path('/storage/media/othersDocuments/' . $year . '/' . $month . '/' . $nif);
                    File::makeDirectory($path, 0777, true);

                    $files = $request->file('othersdocuments');


                    foreach ($files as $index) {

                        $check = DB::Table('users')
                            ->join('others_documents', 'others_documents.user_id', '=', 'users.id')
                            ->where('others_documents.year', '=', $year)
                            ->where('others_documents.month', '=', $month)
                            ->where('users.nif', '=', $nif)
                            ->select('others_documents.filename')
                            ->exists();

                        if ($check) {
                            $name = $index->getClientOriginalName();
                            $index->storeAs('storage/media/othersDocuments/' . $year . '/' . $month . '/' . $nif, $name);
                            $otherDocument = new OtherDocument();
                            $otherDocument->user_id = $userid;
                            $otherDocument->month = $month;
                            $otherDocument->year = $year;
                            $otherDocument->save();
                        } else {
                            $name = $index->getClientOriginalName();
                            $index->storeAs('storage/media/othersDocuments/' . $year . '/' . $month . '/' . $nif, $name);
                            $otherDocument = new OtherDocument();
                            $otherDocument->user_id = $userid;
                            $otherDocument->month = $month;
                            $otherDocument->year = $year;
                            $otherDocument->save();
                        }
                    }
                } else {
                    $path = public_path('/storage/media/othersDocuments/' . $year . '/' . $month);

                    if (!File::exists($path)) {
                        File::makeDirectory($path, 0777, true);
                        $path = public_path('/storage/media/othersDocuments/' . $year . '/' . $month . '/' . $nif);
                        File::makeDirectory($path, 0777, true);

                        $files = $request->file('othersdocuments');

                        foreach ($files as $index) {
                            $name = $index->getClientOriginalName();
                            $index->storeAs('storage/media/othersDocuments/' . $year . '/' . $month . '/' . $nif, $name);
                            $otherDocument = new OtherDocument();
                            $otherDocument->user_id = $userid;
                            $otherDocument->month = $month;
                            $otherDocument->year = $year;
                            $otherDocument->save();
                        }
                    } else {
                        $path = public_path('/storage/media/othersDocuments/' . $year . '/' . $month . '/' . $nif);
                        if (!File::exists($path)) {
                            File::makeDirectory($path, 0777, true);
                            $files = $request->file('othersdocuments');

                            foreach ($files as $index) {
                                $name = $index->getClientOriginalName();
                                $index->storeAs('storage/media/othersDocuments/' . $year . '/' . $month . '/' . $nif, $name);
                                $otherDocument = new OtherDocument();
                                $otherDocument->user_id = $userid;
                                $otherDocument->month = $month;
                                $otherDocument->year = $year;
                                $otherDocument->save();
                            }
                        } else {
                            $files = $request->file('othersdocuments');

                            foreach ($files as $index) {
                                $name = pathinfo($index, PATHINFO_FILENAME) . '+1';
                                $extension = $index->getClientOriginalExtension();
                                $index->storeAs('storage/media/othersDocuments/' . $year . '/' . $month . '/' . $nif, $name . $extension);
                                $otherDocument = new OtherDocument();
                                $otherDocument->user_id = $userid;
                                $otherDocument->month = $month;
                                $otherDocument->year = $year;
                                $otherDocument->save();
                            }
                        }
                    }
                }
            } else {
                echo '<div class="alert alert-warning"><strong>Warning!</strong> No has añadido ningun archivo aún.</div>';

                return view('othersdocuments.uploadForm');
            }

            return view('othersdocuments.uploadForm')->with('successMsg', "Los documentos de imputación de costes se han subido correctamente, gracias ;)");
        } else {
            echo '<div class="alert alert-warning"><strong>Warning!</strong>El ' . $nif . 'corresponde a una empresa que no ha sido creada aún.</div>';
        }
    }

    public function downloadForm()
    {
        return view('othersdocuments.downloadForm');
    }

    public function downloadList(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        $othersdocuments = DB::Table('users')
            ->join('others_documents', 'others_documents.user_id', '=', 'users.id')
            ->where('others_documents.year', '=', $year)
            ->where('other_documents.month', '=', $month)
            ->where('users.nif', '=', Auth::user()->nif)
            ->get()
            ->toArray();

        return view('othersdocuments.downloadList', compact('othersdocuments', 'month', 'year'));
    }

    public function downloadOthersDocuments(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        $request->validate([
            'othersDocuments' => 'required|min:1'
        ]);

        $othersDocuments = $request->input('othersDocuments');

        if ($othersDocuments != null) {


            if ($month || $year != null) {

                $zipFilename = Auth::user()->nif . '_' . $month . $year . '.zip';
                $zip = new ZipArchive;

                $public_dir = public_path('storage/media/othersDocuments/' . $year . '/' . $month . '/' . Auth::user()->nif);

                if ($zip->open($public_dir . '/' . $zipFilename, ZipArchive::CREATE) === TRUE) {
                    foreach ($othersDocuments as $index) {
                        $temp = (array_values((array)$index))[0];
                        $zip->addFile($public_dir . '/' . $temp, $temp);
                    }
                    $zip->close();
                }

                if (file_exists($public_dir . '/' . $zipFilename)) {
                    return response()->download(public_path('storage/media/othersDocuments/' . $year . '/' . $month . '/' . Auth::user()->nif . '/' . $zipFilename))->deleteFileAfterSend(true);
                }
            } else {
                echo '<div class="alert alert-warning"><strong>Warning!</strong> Debes elegir un mes y un año.<div>';
            }
        } else {
            echo '<div class="alert alert-warning"><strong>Warning!</strong> Hemos detectado un error, vuelva a intentarlo, gracias ;)<div>';
        }

        $othersdocuments = DB::Table('users')
            ->join('others_documents', 'others_documents.user_id', '=', 'users.id')
            ->where('others_documents.year', '=', $year)
            ->where('other_documents.month', '=', $month)
            ->where('users.nif', '=', Auth::user()->nif)
            ->get()
            ->toArray();

        if ($othersdocuments != null) {
            return view('othersdocuments.downloadList', compact('othersdocuments', 'month', 'year'));
        } else {
            return view('othersdocuments.downloadList', compact('No hay documentos en éstas fechas.'));
        }

        return view('othersdocuments.downloadList', compact('othersdocuments', 'month', 'year'));
    }

    public function showForm()
    {
        return view('othersdocuments.showForm');
    }

    public function showOthersDocuments(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        $othersdocuments = DB::Table('users')
            ->join('others_documents', 'others_documents.user_id', '=', 'users.id')
            ->where('others_documents.year', '=', $year)
            ->where('other_documents.month', '=', $month)
            ->select('users.nif', 'others_documents.filename', 'others_documents.year', 'other_documents.month')
            ->paginate(10);

        if ($othersdocuments[0] != null) {
            return view('othersdocuments.showOtherDocuments', compact('othersdocuments', 'month', 'year'));
        } else {
            echo '<div class="alert alert-warning">No hay documentos en ' . $month . $year . '<div>';
        }
    }

    public function deleteOthersDocuments(OtherDocument $otherdocument, $month, $year)
    {
        $otherdocument->delete();

        $othersdocuments = DB::Table('others_documents')->where('year', $year)->where('month', $month)->get()->toArray();
        unlink(public_path('/storage/media/othersDocuments/' . $year . '/' . $month . '/' . $otherdocument->nif . '/' . $otherdocument->filename));


        return view('othersdocuments.showForm', compact('othersdocuments'));
    }

    public function deleteAllOtherDocuments($month, $year)
    {

        DB::Table('others_documents')->where('year', $year)->where('month', $month)->delete();

        File::deleteDirectory(public_path('/storage/media/othersDocuments/' . $year . '/' . $month));

        return view('othersdocuments.showForm');
    }
}
