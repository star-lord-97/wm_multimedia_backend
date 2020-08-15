<?php

namespace App\Http\Controllers;

use App\File;
use App\User;
use Illuminate\Http\Request;
use phpseclib\Crypt\RSA;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($userId)
    {
        // get the current user obj from db
        $user = User::firstWhere('id', $userId);

        // get his public key to search with
        $publicKey = $user->public_key;

        // get all the files with his public key
        $files = File::where('uploader_public_key', $publicKey)->get(['id', 'title', 'status', 'link']);

        // return the files data in a json format
        return response()->json($files);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // create new rsa object
        $rsa = new RSA();

        // get fakepath from $request
        $fakepath = $request->json()->get("file");

        // get the file name from $request
        $x = explode('fakepath\\', $fakepath);
        $file_name = end($x);

        // get extension from $request
        $y = explode('.', $fakepath);
        $ext = end($y);

        // get dir from $file_name
        $dir = "C:\Users\\" . getenv("username") . "\Desktop\SafeBox Uploads\\" . $file_name;

        // adding the corresponding header to each content type and getting the file's content
        if (strtolower($ext) == "pdf") {
            $new_file_content = file_get_contents($dir);
            header("Content-Type: application/pdf");
        } elseif (strtolower($ext) == "doc") {
            $new_file_content = file_get_contents($dir);
            header("Content-Type: application/msword");
        } elseif (strtolower($ext) == "docx") {
            $new_file_content = file_get_contents($dir);
            header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
        } elseif (strtolower($ext) == "txt") {
            $new_file_content = file_get_contents($dir);
            header("Content-Type: text/plain");
        } elseif (strtolower($ext) == "jpg" || strtolower($ext) == "jpeg") {
            $new_file_content = file_get_contents($dir);
            header("Content-Type: image/jpeg");
        } else {
            $new_file_content = file_get_contents($dir);
        }

        // getting all the users and files for iterating over them
        $users = User::all();
        $files = File::all();

        // iterating over all users one by one
        foreach ($users as $user) {
            // getting the current iteration user's private key
            $privatekey = $user->private_key;

            // iterating over files one by one
            foreach ($files as $file) {
                // getting the current iteration file's uploader public key
                $publickey = $file->uploader_public_key;

                // get the current iteration file content
                $content = file_get_contents($file->dir);

                // loading the current user private key to sign the current iteration file
                $rsa->loadKey($privatekey);
                $signature = $rsa->sign($content);

                // loading the uploader's public key to compare the new file's content with the current file's signature
                $rsa->loadKey($publickey);
                if ($rsa->verify($new_file_content, $signature)) {
                    // if verified AKA already in the db, return a message with the uploader's name and exits
                    return response("file already uploaded by " . $user->name);
                    exit;
                }
            }
        }

        // if not verified AKA not on the db, add it to the db, we leave 'link' blank and add it after to contain the id in the link
        $new_file = File::create([
            'title' => $request->json()->get('name'),
            'extension' => $ext,
            'dir' => $dir,
            'uploader_public_key' => $request->json()->get("public_key"),
            'status' => $request->json()->get("status"),
            'link' => ""
        ]);

        // generating the link and adding it to the db
        $link = "http://127.0.0.1:8000/api/file/" . $new_file->id;
        $new_file->update(['link' => $link]);

        // returning a message to the front-end to OK everything
        return response("uploaded");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($fileId)
    {
        // find the file with the id in the link
        $file = File::firstWhere('id', $fileId);

        // get the file's title from the db
        $title = $file->title;

        // get the file's extension from the db
        $extension = $file->extension;

        // get the file content from the db
        $content = file_get_contents($file->dir);

        // get the directory in which the file will be downloaded
        $dir = "C:\Users\\" . getenv("username") . "\Downloads\SafeBox Downloads\\" . $title . "." . $extension;

        // put the file contents in the file directory
        file_put_contents($dir, $content);

        // returning an OK message
        return response("downloaded");
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
