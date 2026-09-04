<?php
namespace App\Helpers;
use App\Models\Setting;
use Twilio\Rest\Client;

class SmsHelper {

    public function SendSms($to_number, $type, $order_number = null)
    {
        $setting = Setting::first();
        if ($setting->is_twilio == 0) {
            return;
        }
        
        $gateway = $setting->sms_gateway ?? 'automas';
        if ($gateway == 'custom' && empty($setting->sms_url)) {
            return;
        }
        if ($gateway == 'automas' && (empty($setting->automas_api_key) || empty($setting->automas_sender_id))) {
            return;
        }

        $sms_section = json_decode($setting->twilio_section, true) ?? [];
        
        // Handle both with and without quotes for safety
        $template = $sms_section[$type] ?? ($sms_section[trim($type, "'\"")] ?? ($sms_section["'" . trim($type, "'\"") . "'"] ?? ''));
        if(empty($template)) return;

        $body = str_replace("{order_number}", $order_number , $template);
        
        $order = \App\Models\Order::where('transaction_number', $order_number)->first();
        if($order) {
            $total = \App\Helpers\PriceHelper::OrderTotal($order);
            $order_amount = ($setting->currency_direction == 1) ? $order->currency_sign . $total : $total . $order->currency_sign;
            
            $order_date = $order->created_at->format('d M Y');
            
            $billing = json_decode($order->billing_info, true);
            $customer_name = ($billing['bill_first_name'] ?? '') . ' ' . ($billing['bill_last_name'] ?? '');
            $customer_phone = $billing['bill_phone'] ?? '';
            
            $addressFields = [];
            if(!empty($billing['bill_address1'])) $addressFields[] = $billing['bill_address1'];
            if(!empty($billing['bill_address2'])) $addressFields[] = $billing['bill_address2'];
            if(!empty($billing['bill_city'])) $addressFields[] = $billing['bill_city'];
            $customer_address = implode(', ', $addressFields);
            
            $cart = json_decode($order->cart, true);
            $items = [];
            if($cart) {
                foreach ($cart as $item) {
                    $items[] = ($item['name'] ?? 'Item') . ' x ' . ($item['qty'] ?? 1);
                }
            }
            $order_items = implode(', ', $items);

            $payment_method = $order->payment_method ?? 'Unknown';

            $body = str_replace("{order_amount}", $order_amount, $body);
            $body = str_replace("{order_date}", $order_date, $body);
            $body = str_replace("{customer_name}", $customer_name, $body);
            $body = str_replace("{customer_phone}", $customer_phone, $body);
            $body = str_replace("{customer_address}", $customer_address, $body);
            $body = str_replace("{order_items}", $order_items, $body);
            $body = str_replace("{payment_method}", $payment_method, $body);
        }

        $this->dispatchSms($setting, $to_number, $body);

        // Send to merchant automatically for new purchases
        if (trim($type, "'\"") == 'purchase' && !empty($setting->footer_phone)) {
            $merchant_template = $sms_section["'merchant_purchase'"] ?? ($sms_section["merchant_purchase"] ?? '');
            if (!empty($merchant_template)) {
                $merchant_body = str_replace("{order_number}", $order_number, $merchant_template);
                if ($order) {
                    $merchant_body = str_replace("{order_amount}", $order_amount, $merchant_body);
                    $merchant_body = str_replace("{order_date}", $order_date, $merchant_body);
                    $merchant_body = str_replace("{customer_name}", $customer_name, $merchant_body);
                    $merchant_body = str_replace("{customer_phone}", $customer_phone, $merchant_body);
                    $merchant_body = str_replace("{customer_address}", $customer_address, $merchant_body);
                    $merchant_body = str_replace("{order_items}", $order_items, $merchant_body);
                    $merchant_body = str_replace("{payment_method}", $payment_method, $merchant_body);
                }
                $this->dispatchSms($setting, $setting->footer_phone, $merchant_body);
            }
        }
    }

    public function SendCustomSms($to_number, $body)
    {
        $setting = Setting::first();
        if ($setting->is_twilio == 0 || empty($to_number)) {
            return;
        }

        $this->dispatchSms($setting, $to_number, $body);
    }

    private function dispatchSms($setting, $to_number, $body)
    {
        $gateway = $setting->sms_gateway ?? 'automas';

        if ($gateway == 'automas') {
            $this->sendAutomasDirect($to_number, $body, $setting->automas_api_key, $setting->automas_sender_id, $setting->automas_type);
        } else {
            // Custom Universal Gateway
            if(empty($setting->sms_url)) return;
            try {
                $url = $setting->sms_url;
                $url = str_replace('{number}', $to_number, $url);
                $url = str_replace('{message}', urlencode($body), $url);

                if (function_exists('exec') && !in_array('exec', array_map('trim', explode(', ', ini_get('disable_functions'))))) {
                    exec("curl -s -o /dev/null -w '' " . escapeshellarg($url) . " > /dev/null 2>&1 &");
                } else {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_exec($ch);
                    curl_close($ch);
                }
            } catch (\Throwable $th) {
                \Log::error('Universal SMS Error: ' . $th->getMessage());
            }
        }
    }

    public function sendAutomasDirect($to_number, $body, $apiKey, $senderId, $type = 'auto', $async = true)
    {
        if (empty($apiKey) || empty($senderId)) {
            return false;
        }

        // Clean phone number (remove +, spaces, dashes, parentheses)
        $clean_number = preg_replace('/[^0-9]/', '', $to_number);
        
        // Normalize Bangladesh number formats
        if (preg_match('/^01[3-9]\d{8}$/', $clean_number)) {
            $clean_number = '88' . $clean_number; // e.g., 017... to 88017...
        }

        // Auto-detect Unicode (if string length != mb_strlen)
        $is_unicode = false;
        if ($type == 'unicode') {
            $is_unicode = true;
        } elseif ($type == 'auto') {
            if (strlen($body) != mb_strlen($body, 'UTF-8')) {
                $is_unicode = true;
            }
        }

        $payload = [
            'apikey' => $apiKey,
            'sender' => $senderId,
            'msisdn' => $clean_number,
            'smstext' => $body,
        ];

        if ($is_unicode) {
            $payload['type'] = 8;
            $payload['smsformat'] = 8;
        }

        $url = "https://api.automas.com.bd/smsapiv3";

        try {
            if ($async && function_exists('exec') && !in_array('exec', array_map('trim', explode(', ', ini_get('disable_functions'))))) {
                $postData = http_build_query($payload);
                $cmd = "curl -s -X POST " . escapeshellarg($url) . " -d " . escapeshellarg($postData) . " > /dev/null 2>&1 &";
                exec($cmd);
                return true;
            } else {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch);
                curl_close($ch);
                return $response;
            }
        } catch (\Throwable $th) {
            \Log::error('Automas SMS Error: ' . $th->getMessage());
            return false;
        }
    }
}