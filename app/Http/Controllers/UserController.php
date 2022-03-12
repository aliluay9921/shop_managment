<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use SendResponse, Pagination;

    public function login(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'password' => 'required',
            "user_name" => 'required',
        ], [
            'user_name.required' => ' يرجى ادخال اسم المستخدم ',
            'password.required' => 'يرجى ادخال كلمة المرور ',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'فشل عملية تسجيل الدخول', $validator->errors(), []);
        }
        if (auth()->attempt(array('user_name' => $request['user_name'], 'password' => $request['password']))) {
            $user = auth()->user();
            $token = $user->createToken('shop_managment')->accessToken;
            return $this->send_response(200, 'تم تسجيل الدخول بنجاح', [], $user, $token);
        } else {
            return $this->send_response(400, 'هناك مشكلة تحقق من تطابق المدخلات', null, null, null);
        }
    }

    public function getUsers()
    {
        $users = User::select("*");
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            $users->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {
            $columns = Schema::getColumnListing('users');
            foreach ($columns as $column) {
                $users->orWhere($column, 'LIKE', '%' . $_GET['query'] . '%');
            }
        }
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key == 'skip' || $key == 'limit' || $key == 'query' || $key == 'filter') {
                    continue;
                } else {
                    $sort = $value == 'true' ? 'desc' : 'asc';
                    $users->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($users,  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب المستخدمين بنجاح', [], $res["model"], null, $res["count"]);
    }

    public function addUser(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "user_name" => 'required|unique:users,user_name',
            "password" => 'required',
            "phone_number" => 'required',
            "address" => 'required',
            "shop_name" => 'required|unique:users,shop_name',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ بالمدخلات', $validator->errors(), []);
        }
        $user = User::create([
            'user_name' => $request['user_name'],
            'password' => bcrypt($request['password']),
            'phone_number' => $request['phone_number'],
            'address' => $request['address'],
            'shop_name' => $request['shop_name'],
        ]);
        return $this->send_response(200, 'تم إضافة المستخدم بنجاح', [], $user);
    }

    public function toggleUserActive(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => 'required|exists:users,id',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ بالمدخلات', $validator->errors(), []);
        }
        $user = User::find($request['id']);
        $user->update([
            'active' => !$user->active,
        ]);
        return $this->send_response(200, 'تم تغيير حالة المستخدم بنجاح', [], $user);
    }
}
