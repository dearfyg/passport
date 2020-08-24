<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\UserModel;
use Illuminate\Support\Facades\Redis;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
class RegisterController extends Controller
{
    /**
     * 注册
     */
    public function register(){
        return view("login.register");
    }
    /**
     * 执行注册
     */
    public function reg(){
        //表单验证
        request()->validate([
            'user_name' => 'bail|required|unique:user|regex:/^[a-zA-Z0-9_-]{4,16}$/',
            'user_email' => 'bail|required|regex:/^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/',
            'user_pwd' => 'bail|required|regex:/^[0-9A-Za-z]{8,16}$/',
            'password' => 'bail|required|same:user_pwd',
            'user_phone' => 'bail|required|regex:/^1[3-578]\d{9}$/',
        ],[
            "user_name.required"=>"用户名不可为空",
            "user_name.unique"=>"用户名已存在",
            "user_name.regex"=>"用户名必须由4到16位（字母，数字，下划线，减号）",
            "user_email.required"=>"邮箱不可为空",
            "user_email.regex"=>"邮箱格式不正确",
            "user_pwd.required"=>"密码不可为空",
            "user_pwd.regex"=>"密码必须由8-16位数字或这字母组成",
            "password.required"=>"确认密码不可为空",
            "password.same"=>"确认密码必须和密码一致",
            "user_phone.required"=>"手机号不可为空",
            "user_phone.regex"=>"手机号格式不正确",

        ]);
        $data=request()->except("_token","code","password");
        $code=request()->post("code");
        //获取redis中该uuid的验证码
        $uuid=$_COOKIE["uuid"];
        $codeRedis=Redis::get($uuid);
        //不存在失效
        if(empty($codeRedis)){
            return redirect("web/register")->with("msg","验证码错误或已失效");
        }
        //判断验证码是否正确
        if($codeRedis!=$code){
            return redirect("web/register")->with("msg","验证码错误或已失效");
        }
        //添加用户数据
        $data["time_create"]=time();
        //密码加密
        $data["user_pwd"]=password_hash($data["user_pwd"],PASSWORD_DEFAULT);
        $res=UserModel::create($data);
        if($res){
            $token = rand(1,1000000).Str::random(10);
            //将用户信息冲入cookie 使会话保持
            Cookie::queue("token",$token,120,"/","shop1.com",false,true);
            //存入redis
            Redis::hmset("token_".$token,$res->toArray());
            dd($_SERVER["return_url"]);
            return redirect($_SERVER["return_url"]);
        }else{
            return redirect("web/register");
        }

    }
    /**
     * 获取验证码
     */
    public function gain(){
        $phone=request()->phone;
        if(!$phone){
            return $this->returnArr(0,"参数缺失");
        }
        $code=rand(100000,999999);
        //将验证码存入redis五分钟有效
        $uuid=$_COOKIE["uuid"];
        Redis::set($uuid,$code);
        Redis::expire($uuid,300);
        //调用短信发送验证码方法
        $res=$this->message($phone,$code);
        $text=json_encode($res);
        //将短信返回数据写到文件
        file_put_contents(storage_path("/logs/message.log"),$text);
        //判断是否发送成功
        if($res["Message"]!="OK"){
            return $this->returnArr(0,"发送失败");
        }
        return $this->returnArr(1,"发送成功");
    }
    /**
     * 验证验证码
     */
    public function code(){
        $code=request()->code;
        //获取redis中该uuid的验证码
        $uuid=$_COOKIE["uuid"];
        $codeRedis=Redis::get($uuid);
        //判断是否存在
        if($codeRedis){
            //判断是否正确
            if($codeRedis==$code){
                return $this->returnArr(1,"ok");
            }
        }
        return $this->returnArr(0,"验证码错误或已失效");
    }
    /**
     * 验证用户名是否存在
     */
    public function name(){
        $name=request()->name;
        if(!$name){
            return $this->returnArr(0,"参数缺失");
        }
        //查询用户名是否存在
        $res=UserModel::where("user_name",$name)->first();
        if($res){
            return $this->returnArr(0,"用户名已存在");
        }
        return $this->returnArr(1,"ok");
    }
    /**
     * 短信发送验证码
     */
    public function message($phone,$code){
        // Download：https://github.com/aliyun/openapi-sdk-php
        // Usage：https://github.com/aliyun/openapi-sdk-php/blob/master/README.md

        AlibabaCloud::accessKeyClient('LTAI4FtjShQ7uxBcxCRGZmN9', 'jwYGmfLavtAL7RBCyntGcztfcmHzZa')
            ->regionId('cn-hangzhou')
            ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                        'PhoneNumbers' => $phone,
                        'SignName' => "佳璇便利",
                        'TemplateCode' => "SMS_183241729",
                        'TemplateParam' => "{\"code\":\"$code\"}",
                    ],
                ])
                ->request();
            return $result->toArray();
        } catch (ClientException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        } catch (ServerException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        }

        //return view("message.message");
    }
    /**
     * 返回数据结构
     */
    public function returnArr($error,$msg){
        $arr=[
            "error"=>$error,
            "msg"=>$msg
        ];
        return $arr;
    }
}
