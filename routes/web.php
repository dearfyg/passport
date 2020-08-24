<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::prefix("/web")->middleware("check_login")->Group(function(){
    Route::get("/login","Login\LoginController@login"); //登录
    Route::get("/quit","Login\LoginController@quit"); //退出登录
    Route::post("/loginDo","Login\LoginController@loginDo");//登录方法
    Route::get("/login/github","Login\GitHubLoginController@loginGithub");
    Route::get("/oauth/github","Login\GitHubLoginController@github");//github回调
    Route::get("/register","Login\RegisterController@register"); //注册
    Route::post("/reg","Login\RegisterController@reg"); //注册方法
    Route::post("/reg/name","Login\RegisterController@name"); //ajax验证用户名
    Route::post("/reg/gain","Login\RegisterController@gain"); //获取验证码
    Route::post("/reg/code","Login\RegisterController@code");   //验证验证码
});
Route::prefix("/api")->Group(function(){
    Route::get("/login","Login\ApiController@auth_login"); //登录
});