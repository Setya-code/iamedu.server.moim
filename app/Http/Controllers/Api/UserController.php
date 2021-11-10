<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helper\ResponseBuilder;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Kreait\Firebase\Auth as FirebaseAuth;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Carbon;

class UserController extends Controller
{
    public function __construct(FirebaseAuth $auth) {
        $this->auth = $auth;
    }

    public function getdata(Request $request)
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        
        $firestore = new FirestoreClient();
        $userId = $request->get('user_id');

        $collectionReference = $firestore->collection('users');
        $documentReference = $collectionReference->document($userId);
        if(empty($collectionReference->data())){
            $status = '404';
            $messages = "Data Is Empty";
            $success = true;    
            return ResponseBuilder::result($status,$messages,null,$success);
        }
        $snapshot = $documentReference->snapshot();

        $status = '200';
        $messages = "Success";
        $success = true;
        return ResponseBuilder::result($status,$messages,$snapshot->data(),$success);
    }

    public function index(Request $request)
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        
        $firestore = new FirestoreClient();
        $userId = $request['id'];

        $collectionReference = $firestore->collection('users');
        $documentReference = $collectionReference->document($userId);
        if(empty($collectionReference->data())){
            $status = '404';
            $messages = "Data Is Empty";
            $success = true;    
            return ResponseBuilder::result($status,$messages,null,$success);
        }
        $snapshot = $documentReference->snapshot();

        $status = '200';
        $messages = "Success";
        $success = true;
        return ResponseBuilder::result($status,$messages,$snapshot->data(),$success);
    }

    public function store(Request $request)
    { 
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');

        $firestore = new FirestoreClient();
        $userId = $request->get('user_id');

        $collectionReference = $firestore->collection('users')->Document($userId);
        if ($collectionReference->snapshot()->data()) {
            return ResponseBuilder::result(
                '409',
                'Duplicate User Data',
                null,
                false
            );
        }

        $date = Carbon::parse()->toDateTime();

        $auth = new Users();
        $file =  $request['img_profil'];

        if($file != null){
            $extension = explode('/', explode(':', substr( $file, 0, strpos( $file, ';')))[1])[1];   // .jpg .png .pdf
            $replace = substr($file, 0, strpos($file, ',')+1);
            // find substring fro replace here eg: data:image/png;base64,
            $subfolder = 'user'.DIRECTORY_SEPARATOR.$userId.DIRECTORY_SEPARATOR;
            $image = str_replace($replace, '', $file);
            $image = str_replace(' ', '+', $image); 
            $imageName = Str::random(5).'.'.$extension;
            $imagePath = $subfolder.$imageName;

            $auth->img_profil  = $imagePath;
            $file = Storage::disk('local')->put($imagePath, base64_decode($image));
        }else{
            $auth->img_profil  = "";
        }

        $auth->fullname = $request['fullname'] ;
        $auth->username = $request['username'];
        $auth->date_birth  = $request['date_birth'];
        $auth->gender  = $request['gender'];
        $auth->email  = $request['email'];
        $auth->created_at  = $date;
        $auth->created_by  = $userId;

        $collectionReference = $firestore->collection('users')->Document($userId);
        $collectionReference->set( (array)$auth );
        
        $status = '200';
        $messages = "Success";
        $success = true;
        return ResponseBuilder::result($status,$messages,$auth,$success);
    
    }

    public function updateData(Request $request)
    { 
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        $userId = $request->get('user_id');        
        $date = Carbon::parse()->toDateTime();
        $file =  $request['img_profil'];

        $img_profil  = "";
        if($file != null){
            $extension = explode('/', explode(':', substr( $file, 0, strpos( $file, ';')))[1])[1];   // .jpg .png .pdf
            $replace = substr($file, 0, strpos($file, ',')+1);
            // find substring fro replace here eg: data:image/png;base64,
            $subfolder = 'user'.DIRECTORY_SEPARATOR.$userId.DIRECTORY_SEPARATOR;
            $image = str_replace($replace, '', $file);
            $image = str_replace(' ', '+', $image); 
            $imageName = Str::random(5).'.'.$extension;
            $imagePath = $subfolder.$imageName;

            $img_profil  = $imagePath;
            $file = Storage::disk('local')->put($imagePath, base64_decode($image));
        }

        $firestore = new FirestoreClient();
        $collectionReference = $firestore->collection('users')->Document($userId);
        if($collectionReference == null){
            $status = '404';
            $messages = "Data Is Empty";
            $success = true;    
            return ResponseBuilder::result($status,$messages,null,$success);
        }

        $collectionReference->update( [
            ['path' => 'fullname', 'value'=> $request['fullname'] ],
            ['path' => 'gender', 'value'=> $request['gender']],
            ['path' => 'date_birth', 'value'=> $request['date_birth']],
            ['path' => 'img_profil', 'value'=> $img_profil],
            ['path' => 'bio', 'value'=> $request['bio']],
            ['path' => 'phone_no', 'value'=> $request['phone_no']],
            ['path' => 'updated_at', 'value'=> $date],
            ['path' => 'updated_by', 'value'=> $userId]
        ]);
        
        $status = '200';
        $messages = "Success";
        $success = true;
        return ResponseBuilder::result($status,$messages,null,$success);
    
    }

    public function destroy(Request $request)
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        $userId = $request->get('user_id');        
        $date = Carbon::parse()->toDateTime();

        $firestore = new FirestoreClient();
        $collectionReference = $firestore->collection('users')->Document($userId);
        if(empty($collectionReference->data())){
            $status = '404';
            $messages = "Data Is Empty";
            $success = true;    
            return ResponseBuilder::result($status,$messages,null,$success);
        }

        $collectionReference->update( [
            ['path' => 'deleted_at', 'value'=> $date],
            ['path' => 'deleted_by', 'value'=> $userId]
        ] );
        
        $status = '200';
        $data = null;
        $messages = "Success";
        $success = true;
        return ResponseBuilder::result($status,$messages,$data,$success);
    }

    public function getUserMoim(Request $request)
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        
        $firestore = new FirestoreClient();
        $userId = $request->get('user_id');

        $collectionReference = $firestore->collection('users')->document($userId)->snapshot();
        if(empty($collectionReference->data())){
            $status = '404';
            $messages = "Data Is Empty";
            $success = true;    
            return ResponseBuilder::result($status,$messages,null,$success);
        }

        $moim = $collectionReference['moim'];
        if($moim == null){
            $status = '404';
            $messages = "Data Is Empty";
            $success = true;    
            return ResponseBuilder::result($status,$messages,null,$success);
        }

        $data = [];
        if(count($moim)>=5){
            $jumlah = 5;
        }else{
            $jumlah = count($moim);
        }
        for($i = 0; $i < $jumlah; $i++){
            $cellocMoim = $firestore->collection('moim')->document($moim[$i]['moim_id'])->snapshot()->data();
            $documents = $firestore->collection('read_post')
                        ->where('user_id', '=', $userId)
                        ->where('moim_id', '=', $moim[$i]['moim_id'])
                        ->documents();
            $totalReadPost = 0;
            foreach ($documents as $document) {
                if ($document->exists()) {
                    $totalReadPost += 1;
                } else {
                    echo('Document does not exist!');
                }
            }
            $postArray =  $cellocMoim['total_posts'] - $totalReadPost ;
            $photoArray =  $cellocMoim['photo'];
            $photo = "";
            if($photoArray['photo_path']!=null){
                $photo = $photoArray['photo_path'];
            }else{
                $photo = $photoArray['avatar_name'];
            }
            $post = [];

            if($postArray>=1){
                $arrayPosts = $firestore->collection('posts')
                ->where('moim_id', '=', $moim[$i]['moim_id'])
                ->orderBy('created_at', 'DESC')->limit(1)
                ->documents();    
                foreach ($arrayPosts as $arrayPost) {
                    if ($arrayPost->exists()) {
                        $dataPost = $arrayPost->data();
                        $dataUser = $firestore->collection('users')->document($dataPost['created_by'])->snapshot();
                        $tanggal =  Carbon::parse($dataPost['created_at'])
                            ->toDateTimeString();
                        $post[] =array(
                            'description'     => $dataPost['description'],
                            'created_at'   =>  $tanggal,
                            'author_id'   =>  $dataPost['created_by'],
                            'author_name' => $dataUser['username']
                        );
                    } else {
                        echo('Document does not exist!');
                    }
                }
            }
            
            $data [] = array(
                'id_moim'     => $moim[$i]['moim_id'],
                'moim_name'   =>  $cellocMoim['name'],
                'total_unread_post'   =>  $postArray,
                'profil_moim' => $photo,
                'last_post' => $post
            );
        }
           
        $status = '200';
        $messages = "Success";
        $success = true;    
        return ResponseBuilder::result($status,$messages,$data,$success);
    }

    public function data()
    {
        $firestore = new FirestoreClient();

        $collectionReference = $firestore->collection('test_col');
        $documentReference = $collectionReference->document('kJfXJCHqf0Oy6H7eJ1VO');
        
        $snapshot = $documentReference->snapshot();
        echo "Hello " . $snapshot['age'];
    }

}
