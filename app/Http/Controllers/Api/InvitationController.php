<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helper\ResponseBuilder;
use App\Models\Preferences;
use App\Models\Members;
use App\Models\Post;
use App\Models\Photo;
use App\Models\Moim;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Carbon;

class InvitationController extends Controller
{
    public function __construct(FirebaseAuth $auth/* ,SignIn $sign */) {
        $this->auth = $auth;
        // $this->sign = $sign;
    }
    public function getInvitation(Request $request)
    { 
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        $userId = $request->get('user_id');

        $firestore = new FirestoreClient();

        $collectionReference = $firestore->collection('users')->document($userId)->snapshot();
        $moim = $collectionReference['moim'];
        $data = [];
        
        for($i = 0; $i < count($moim); $i++){
            if($moim[$i]['member_status']=="invited"){
                $moimInvited = $firestore->collection('MOIM')->document($moim[$i]['moim_id'])->snapshot();
                $photo = $moimInvited['photo'];
                if($photo['photo_path']!=null){
                    $photo = $photo['photo_path'];
                }else{
                    $photo = $photo['avatar_name'];
                }
    
                $data [] = array(
                    'id'     => $moim[$i]['moim_id'],
                    'moim_name'   =>  $moimInvited['name'],
                    'photo'   =>  $photo,
                    'total_members'   =>  $moimInvited['total_members'],
                    'total_posts' => $moimInvited['total_posts']
                );
            }
        }
        if($data == null){
            $status = '404';
            $messages = "Data Not Found";
            $success = true;    
            return ResponseBuilder::result($status,$messages,NULL,$success);
        }
        
        $status = '200';
        $messages = "Success";
        $success = true;    
        return ResponseBuilder::result($status,$messages,$data,$success);
    }
    
    public function store(Request $request)
    { 
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        $userId = $request->get('user_id');

        $firestore = new FirestoreClient();

        $collectionReference = $firestore->collection('users')->document($userId)->snapshot();
        if(empty($collectionReference->data())){
            $status = '404';
            $messages = "Data Is Empty";
            $success = true;    
            return ResponseBuilder::result($status,$messages,null,$success);
        }
        
        $status = '200';
        $messages = "Success";
        $success = true;    
        return ResponseBuilder::result($status,$messages,$collectionReference,$success);
    }

}
