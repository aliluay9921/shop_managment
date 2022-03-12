<?php

namespace App\Http\Controllers;

use App\Models\Good;
use App\Models\GoodInvoice;
use App\Models\Invoice;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    use SendResponse, Pagination;
    public function random_code()
    {
        $code = substr(str_shuffle("0123456789"), 0, 6);
        $get = Invoice::where('code_invoices', $code)->first();
        if ($get) {
            return $this->random_code();
        } else {
            return $code;
        }
    }
    public function getInvoices()
    {
        if (isset($_GET["invoice_id"])) {
            $goods = GoodInvoice::where('invoice_id', $_GET["invoice_id"]);
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
                    if ($key == 'skip' || $key == 'limit' || $key == 'query' || $key == 'invoice_id') {
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
            if ($goods) {
                return $this->send_response(200, 'تم جلب الفواتير بنجاح', [], $res["model"], null, $res["count"]);
            } else {
                return $this->send_response(404, 'لا يوجد فاتورة بهذا الرقم', [], []);
            }
        }
        $invoices = Invoice::where('user_id', auth()->user()->id);
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            // return $filter;
            $invoices->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {
            $invoices->where(function ($q) {
                $columns = Schema::getColumnListing('invoices');
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
                    $invoices->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($invoices,  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب الفواتير بنجاح', [], $res["model"], null, $res["count"]);
    }
    public function addInvoice(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'goods_id.*.id' => 'required|exists:goods,id',
            'goods_id.*.quantity' => 'required|Numeric',
            'client_name' => 'required',
            'client_phone' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ في البيانات', $validator->errors()->all());
        }

        $total_price = 0;
        $data = [];
        $data = [
            'client_name' => $request['client_name'],
            'client_phone' => $request['client_phone'],
            'code_invoices' => $this->random_code(),
            'user_id' => auth()->user()->id,
        ];
        if (array_key_exists('note', $request)) {
            $data['note'] = $request['note'];
        }
        $goods_id = [];
        foreach ($request['goods_id'] as $good_id) {
            $good = Good::find($good_id['id']);
            array_push($goods_id, $good_id['id']);
            if ($good->quantity < $good_id['quantity']) {
                return $this->send_response(400, 'الكمية المطلوبة أكبر من المتاحة', [], []);
            }
            $good->update([
                'quantity' => $good->quantity - $good_id['quantity']
            ]);
            $total_price += $good->buy_price * $good_id['quantity'];
        }

        $data['total_price'] = $total_price;
        $invoice = Invoice::create($data);
        foreach ($goods_id as $key => $good_id) {
            GoodInvoice::create([
                'good_id' => $good_id,
                'invoice_id' => $invoice->id,
                'quantity' => $request['goods_id'][$key]['quantity']
            ]);
        }
        return $this->send_response(200, 'تم اضافة الفاتورة بنجاح', [], Invoice::find($invoice->id));
    }
    public function retriveGoods(Request $request)
    {

        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'invoice_id' => 'required|exists:invoices,id',
            'good_id' => 'required|exists:goods,id',
            'good_quantity' => 'required|Numeric',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ في البيانات', $validator->errors()->all());
        }
        $invoice = Invoice::find($request['invoice_id']);

        if ($invoice->user_id != auth()->user()->id) {
            return $this->send_response(400, 'لا يمكنك تعديل هذه الفاتورة', [], []);
        }
        if ($request["good_quantity"] == -1) {
            // حذف الفاتورة كلها  
            // ماكو داعي ندز شي ثاني غير ال -1
            $get_good_invoice = GoodInvoice::where("invoice_id", $request['invoice_id'])->get();

            foreach ($get_good_invoice as $good_invoice) {
                // جيب البضاعة حسب الموجود وزيد عددهن
                $good_retrive = Good::find($good_invoice->good_id);
                $good_retrive->update([
                    'quantity' => $good_retrive->quantity + $good_invoice->quantity
                ]);
                $good_invoice->delete();
            }
            $delete_invoice = Invoice::find($request["invoice_id"]);
            $delete_invoice->delete();
            return $this->send_response(200, 'تم حذف المنتج بنجاح', [], []);
        }
        $good = Good::find($request['good_id']);
        //    حذف عنصر معين من فاتورة
        $good_invoice = GoodInvoice::where("invoice_id", $request['invoice_id'])->where("good_id", $request['good_id'])->first();
        if ($good_invoice) {
            // if quntity grater than the old quntity subtract that and added to the good
            if ($good_invoice->quantity > $request['good_quantity']) {
                $good_invoice->update([
                    'quantity' => $good_invoice->quantity - $request['good_quantity']
                ]);
            } else if ($good_invoice->quantity == $request['good_quantity']) {
                $good_invoice->delete();
            } else {
                return $this->send_response(400, 'كمية البضاعة المراد استرجاعها اكبر من المتوفرة في الفاتورة', [], []);
            }
            $good->update([
                "quantity" => $good->quantity + $request['good_quantity']
            ]);
            $invoice->update([
                'total_price' => $invoice->total_price - ($good->buy_price * $request["good_quantity"])
            ]);
        } else {
            return $this->send_response(400, 'يرجى ادخال بضاعة متوفرة في الفاتورة', [], []);
        }

        return $this->send_response(200, 'تم تعديل الفاتورة بنجاح', [], Invoice::find($invoice->id));
    }
}