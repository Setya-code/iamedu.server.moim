<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helper\ResponseBuilder;
use App\Models\Preferences;
use App\Models\Members;
use App\Models\Photo;
use App\Models\Moim;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Auth as FirebaseAuth;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Carbon;

class MoimController extends Controller
{
    public function __construct(FirebaseAuth $auth/* ,SignIn $sign */) {
        $this->auth = $auth;
        // $this->sign = $sign;
    }

    public function store(Request $request)
    { 
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        $userId = $request->get('user_id');
        $date = Carbon::parse()->toDateTime();

        $members = new Members;
        $members->user_id =  $userId ;
        $members->user_role =  "admin";
        $members->member_status = 'Joined' ;
        $members->joined_at =  $date;
        $members->deleted_at =  null;

        $reqPhoto =  $request['photo'];
        $profil = new Photo;
        if($reqPhoto['photo_path']!=null){
            $file = $reqPhoto['photo_path'];
            // $extension = explode('/', explode(':', substr( $file, 0, strpos( $file, ';')))[1])[1];   // .jpg .png .pdf
            $replace = substr($file, 0, strpos($file, ',')+1);
            // find substring fro replace here eg: data:image/png;base64,
            $subfolder = 'user'.DIRECTORY_SEPARATOR.$userId.DIRECTORY_SEPARATOR;
            $image = str_replace($replace, '', $file);
            $image = str_replace(' ', '+', $image); 
            $imageName = Str::random(5).'.'.$reqPhoto['extension'];
            $imagePath = $subfolder.$imageName;

            $file = Storage::disk('local')->put($imagePath, base64_decode($image));
            $profil->photo_path  = $imagePath;
        }else{
            $profil->avatar_name = $reqPhoto['avatar_name'];
        }

        $reqPref = $request['preferences'];
        $preferences = new Preferences;
        $preferences->private_moim = $reqPref['private_moim'];
        $preferences->custom_profile = $reqPref['custom_profile'];

        $moim = new Moim;
        $moim->name = $request['name'];
        $moim->about = $request['about'];
        $moim->categories = $request['categories'];
        $moim->photo = (array) $profil; 
        $moim->preferences = (array) $reqPref;
        $moim->total_posts = 0;
        $moim->total_posts_weekly = 0;
        $moim->total_members = 1;
        $moim->members = [(array) $members]; 
        $moim->created_at =  $date;
        $moim->created_by = $userId; 
        $moim->updated_at = null; 
        $moim->updated_by =null; 
        $moim->deleted_at =null; 
        $moim->deleted_by =null; 

        $firestore = new FirestoreClient();

        $collectionReference = $firestore->collection('moim');
        $moim = $collectionReference->add( (array) $moim );
        
        $status = '200';
        $data = array('moim_id' => $moim->id());
        $messages = "Sukses";
        $success = true;
        return ResponseBuilder::result($status,$messages,$data,$success);
    }

    public function getMembersMoim($moim_id)
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        $firestore = new FirestoreClient();

        $collectionReference = $firestore->collection('moim')->document($moim_id)->snapshot();
        if(empty($collectionReference->data())){
            $status = '404';
            $messages = "Data Is Empty";
            $success = true;    
            return ResponseBuilder::result($status,$messages,null,$success);
        }

        $data =[];
        $members = $collectionReference['members'];
        for($i = 0; $i < count($members); $i++){
            $user = $firestore->collection('users')->document($members[$i]['user_id'])->snapshot();
            $tanggal =  Carbon::parse($members[$i]['joined_at']);
            $data[] = array(
                'user_id' => $members[$i]['user_id'],
                'username'     => $user['username'],
                'fullname'   =>  $user['fullname'],
                'joined_at'     => $tanggal,
                'members' => $members[$i]['member_status'],
                'user_role' => $members[$i]['user_role'],
            );
        }

