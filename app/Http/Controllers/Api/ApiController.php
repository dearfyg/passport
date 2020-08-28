<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\UserModel;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
class ApiController extends Controller
{
    //登录接口
    public function login(){
        $user = request()->user;
        $user_pwd = request()->user_pwd;
        //进行判断
        if(empty($user)|| empty($user_pwd)) {
            return $this->Json('00001','参数错误');
        }
        //查询数据库
        $userInfo = UserModel::where("user_name",$user)->first();
        //判断
        if($userInfo){
            if(password_verify($user_pwd,$userInfo->user_pwd)){
                //登陆成功
                $token = rand(1,1000000).Str::random(10);
                Redis::hmset("token_".$token,$userInfo->toArray());
                return $this->Json("00000","ok",$token);
            }else{
                return $this->Json("00003","账号或密码错误");
            }
        }else{
            return $this->Json("00002","账号或密码错误");
        }
    }
    //注册接口
    public function reg(){
        //接值
        $user_name =request()->user_name;
        $user_pwd = request()->user_pwd;
        $user_phone = request()->user_phone;
        $data = [
            'user_name'=>$user_name,
            'user_pwd'=>password_hash($user_pwd,PASSWORD_DEFAULT),
            'user_phone'=>$user_phone,
            'time_create'=>time(),
        ];
       //存入数据
        $res = UserModel::create($data);
        if($res){
            //登陆成功
            $token = rand(1,1000000).Str::random(10);
            Redis::hmset("token_".$token,$res->toArray());
            return $this->Json('00000','注册成功',$token);
        }else{
            return $this->Json("00001","注册失败");
        }

    }
}
