<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helper\ResponseBuilder;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Carbon;

class CategoryController extends Controller
{
    public function __construct(FirebaseAuth $auth/* ,SignIn $sign */) {
        $this->auth = $auth;
        // $this->sign = $sign;
    }

    public function index()
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        $firestore = new FirestoreClient();

        $collectionReference = $firestore->collection('categories')->documents();
        if($collectionReference == null){
            $status = '404';
            $messages = "Data Is Empty";
            $success = true;    
            return ResponseBuilder::result($status,$messages,null,$success);
        }
        
        $post = [] ;
        foreach ($collectionReference as $arrayPost) {
            if ($arrayPost->exists()) {
                $dataPost = $arrayPost->data();
                $post[] = array(
                    'id' =>$arrayPost->id(),
                    'name'=>$dataPost['name']
                );
            } else {
                echo('Document does not exist!');
            }
        }

        $status = '200';
        $messages = "Success";
        $success = true;
        return ResponseBuilder::result($status,$messages,$post,$success);
    }


}
