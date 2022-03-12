<?php

namespace App\Http\Controllers;

use App\Models\debtRecords;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeptRecordController extends Controller
{
    use SendResponse, Pagination;
    public function random_code()
    {
        $code = substr(str_shuffle("0123456789"), 0, 6);
        $get = debtRecords::where('code', $code)->first();
        if ($get) {
            return $this->random_code();
        } else {
            return $code;
        }
    }
    public function addDeptRecord(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'client_name' => 'required',
            'client_phone' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'حدث خطأ ما', $validator->errors(), []);
        }
        $data = [
            'client_name'  => $request['client_name'],
            'client_phone' => $request['client_phone'],
            'code'         => $this->random_code(),
            'user_id'      => auth()->user()->id,
        ];
        $dept_record = debtRecords::create($data);
        return $this->send_response(200, 'تم إضافة السجل بنجاح', debtRecords::find($dept_record), []);
    }
    public function addGoodsToDept(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'dept_record_id' => 'required|exists:debt_records,id',
            'goods.*.id' => 'exists:goods,id',
            'goods.*.quantity' => 'exists:goods,id',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'حدث خطأ ما', $validator->errors(), []);
        }
        $dept_record = debtRecords::find($request['dept_record_id']);
        
    }
}