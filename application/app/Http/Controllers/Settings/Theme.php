<?php

/** --------------------------------------------------------------------------------
 * This controller manages all the business logic for theme settings
 *
 * @package    Grow CRM
 * @author     NextLoop
 *----------------------------------------------------------------------------------*/

namespace App\Http\Controllers\Settings;
use App\Http\Controllers\Controller;
use App\Http\Responses\Settings\Theme\IndexResponse;
use App\Http\Responses\Settings\Theme\UpdateResponse;
use App\Repositories\SettingsRepository;
use App\Rules\NoTags;
use DB;
use Illuminate\Http\Request;
use Validator;

class Theme extends Controller {

    /**
     * The settings repository instance.
     */
    protected $settingsrepo;

    public function __construct(SettingsRepository $settingsrepo) {

        //parent
        parent::__construct();

        //authenticated
        $this->middleware('auth');

        //settings Theme
        $this->middleware('settingsMiddlewareIndex');

        $this->settingsrepo = $settingsrepo;

    }

    /**
     * Display Theme settings
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {

        //crumbs, page data & stats
        $page = $this->pageSettings();

        $settings = \App\Models\Settings::find(1);
        $settings2 = \App\Models\Settings2::find(1);

        //reponse payload
        $payload = [
            'page' => $page,
            'settings' => $settings,
            'settings2' => $settings2,
        ];

        //show the view
        return new IndexResponse($payload);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update() {

        //custom error messages
        $messages = [];

        //validate
        $validator = Validator::make(request()->all(), [
            'settings_theme_name' => [
                'required',
                new NoTags,
            ],
        ], $messages);

        //errors
        if ($validator->fails()) {
            $errors = $validator->errors();
            $messages = '';
            foreach ($errors->all() as $message) {
                $messages .= "<li>$message</li>";
            }

            abort(409, $messages);
        }

        //update
        if (!$this->settingsrepo->updateTheme()) {
            abort(409);
        }

        //update custom css - strip out the <style> tag
        $style_css = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', request('settings2_theme_css'));
        \App\Models\Settings2::where('settings2_id', 1)
            ->update([
                'settings2_theme_css' => $style_css,
            ]);

        //are we updating all users
        if (request('reset_users_theme') == 'on') {
            DB::table('users')->update([
                'pref_theme' => request('settings_theme_name'),
            ]);
        }

        //reponse payload
        $payload = [];

        //generate a response
        return new UpdateResponse($payload);
    }
    /**
     * basic page setting for this section of the app
     * @param string $section page section (optional)
     * @param array $data any other data (optional)
     * @return array
     */
    private function pageSettings($section = '', $data = []) {

        $page = [
            'crumbs' => [
                __('lang.settings'),
                __('lang.theme'),
            ],
            'crumbs_special_class' => 'main-pages-crumbs',
            'page' => 'settings',
            'meta_title' => __('lang.settings'),
            'heading' => __('lang.settings'),
            'settingsmenu_Theme' => 'active',
        ];
        return $page;
    }

}
