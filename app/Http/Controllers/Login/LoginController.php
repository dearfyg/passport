<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\UserModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cookie;
class LoginController extends Controller
{
    /**
    登录页面
     ***/
    public function login(){
        //接收回调地址
        $return_url = request()->return_url;
        cookie::queue("return_url",$return_url);
        //判断用户是否登陆
        $token = cookie::get("token");
        if(empty($token)){
            return view("login.login",["return_url"=>$return_url]);
        }
        return redirect($return_url);
    }
    /**
    登录方法
     ***/
    public function loginDo(){
        //表单验证
        request()->validate([
            'user' => 'bail|required',
            'user_pwd' => 'bail|required',
        ],[
            "user.required"=>"用户名手机号或邮箱不可为空",
            "user_pwd.required"=>"密码不可为空",
        ]);
        //接值
        $user=request()->post("user");
        $pwd=request()->post("user_pwd");
        //判断用户名 or 手机号 or 邮箱 是否存在
        $res=UserModel::where("user_name",$user)->orWhere("user_phone",$user)->orWhere("user_email",$user)->first();
        if($res){
            //判断密码是否正确
            if(password_verify($pwd,$res->user_pwd)){
                //判断账号状态，是否锁定
                if($res["error_num"]>=3 && time()-$res["error_time"]<600){
                    return redirect("web/login")->with("msg","账号已锁定，请在十分钟后重试");
                }
                //登陆成功清空错误次数和时间
                UserModel::where("user_id",$res["user_id"])->update(["error_num"=>0,"error_time"=>null]);
                //登陆成功
                $token = rand(1,1000000).Str::random(10);
                //将用户信息冲入cookie 使会话保持
                Cookie::queue("token",$token,120,"/","shop1.com",false,true);
                //存入redis
                $res = $res->toArray();
                Redis::hmset("token_".$token,$res);
                 //拼接url
                $return_url = request()->return_url;
                return redirect($return_url)->with("msg","登陆成功");
            }
            $user_id=$res["user_id"];       //当前用户id
            $error_num=$res["error_num"];   //错误次数
            $error_time=$res["error_time"];     //最后错误时间
            //判断错误次数
            if($error_num>=3){
                //判断最后登陆错误时间和当前时间是否超过十分钟
                if(time()-$error_time > 600){
                    //超过十分钟修改错误次数为1 错误时间为当前时间戳
                    UserModel::where("user_id",$user_id)->update(["error_num"=>1,"error_time"=>time()]);
                    //登陆失败
                    return redirect("web/login")->with("msg","账号或密码错误");
                }else{
                    //未超过十分钟提示账号已锁定
                    return redirect("web/login")->with("msg","账号已锁定，请在十分钟后重试");
                }
            }else{
                //错误次数加1
                $error_num=$error_num+1;
                //修改错误次数以及错误时间
                UserModel::where("user_id",$user_id)->update(["error_num"=>$error_num,"error_time"=>time()]);
                //判断如果错误次数加1等于3锁定账号
                if($error_num<3){
                    //登陆失败
                    return redirect("web/login")->with("msg","账号或密码错误");
                }
                return redirect("web/login")->with("msg","账号已锁定，请在十分钟后重试");
            }
        }
        //登陆失败
        return redirect("web/login")->with("msg","账号或密码错误");

    }
    /**
    退出登录
     ***/
    public function quit(){
        //接收回调地址
        $return_url = request()->return_url;
        //获取token
        $token = Cookie::get("token");
        //删除cookie
        Cookie::queue("token",null,-1);
        //删除redis
        Redis::del("token_".$token);
        //返回回调地址
        return redirect($return_url);

    }
}
