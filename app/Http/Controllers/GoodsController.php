<?php

namespace App\Http\Controllers;

use App\Models\Good;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class GoodsController extends Controller
{
    use SendResponse, Pagination;

    public function getGoods()
    {
        $goods = Good::where("user_id", auth()->user()->id);
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            // return $filter;
            $goods->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {
            $goods->where(function ($q) {
                $columns = Schema::getColumnListing('goods');
                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $_GET['query'] . '%');
                }
            });
        }
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key == 'skip' || $key == 'limit' || $key == 'query' || $key == 'filter') {
                    continue;
                } else {
                    $sort = $value == 'true' ? 'desc' : 'asc';
                    $goods->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($goods,  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب البضاعة بنجاح', [], $res["model"], null, $res["count"]);
    }


    public function addGoods(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric',
            'buy_price' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'company' => 'required|string|max:255',
            'good_number' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ في المدخلات', $validator->errors(), []);
        }
        $good = Good::create([
            'name' => $request['name'],
            'quantity' => $request['quantity'],
            'buy_price' => $request['buy_price'],
            'sale_price' => $request['sale_price'],
            'company' => $request['company'],
            'good_number' => $request['good_number'],
            'user_id' => auth()->user()->id,
        ]);

        return $this->send_response(200, 'تمت عملية الاضافة بنجاح', [], $good);
    }

    public function updateGoods(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:goods,id",
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric',
            'buy_price' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'company' => 'required|string|max:255',
            'good_number' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ في المدخلات', $validator->errors(), []);
        }
        $good = Good::find($request['id']);
        if (auth()->user()->id != $good->user_id)
            return $this->send_response(400, 'لا يمكنك تعديل بيانات هذا المنتج', [], []);
        $good->update([
            'name' => $request['name'],
            'quantity' => $request['quantity'],
            'buy_price' => $request['buy_price'],
            'sale_price' => $request['sale_price'],
            'company' => $request['company'],
            'good_number' => $request['good_number'],
        ]);
        return $this->send_response(200, 'تمت عملية التعديل بنجاح', [], Good::find($request['id']));
    }

    public function deleteGoods(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:goods,id",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ في المدخلات', $validator->errors(), []);
        }
        $good = Good::find($request['id']);
        if (auth()->user()->id != $good->user_id)
            return $this->send_response(400, 'لا يمكنك حذف هذا المنتج', [], []);
        $good->delete();
        return $this->send_response(200, 'تمت عملية الحذف بنجاح', [], []);
    }
}