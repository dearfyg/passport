<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
use App\Model\UserModel;
use Illuminate\Http\Request;
use App\Model\GithubUser;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Redis;
class GitHubLoginController extends Controller
{
    /**
     * github登陆
     */
    public function loginGithub(){
        $url="https://github.com/login/oauth/authorize?client_id=".env('OAUTH_GITHUB_ID')."&redirect_uri=".env("APP_URL")."/oauth/github";
        return redirect($url);
    }
    /**
     * github回调
     */
    public function github(){
        //接受github返回的code
        if(empty($_GET["code"])){
            //登陆失败
            return redirect("/login")->with("msg","登陆失败");
        }
        $code=$_GET["code"];
        //换取access_token
        $token=$this->getToken($code);
        //获取github用户信息
        $UserInfo=$this->githubUserInfo($token);
        //判断该github是否存在
        $res=GithubUser::where("guid",$UserInfo["id"])->first();
        if(!$res){
            //将用户信息填入数据库
            //判断github用户名是否为空
            if(empty($UserInfo["name"])){
                //生成随机用户名
                $UserInfo["name"]=substr(md5(rand(10000,99999).time()),5,15);
            }
            $data=[
                "guid"=>$UserInfo["id"],     //github返回id
                "avatar_url"=>$UserInfo["avatar_url"],
                "github_url"=>$UserInfo["html_url"],
                "github_username"=>$UserInfo["name"],
                "github_email"=>$UserInfo["email"],
                "create_time"=>time()
            ];
            $github=GithubUser::create($data);

            //将用户名和github表id存入主用户表
            $user=UserModel::create(["user_name"=>$UserInfo["name"],"g_id"=>$github["g_id"],"time_create"=>time()])->toArray();
        }else{
            $user=UserModel::where("g_id",$res["g_id"])->first()->toArray();
        }
        //获取回调地址
        $return_url = cookie::get("return_url");
        //随机的token
        $token = rand(1,1000000).Str::random(10);
        //将用户信息冲入cookie 使会话保持
        Cookie::queue("token",$token,120,"/","shop1.com",false,true);
        //存入redis
        Redis::hmset("token_".$token,$user);
        return redirect($return_url);
    }
    /**
     * 根据code 换取 token
     */
    protected function getToken($code){
        $url = 'https://github.com/login/oauth/access_token';

        //post 接口  Guzzle or  curl
        $client = new Client();
        $response = $client->request('POST',$url,[
            'form_params'   => [
                'client_id'         => env('OAUTH_GITHUB_ID'),
                'client_secret'     => env('OAUTH_GITHUB_SEC'),
                'code'              => $code
            ]
        ]);
        //将查询到的字符串解析到变量中
        parse_str($response->getBody(),$str);
        return $str['access_token'];
    }
    /**
     * 获取github个人信息
     */
    public function githubUserInfo($token){
        $url = 'https://api.github.com/user';
        //请求接口
        $client = new Client();
        $response = $client->request('GET',$url,[
            'headers'   => [
                'Authorization' => "token $token"
            ]
        ]);
        return json_decode($response->getBody(),true);
    }
}
