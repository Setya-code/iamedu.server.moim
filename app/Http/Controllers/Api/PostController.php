<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helper\ResponseBuilder;
use App\Models\Post;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Auth as FirebaseAuth;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Carbon;

class PostController extends Controller
{
    public function __construct(FirebaseAuth $auth/* ,SignIn $sign */) {
        $this->auth = $auth;
        // $this->sign = $sign;
    }
    public function store(Request $request)
    { 
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        $userId = $request->get('user_id');

        $post = new Post;
        $idpost = Str::random(20);
        $attachments =  $request['attachments'];
        if($attachments != ""){
            $dataattachments = [];
            for($i = 0; $i < count($attachments); $i++){
                $file =  $attachments[$i]["content"];
    
                // $extension = explode('/', explode(':', substr( $file, 0, strpos( $file, ';')))[1])[1];   // .jpg .png .pdf
                $replace = substr($file, 0, strpos($file, ',')+1);
                // find substring fro replace here eg: data:image/png;base64,
                $subfolder = 'moim'.DIRECTORY_SEPARATOR.'post'.DIRECTORY_SEPARATOR.$idpost.DIRECTORY_SEPARATOR;
                $image = str_replace($replace, '', $file);
                $image = str_replace(' ', '+', $image); 
                $imageName = Str::random(5).'.'.$attachments[$i]["extension"];
                $imagePath = $subfolder.$imageName;
      
                $file = Storage::disk('local')->put($imagePath, base64_decode($image));
    
                $dataattachments[] =array(
                    'file_name'     => $imagePath
                );
            }
            
            $post->attachments =  $dataattachments;
        }

        $firestore = new FirestoreClient();

        $date = Carbon::parse()->toDateTime();
        
        $post->description = $request['description'];
        $post->created_at =  $date;
        $post->created_by = $userId; 
        $post->updated_at = null; 
        $post->updated_by =null; 
        $post->deleted_at =null; 
        $post->deleted_by =null; 
        $postMoim = [];
        if($request['moim_id'] != ""){
            $post->moim_id = $request['moim_id'];
            $collectionMoim = $firestore->collection('moim')->Document($request['moim_id']);
            if($collectionMoim == null){
                $status = '404';
                $messages = "Data Is Empty";
                $success = true;    
                return ResponseBuilder::result($status,$messages,null,$success);
            }
            $postMoim[] = array(
                'post_id'    => $idpost,
                'created_at' =>  $date,
                'created_by' => $userId,
                'updated_at' => null,
                'updated_by' => null,
                'deleted_at' => null,
                'deleted_by' => null
            );
            $collectionMoim->update( [
                ['path' => 'posts', 'value'=> $postMoim],
            ]);
            
        }

        $collectionReference = $firestore->collection('posts')->Document($idpost);
        $collectionReference->set((array)$post);

        $status = '200';
        $messages = "Success";
        $success = true;    
        return ResponseBuilder::result($status,$messages,$post,$success);
    }

    public function update($id, Request $request)
    { 
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        $userId = $request->get('user_id');

        $firestore = new FirestoreClient();
        $date = Carbon::parse()->toDateTime();

        $posts = $firestore->collection('posts')->Document($id);
        $postMoim = $posts->snapshot();
        if(empty($postMoim->data())){
            $status = '404';
            $messages = "Data Post Is Empty";
            $success = true;    
            return ResponseBuilder::result($status,$messages,null,$success);
        }
        
        if($postMoim['created_by'] != $userId){
            $status = '404';
            $messages = "Data Not Found";
            $success = true;    
            return ResponseBuilder::result($status,$messages,null,$success);
        }

        $attachments =  $request['attachments'];
        $dataattachments = [];
        if($attachments != ""){
            for($i = 0; $i < count($attachments); $i++){
                $file =  $attachments[$i]["content"];

                // $extension = explode('/', explode(':', substr( $file, 0, strpos( $file, ';')))[1])[1];   // .jpg .png .pdf
                $replace = substr($file, 0, strpos($file, ',')+1);

                // find substring fro replace here eg: data:image/png;base64,
                $idpost = Str::random(20);
                $subfolder = 'moim'.DIRECTORY_SEPARATOR.'post'.DIRECTORY_SEPARATOR.$idpost.DIRECTORY_SEPARATOR;
                $image = str_replace($replace, '', $file);
                $image = str_replace(' ', '+', $image); 
                $imageName = Str::random(5).'.'.$attachments[$i]["extension"];
                $imagePath = $subfolder.$imageName;
    
                $file = Storage::disk('local')->put($imagePath, base64_decode($image));

                $dataattachments[] =array(
                    'file_name'     => $imagePath
                );
            }
        }
        
        $posts->update( [
            ['path' => 'attachments', 'value'=> $dataattachments],
            ['path' => 'description', 'value'=> $request['description']],
            ['path' => 'updated_at', 'value'=> $date],
            ['path' => 'updated_by', 'value'=> $userId],
        ]);
        

        $status = '200';
        $messages = "Success";
        $success = true;    
        return ResponseBuilder::result($status,$messages,null,$success);
    }

