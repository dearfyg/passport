<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redis;
class Checklogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //写一个全局变量获取地址
        $return_url = request()->return_url;
        $_SERVER["return_url"] = $return_url;
        $_SERVER['uid'] = 0;        //默认未登录
        //获取cookie
        $token = Cookie::get("token");

        //判断是否有cookie
        if($token){
            //获取redis是否有此数据
            $key = "token_".$token;
            $uid = Redis::hgetall($key);
            //判断redis是否有此值
            if($uid){
                $_SERVER['uid'] = $uid['user_id']; //用户id存入全局变量
                $_SERVER['user_name'] = $uid['user_name'];//用户名称存入全局变量
                $_SERVER['token'] = $token;//用户请求的token存入全局变量
            }
        }
        return $next($request);
    }
}
