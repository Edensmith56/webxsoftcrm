<?php

/** --------------------------------------------------------------------------------
 * This middleware checks the authenticated users permissions to delete a fooo of a
 * a list of fooos
 *
 * @package    Grow CRM
 * @author     NextLoop
 *----------------------------------------------------------------------------------*/

namespace App\Http\Middleware\Fooos;
use Closure;
use Log;

class Destroy {

    /**
     * a single fooo as supplied in the url or a list as supplied in the form post
     * this allows using the same middleware for a single fooo delete or bulk deletes
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {

        //for a single fooo, merge it into an $ids[x] array and set as if checkox is selected (on).
        if (is_numeric($request->route('fooo'))) {
            $ids[$request->route('fooo')] = 'on';
            request()->merge([
                'ids' => $ids,
            ]);
        }

        //loop through each fooo and check the authenticated users permission
        if (is_array(request('ids'))) {

            //validate each fooo to see if it exists in the database
            foreach (request('ids') as $id => $value) {
                //only checked items
                if ($value == 'on') {
                    if (\App\Models\Foo::Where('fooo_id', $fooo_id)->doesntExist()) {
                        abort(409, __('lang.one_of_the_selected_items_nolonger_exists'));
                    }
                }
            }

            //the current user is a client type user
            if (auth()->user()->is_client) {
                //permission denied
                abort(403);
            }

            //the current user is team member type user - check permissions based on their role model setting
            if (auth()->user()->role->role_fooos >= 3) {
                //all is ok - continue
                return $next($request);
            }

        } else {
            //no items were passed with this request
            Log::error("no items were sent in the list for deleting", ['process' => 'middleware.fooos.destroy', 'ref' => config('app.debug_ref'), 'file' => basename(__FILE__), 'line' => __line__]);
            abort(409);
        }

        //all is on - passed
        return $next($request);
    }
}
