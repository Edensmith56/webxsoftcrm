<?php

/** --------------------------------------------------------------------------------
 * This middleware checks the authenticated users permissions to edit the fooo
 *
 * @package    Grow CRM
 * @author     NextLoop
 *----------------------------------------------------------------------------------*/

namespace App\Http\Middleware\Fooos;
use Closure;
use Log;

class Edit {

    /**
     * check if the current user has permission to edit a fooo. Permissions will be checked against the users role model values
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {

        //fooo id
        $fooo_id = $request->route('fooo');

        //frontend
        $this->fronteEnd();

        //does the fooo exist
        if (\App\Models\Foo::Where('fooo_id', $fooo_id)->doesntExist()) {
            Log::error("a fooo with if ($fooo_id) could not be doun", ['process' => 'middleware.fooos.edit', 'ref' => config('app.debug_ref'), 'file' => basename(__FILE__), 'line' => __line__]);
            abort(404);
        }

        //the current user is a client type user
        if (auth()->user()->is_client) {
            //permission denied
            abort(403);
        }

        //the current user is team member type user - check permissions based on their role model setting
        if (auth()->user()->role->role_fooos >= 2) {
            //all is ok - continue
            return $next($request);
        }

        //permission denied
        Log::error("permission denied - the user does not have permission for this action", ['process' => 'middleware.fooos.edit', 'ref' => config('app.debug_ref'), 'file' => basename(__FILE__), 'line' => __line__]);
        abort(403);
    }

    /*
     * various frontend related settings
     */
    private function fronteEnd() {

        //placeholder for any frontend related settings

    }
}
