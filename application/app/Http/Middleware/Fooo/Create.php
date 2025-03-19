<?php

/** --------------------------------------------------------------------------------
 * This middleware checks the authenticated users permissions to create a new fooo
 *
 * @package    Grow CRM
 * @author     NextLoop
 *----------------------------------------------------------------------------------*/

namespace App\Http\Middleware\Fooos;
use Closure;
use Log;

class Create {

    /**
     * check if the current user has permission to create a new fooo. Permissions will be checked against the users role model values
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {

        //various frontend settings
        $this->fronteEnd();

        //the current user is a client type user
        if (auth()->user()->is_client) {
            abort(403);
        }

        //the current user is team member type user - check permissions based on their role model setting
        if (auth()->user()->role->role_fooos >= 2) {
            //all is ok - continue
            return $next($request);
        }

        //permission denied
        Log::error("permission denied - the user does not have permission for this action", ['process' => 'middleware.fooos.create', 'ref' => config('app.debug_ref'), 'file' => basename(__FILE__), 'line' => __line__]);
        abort(403);
    }

    /*
     * various frontend related settings
     */
    private function fronteEnd() {

        //placeholder for any frontend related settings

    }

}
