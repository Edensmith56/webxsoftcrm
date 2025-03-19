<?php

/** --------------------------------------------------------------------------------
 * This middleware checks the authenticated users permissions for listing fooos
 *
 * @package    Grow CRM
 * @author     NextLoop
 *----------------------------------------------------------------------------------*/

namespace App\Http\Middleware\Fooos;

use Closure;
use Log;

class Index {

    /**
     * check if the current user has permission to view fooos. Permissions will be checked against the users role model values
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {

        //various frontend settings
        $this->fronteEnd();

        //the current user is a client typer user
        if (auth()->user()->is_client) {
            //all is ok - continue
            return $next($request);
        }

        //the current user is team member type user - check permissions based on their role model setting
        if (auth()->user()->is_team) {
            if (auth()->user()->role->role_fooos >= 1) {
                //all is ok - continue
                return $next($request);
            }
        }

        //permission denied
        Log::error("permission denied - the user does not have permission for this action", ['process' => 'middleware.fooos.index', 'ref' => config('app.debug_ref'), 'file' => basename(__FILE__), 'line' => __line__]);
        abort(403);
    }

    /*
     * various frontend related settings
     */
    private function fronteEnd() {

        //default visibility of some elements in blade templates
        config([
            'visibility.actions_buttons_search' => true,
            'visibility.actions_buttons_filter' => true,
        ]);

        /**
         * make some elements visible in the blade templated
         */
        if (auth()->user()->is_team) {
            if (auth()->user()->role->role_fooos >= 2) {
                config([
                    'visibility.action_buttons_delete' => true,
                    'visibility.action_buttons_add' => true,
                    'visibility.action_buttons_edit' => true,
                    'visibility.contacts_col_checkboxes' => true,
                ]);
            }
        }

    }
}