    public function destroy($id, Request $request)
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        $userId = $request->get('user_id');

        $date = Carbon::parse()->toDateTime();
        $firestore = new FirestoreClient();

        $collectionReference = $firestore->collection('posts')->Document($id);
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
    
    public function getdata($id, Request $request)
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        
        $firestore = new FirestoreClient();
        $userId = $request->get('user_id');

        $collectionReference = $firestore->collection('posts')->document($id)->snapshot();
        if(empty($collectionReference->data())){
            $status = '404';
            $messages = "Data Is Empty";
            $success = true;    
            return ResponseBuilder::result($status,$messages,null,$success);
        }
        $data = [];
        if($collectionReference['moim_id']==null){
            $user = $firestore->collection('users')
                    ->document($collectionReference['created_by'])
                    ->snapshot();
            $data[] = array(
                'id' => $id,
                'moim_id' => $collectionReference['moim_id'],
                'user_id' => $collectionReference['created_by'],
                'username' => $user['username'],
                'photo' => $user['img_profil'],
                'description' => $collectionReference['description'],
                'attachments' => $collectionReference['attachments']
            );
        }else{
            $user = $firestore->collection('users')
            ->document($collectionReference['created_by'])
            ->snapshot();
            
            $username = "";
            $photo="";
            for($i = 0; $i < count($user['moim']); $i++){
                if($user['moim'][$i]['moim_id']==$collectionReference['moim_id']){
                    if($user['moim'][$i]['photo']['path_photo'] == null){
                        $photo = $user['moim'][$i]['photo']['avatar'] ;
                    }else{
                        $photo = $user['moim'][$i]['photo']['path_photo'] ;
                    }
                    $username = $user['moim'][$i]['username'];
                }
            }
            
            $data[] = array(
                'id' => $id,
                'moim_id' => $collectionReference['moim_id'],
                'user_id' => $collectionReference['created_by'],
                'username' => $username,
                'photo' => $photo,
                'description' => $collectionReference['description'],
                'attachments' => $collectionReference['attachments']
            );
        }

        $status = '200';
        $messages = "Success";
        $success = true;
        return ResponseBuilder::result($status,$messages,$data,$success);
    }

    public function getPostMoim($moim_id,Request $request)
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        
        $firestore = new FirestoreClient();
        $arrayPosts = $firestore->collection('posts')
                            ->where('moim_id','=', $moim_id)
                            ->documents();
        $post = [] ;
        foreach ($arrayPosts as $arrayPost) {
            if ($arrayPost->exists()) {
                $username = "";
                $photo = "";
                $dataPost = $arrayPost->data();
                $datamoim = $firestore->collection('moim')->document($dataPost['moim_id'])->snapshot();
                $dataUser = $firestore->collection('users')->document($dataPost['created_by'])->snapshot();
                if(!empty($dataUser->data())){
                    $user ="";
                    if($datamoim['preferences']['custom_profile'] == true){
                        if(!empty($dataUser['moim'])){
                            for($i = 0 ; $i < count($dataUser['moim']); $i++){
                                if($dataUser['moim'][$i]['moim_id']===$dataPost['moim_id']){ 
                                    $username = $dataUser['moim'][$i]['username'];
                                    if($dataUser['moim'][$i]['photo']['photo_path']==null){
                                        $photo = $dataUser['moim'][$i]['photo']['avatar_name'];
                                    }else{
                                        $photo = $dataUser['moim'][$i]['photo']['photo_path'];
                                    }
                                }
                            }
                        }else{
                            $username =  $dataUser['username'];
                            $photo =  $dataUser['img_profil'];
                        }
                    }else{
                        $username =  $dataUser['username'];
                        $photo =  $dataUser['img_profil'];
                    }
                    $user = array(
                        'username' => $username,
                        'fullname'=> $dataUser['fullname'],
                        'photo' => $photo
                    );
                    $username = "";
                    $photo = "";
                    $dataPost['user'] =  $user ;
                }
                $dataPost['created_at'] =    Carbon::parse($dataPost['created_at']);
                $post[] = $dataPost ;
                
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
