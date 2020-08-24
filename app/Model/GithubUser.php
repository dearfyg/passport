<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class GithubUser extends Model
{
    protected $table = "github_user";
    protected $primaryKey = "g_id";
    public $timestamps = false;
}