        $status = '200';
        $data =  $data;
        $messages = "Sukses";
        $success = true;
        return ResponseBuilder::result($status,$messages,$data,$success);
    }

    public function getDetailMoim($moim_id)
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        $firestore = new FirestoreClient();

        $collectionReference = $firestore->collection('moim')->document($moim_id)->snapshot();
        if(empty($collectionReference->data())){
            $status = '404';
            $messages = "Data Is Empty";
            $success = true;    
            return ResponseBuilder::result($status,$messages,null,$success);
        }
        if($collectionReference['photo']['photo_path']!=null){
            $photo = $collectionReference['photo']['photo_path'];
        }else{
            $photo = $collectionReference['photo']['avatar_name'];
        }
        $data[] = array(
            'name' => $collectionReference['name'],
            'cover' => $collectionReference['cover'],
            'total_members' => $collectionReference['total_members'],
            'photo' =>  $photo,
            'about' => $collectionReference['about'],
            'preferences' => $collectionReference['preferences'],
        );

        $status = '200';
        $messages = "Sukses";
        $success = true;
        return ResponseBuilder::result($status,$messages,$data,$success);
    }


    public function updateMoim($id, Request $request)
    { 
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        $userId = $request->get('user_id');

        $firestore = new FirestoreClient();
        $date = Carbon::parse()->toDateTime();

        $moim = $firestore->collection('moim')->Document($id);
        $postMoim = $moim->snapshot();
        if(empty($postMoim->data())){
            $status = '404';
            $messages = "Data Moim Is Empty";
            $success = true;    
            return ResponseBuilder::result($status,$messages,null,$success);
        }

        $reqPhoto =  $request['photo'];
        if($reqPhoto['photo_path']!=null){
            $file = $reqPhoto['photo_path'];
            $extension = explode('/', explode(':', substr( $file, 0, strpos( $file, ';')))[1])[1];   // .jpg .png .pdf
            $replace = substr($file, 0, strpos($file, ',')+1);
            // find substring fro replace here eg: data:image/png;base64,
            $subfolder = 'user'.DIRECTORY_SEPARATOR.$userId.DIRECTORY_SEPARATOR;
            $image = str_replace($replace, '', $file);
            $image = str_replace(' ', '+', $image); 
            $imageName = Str::random(5).'.'.$extension;
            $imagePath = $subfolder.$imageName;

            $file = Storage::disk('local')->put($imagePath, base64_decode($image));
            // $profil->photo_path  = $imagePath;
            $profil = array(
                'photo_path' => $imagePath,
                'avatar_name' => null
            );
        }else{
            // $profil->avatar_name = $reqPhoto['avatar_name'];
            $profil = array(
                'photo_path' =>null,
                'avatar_name' => $reqPhoto['avatar_name']
            );
        }
        
        $moim->update( [
            ['path' => 'photo', 'value'=> $profil],
            ['path' => 'about', 'value'=> $request['about']],
            ['path' => 'name', 'value'=> $request['name']],
            ['path' => 'categories', 'value'=> $request['categories']],
            ['path' => 'updated_at', 'value'=> $date],
            ['path' => 'updated_by', 'value'=> $userId],
        ]);
        

        $status = '200';
        $messages = "Success";
        $success = true;    
        return ResponseBuilder::result($status,$messages,null,$success);
    }

    public function update($id, Request $request)
    { 
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');
        $userId = $request->get('user_id');

        $firestore = new FirestoreClient();
        $date = Carbon::parse()->toDateTime();

        $moim = $firestore->collection('moim')->Document($id);
        $postMoim = $moim->snapshot();
        if(empty($postMoim->data())){
            $status = '404';
            $messages = "Data Moim Is Empty";
            $success = true;    
            return ResponseBuilder::result($status,$messages,null,$success);
        }
        
        $moim->update( [
            ['path' => 'preferences', 'value'=> $request['preferences']],
            ['path' => 'updated_at', 'value'=> $date],
            ['path' => 'updated_by', 'value'=> $userId],
        ]);

        $status = '200';
        $messages = "Success";
        $success = true;    
        return ResponseBuilder::result($status,$messages,null,$success);
    }

}
