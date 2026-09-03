<?php

namespace App\Http\Controllers\Back;

use App\{
    Models\EmailTemplate,
    Http\Controllers\Controller,
};
use App\Models\Setting;
use Illuminate\Http\Request;

class SMSSettingController extends Controller
{

    /**
     * Constructor Method.
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('adminlocalize');
    }

    /**
     * Show the form for updating resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function sms()
    {
        return view('back.settings.sms');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(EmailTemplate $template)
    {
        return view('back.email_template.edit',compact('template'));
    }

    public function smsUpdate(Request $request)
    {
        $input = $request->all();
        if(isset($request['is_twilio'])){
            $input['is_twilio'] = 1;
        }else{
            $input['is_twilio'] = 0;
        }
        
        $gateway = $request->sms_gateway ?? 'automas';
        if ($gateway == 'automas') {
            if ($input['is_twilio'] == 1) {
                $request->validate([
                    "automas_api_key" => "required",
                    "automas_sender_id" => "required",
                ]);
            }
        } else {
            if ($input['is_twilio'] == 1) {
                $request->validate([
                    "sms_url" => "required",
                ]);
            }
        }

        if(isset($input['twilio_section']) && is_array($input['twilio_section'])){
            $input['twilio_section'] = json_encode($input['twilio_section'], true);
        }

        Setting::first()->update($input);
        return redirect()->back()->withSuccess(__('Data Updated Successfully.'));
    }

    public function testSms(Request $request)
    {
        $request->validate([
            'test_phone' => 'required',
            'test_message' => 'required'
        ]);

        $setting = Setting::first();
        if ($setting->sms_gateway == 'automas') {
            $sms = new \App\Helpers\SmsHelper();
            $response = $sms->sendAutomasDirect(
                $request->test_phone, 
                $request->test_message, 
                $setting->automas_api_key, 
                $setting->automas_sender_id, 
                $setting->automas_type,
                false // synchronous call to get response
            );
            
            if ($response) {
                return redirect()->back()->withSuccess(__('Test SMS Response: ') . $response);
            } else {
                return redirect()->back()->withError(__('Failed to send test SMS. Check logs.'));
            }
        }
        
        return redirect()->back()->withError(__('Test SMS tool is only available for Automas Gateway.'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,EmailTemplate $template)
    {
        $template->update($request->all());
        return redirect()->route('back.setting.email')->withSuccess(__('Email Template Updated Successfully.'));
    }


}
