<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnsubscribeController extends Controller {
  /**
   * Where to redirect users after resetting their password.
   *
   * @var string
   */
  protected $redirectTo = '/';

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct() {
    $this->redirectTo = route('home');
  }

  public function showUnsubscribedForm(Request $request, $hash = null, $email = null) {
    $key = 'd6c806f694d9e1647350de0565ef684d3d5804f2';
    if (sha1(urlencode($email) . $key) != $hash) {
      return redirect()->route("login");
    }

    $unsubscribed_user = DB::select("select unsub.id from mailer.unsubscribed unsub inner join oauth.users usr on unsub.id = usr.id where usr.email = '" . $email . "'");

    return view('auth.mailing.unsubscribe')->with(
      ['hash' => $hash, 'email' => $email, 'successMessage' => $unsubscribed_user ? "Вы успешно отписались от рассылки" : ""]
    );
  }

  public function unsubscribed(Request $request) {
    $email = $request->only(['mail']) ?? null;

    $userId = DB::table('oauth.users')->where('email', $email)->select('id')->get()->first();

    try {
      $unsubscribeId = DB::table('mailer.unsubscribed')->insertGetId([
        'id' => $userId->id,
        'active' => $request->only(['unsubscribe'])['unsubscribe'] ?? null,
      ]);
      return ['status_id' => (bool) $unsubscribeId, 'successMessage' => 'Вы успешно отписались от рассылки'];
    } catch (\Exception $e) {
      return ['status_id' => 0, 'unsubscribed' => true];
    }
  }
}
