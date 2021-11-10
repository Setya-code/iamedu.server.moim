<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helper\ResponseBuilder;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;
use Google\Cloud\Firestore\FirestoreClient;

class LoginController extends Controller
{
    public function __construct(FirebaseAuth $auth) {
        $this->auth = $auth;
    }

    public function login(Request $request)
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('RELATIVE_PATH').'resources/credentials/firebase_credentials.json');

        $idp_token = $request['idp-token'];
        $provider = $request['id-provider'];

        try {
            $firestore = new FirestoreClient();

            $signin = $this->auth->signInWithIdpIdToken($provider, $idp_token);
            $signin_resp = $signin->asTokenResponse();

            $verifiedIdToken =  $this->auth->verifyIdToken($signin_resp['id_token']);
            $userId = $verifiedIdToken->claims()->get('user_id');
    
            $signin_resp['user'] = $firestore->collection('users')
                                    ->document($userId)
                                    ->snapshot()
                                    ->data();

            $status = '200';
            $messages = "Success";
            $success = true;
            return ResponseBuilder::result($status, $messages, $signin_resp, $success);
        } catch (InvalidToken $e) {
            $status = 401;
            $data = null;
            $messages = 'The token is invalid: '.$e->getMessage();
            $success = false;
            return ResponseBuilder::result($status,$messages,$data,$success);
        } catch (\InvalidArgumentException $e) {
            $status = 401;
            $data = null;
            $messages = 'The token could not be parsed: '.$e->getMessage();
            $success = false;
            return ResponseBuilder::result($status,$messages,$data,$success);
        } catch (\FailedToSignIn $e) {
            $status = 401;
            $data = null;
            $messages = 'Failed To SignIn: '.$e->getMessage();
            $success = false;
            return ResponseBuilder::result($status,$messages,$data,$success);
        } catch (Exception $e) {
            $status = 500;
            $data = null;
            $messages = 'System error: '.$e->getMessage();
            $success = false;
            return ResponseBuilder::result($status,$messages,$data,$success);
        }
    }
}
