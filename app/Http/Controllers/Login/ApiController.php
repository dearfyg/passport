<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
class ApiController extends Controller
{
    /**
    判断用户是否登录接口
     ***/
    public function auth_login(){
        //首先接取token
        $token = request()->token;
        //去redis查询是否有
        $token = Redis::hgetall("token_".$token);
        if($token){
            //如果有则返回成功(1)
            return 1;
        }else{
            //没有返回失败(0)
            return 0;
        }
    }
}
