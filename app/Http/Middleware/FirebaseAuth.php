<?php

namespace App\Http\Middleware;

use Firebase\Auth\Token\Exception\InvalidToken;
use App\Http\Helper\ResponseBuilder;
use Kreait\Firebase\Auth;
use Closure;
use Illuminate\Http\Request;

class FirebaseAuth
{
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
      $idTokenString = $request->header('app-token');
      try {
        $verifiedIdToken =  $this->auth->verifyIdToken($idTokenString);

        if ($verifiedIdToken) {
          $uid = $verifiedIdToken->claims()->get('user_id');
          $request->attributes->add(['user_id' => $uid]);
          return $next($request);
        }
        else {
          $status = '404';
          $data = null;
          $messages = "Not Found";
          $success = false;
          return ResponseBuilder::result($status,$messages,$data,$success);
        }
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
      }


    }
}
