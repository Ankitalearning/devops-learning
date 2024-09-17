<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Authorization, Content-Type, X-Auth-Token');

use Dompdf\Dompdf;

class CommonMethod extends MY_Model
{
    public function uploadDynamicUrl($var, $filename)
    {
        $url = $_SERVER['DOCUMENT_ROOT'] . '/api/uploads/' . $var . $filename;
        return $url;
    }
    public function uploadDynamicUrlWithHttps($var, $filename)
    {
        $url = 'https://plumberbath.basiq360.com/api/uploads/' . $var . $filename;
        return $url;
    }
    public function baseUrl()
    {
        $url = ' https://plumberbath.basiq360.com/';
        return $url;
    }

    public function googleApiKey()
    {
        $api = $this->db->select('*')->from('all_google_apis')->where('use_count<', 2000)->get()->row_array();
        $key = $api['api_key'];
        $count = $api['use_count'] + 1;
        $this->db->where('all_google_apis.id', $api['id']);
        $this->db->update('all_google_apis', ['use_count' => $count]);
        return $this->response_msg(200, '', $key);
    }
    public function response_msg($error_code, $case = '', $response = [], $customMsg = '')
    {
        $errorMsg = $this->db->select('*')->from('all_error_code_msg')->where('all_error_code_msg.error_code', $error_code)->get()->row_array();

        if ($customMsg == '') {
            if (is_array($response)) {
                $default_data = ['statusMsg' => $errorMsg['error_mesage'], 'statusCode' => $error_code, 'case' => $case];
                return array_merge($default_data, $response);
            } else {
                $default_data = ['statusMsg' => $errorMsg['error_mesage'], 'statusCode' => $error_code, 'case' => $case];
                return array_push($default_data, $response);
            }
        } else {
            if (is_array($response)) {
                $default_data = ['statusMsg' => $customMsg, 'statusCode' => $error_code, 'case' => $case];
                return array_merge($default_data, $response);
            } else {
                $default_data = ['statusMsg' => $customMsg, 'statusCode' => $error_code, 'case' => $case];
                return array_push($default_data, $response);
            }
        }
    }

    public function base64img_to_normal_img($img = Null)
    {
        if ($img == Null) {
            return 'Image File Cannot Be Blank';
        }

        $png_word = 'data:image/png;base64,';
        $jpeg_word = 'data:image/jpeg;base64,';
        $jpg_word = 'data:image/jpg;base64,';

        $ext = '';

        if (strpos($img, $png_word) !== false) {
            $img = str_replace('data:image/png;base64,', '', $img);
            $ext = 'png';
        } else if (strpos($img, $jpeg_word) !== false) {
            $img = str_replace('data:image/jpeg;base64,', '', $img);
            $ext = 'jpeg';
        } else if (strpos($img, $jpg_word) !== false) {
            $img = str_replace('data:image/jpg;base64,', '', $img);
            $ext = 'jpg';
        }
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        return $this->response_msg(200, '', ['data' => $data, 'ext' => $ext]);
    }

    public function all_state_list()
    {
        $this->db->select('DISTINCT(abq_postal_master.state_name) as state_name');
        $this->db->from('abq_postal_master');
        $this->db->where('abq_postal_master.del', 0);
        $this->db->order_by('abq_postal_master.state_name', 'ASC');
        $all_state = $this->db->get()->result_array();


        if (count($all_state) > 0) {
            return $this->response_msg(200, '', ['all_state' => $all_state]);
        } else {
            return $this->response_msg(200, '', ['all_state' => []], 'No Data Found');
        }
    }

    public function all_district_list()
    {
        if (!isset($_POST['state_name']) || empty($_POST['state_name'])) {
            return $this->response_msg(400, 'state_name');
        }
        $this->db->select('DISTINCT(abq_postal_master.district_name) as district_name');
        $this->db->from('abq_postal_master');
        $this->db->where('abq_postal_master.state_name', $_POST['state_name']);
        $this->db->where('abq_postal_master.del', 0);
        $this->db->order_by('abq_postal_master.district_name', 'ASC');

        $all_district = $this->db->get()->result_array();

        if (count($all_district) > 0) {
            return $this->response_msg(200, '', ['all_district' => $all_district]);
        } else {
            return $this->response_msg(200, '', ['all_district' => []], 'No Data Found');
        }
    }

    public function designation_list()
    {
        $designation = [];
        if (!isset($_POST['user_type']) || empty($_POST['user_type']) || ($_POST['user_type'] != 'System User' && $_POST['user_type'] != 'Sales User')) {
            return $this->response_msg(400, 'user_type');
        }

        if (isset($_POST['user_type']) && $_POST['user_type']) {

            $this->db->select('roles.*');
            $this->db->from('roles');
            $this->db->where("roles.id!=", "1");
            if (isset($_POST['user_type']) && $_POST['user_type'] == "System User") {
                $this->db->where("roles.user_type", "System User");
            } else if (isset($_POST['user_type']) && $_POST['user_type'] == "Sales User") {
                $this->db->where("roles.user_type", "Sales User");
            }
            $this->db->where("roles.del", 0);
            $designation = $this->db->get()->result_array();
        }

        return $designation;
    }

    public function get_sales_user_List()
    {
        $junior = $this->getJuniorForSystem();
        $this->db->select('sfa_user.id,sfa_user.employee_id , sfa_user.name,sfa_user.contact_01 as mobile_no , roles.id as role_id , roles.role_name');
        $this->db->from('sfa_user');

        if (isset($this->payload_val->{'id'}) && $this->payload_val->{'id'} != 1) {
            if (isset($junior) && !empty($junior)) {
                $this->db->where_in('sfa_user.id', $junior);
            } else {
                $this->db->where('sfa_user.id', $this->payload_val->{'id'});
            }
        }

        $this->db->join("roles", "roles.id = sfa_user.designation_id", 'left');
        if (isset($this->payload_val->{'id'})) {

            if (isset($_POST['userType']) && $_POST['userType'] == 'Sales') {
                $this->db->group_start();
                $this->db->where('sfa_user.access_level', 2);
                $this->db->or_where("sfa_user.userTypeCheck", 'Sales');
                $this->db->group_end();
            } else {
                $this->db->group_start();
                $this->db->where("sfa_user.userTypeCheck", 'Service');
                $this->db->or_where("sfa_user.user_type", 'Service Engineer');
                $this->db->group_end();
            }
        }
        if (isset($_POST['search']) && $_POST['search']) {
            $this->db->like("sfa_user.name", $_POST['search']);
        }
        $this->db->where("sfa_user.del", 0);
        $this->db->where("sfa_user.status", 1);
        $this->db->where("sfa_user.id!=", 1);
        if (isset($_POST['id']) && $_POST['id']) {
            $this->db->where("sfa_user.id!=", $_POST['id']);
        }
        $this->db->order_by('sfa_user.name', 'ASC');
        $all_sales_user = $this->db->get()->result_array();
        // echo($this->db->last_query());die;


        if (count($all_sales_user) > 0) {
            return $this->response_msg(200, '', ['all_sales_user' => $all_sales_user]);
        } else {
            return $this->response_msg(200, '', [], 'No Data Found');
        }
    }


    public function get_sales_user_List_distributor_wise()
    {
        $this->db->select('sfa_user.id,sfa_user.employee_id , sfa_user.name,sfa_user.contact_01 as mobile_no , roles.id as role_id , roles.role_name');
        $this->db->from('sfa_user');
        $this->db->join("roles", "roles.id = sfa_user.designation_id", 'left');
        // $this->db->join("sfa_dr_assign", "sfa_dr_assign.assigned_to = sfa_user.id", 'left');
        if (isset($_POST['search']) && $_POST['search']) {
            $this->db->like("sfa_user.name", $_POST['search']);
        }
        // $this->db->where("sfa_dr_assign.dr_id", $_POST['dr_id']);
        $this->db->where("sfa_user.del", 0);
        $this->db->where("sfa_user.status", 1);
        $this->db->where("sfa_user.access_level!=", 1);
        $this->db->order_by('sfa_user.name', 'ASC');

        $all_sales_user = $this->db->get()->result_array();

        if (count($all_sales_user) > 0) {
            return $this->response_msg(200, '', ['all_sales_user' => $all_sales_user]);
        } else {
            return $this->response_msg(200, '', [], 'No Data Found');
        }
    }

    public function get_area_list()
    {
        if (!isset($_POST['state']) || empty($_POST['state'])) {
            return $this->response_msg(400, 'state');
        }
        if (!isset($_POST['district']) || empty($_POST['district'])) {
            return $this->response_msg(400, 'district');
        }

        $new_arr = array();
        $this->db->DISTINCT();
        $this->db->select('area');
        $this->db->from('abq_postal_master');
        $this->db->where("abq_postal_master.state_name", $_POST['state']);
        $this->db->where("abq_postal_master.district_name", $_POST['district']);
        $this->db->where("abq_postal_master.area != ''");
        $this->db->where("abq_postal_master.del = '0'");
        $this->db->order_by('abq_postal_master.area', 'ASC');
        $area = $this->db->get()->result_array();
        foreach ($area as $key2 => $newVal) {
            $new_arr[] = $newVal['area'];
        }

        if (count($new_arr) > 0) {
            return $this->response_msg(200, '', ['area' => $new_arr]);
        } else {
            return $this->response_msg(200, '', ['area' => []], 'No Data Found');
        }
    }

    public function order_status_list()
    {
        $order_status_list = ["Draft", "Reject"];
        $order_status_list = implode(',', $order_status_list);

        if (!empty($order_status_list)) {
            return $this->response_msg(200, '', $order_status_list);
        } else {
            return $this->response_msg(200, '', [], 'No Data Found');
        }
    }

    public function convert_to_days_and_time($dateTime)
    {

        date_default_timezone_set('Asia/Kolkata');

        $startDate = date("Y-m-d H:i:s", strtotime($dateTime));

        $endDate = date("Y-m-d H:i:s");
        // calculate difference between two date in second------
        $difference = abs(strtotime($endDate) - strtotime($startDate));

        $difference_minute = floor($difference / 60);

        $hours = floor($difference_minute / 60);

        $days = floor($hours / 24);

        return $this->response_msg(200, '', ["difference_in_minute" => $difference_minute, "hours" => $hours, "days" => $days]);
    }

    public function send_sms($mobile, $message, $otp = NULL)
    {
        if (!empty($mobile) && !empty($message) && !empty($otp)) {

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://www.smsjust.com/blank/sms/user/urlsms.php?username=abacusdesk&pass=$6@AtEn3&senderid=ABCSIT&dest_mobileno=" . $mobile . "&msgtype=TXT&message=" . $message . "&response=Y",
                // CURLOPT_URL => "https://www.smsjust.com/blank/sms/user/urlsms.php?username=caretinhtptrans&pass=inft@crt&senderid=EAUSET&dest_mobileno=" . $mobile . "&msgtype=TXT&message=" . $message . "&response=Y",

                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '',
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            if ($response) {
                return $this->response_msg(200, '', ['otp' => $otp]);
            } else {
                return $this->response_msg(500, '', ['otp' => $otp]);
            }
        } else {
            return $this->response_msg(400, '', ['otp' => '']);
        }
    }

    public function send_mail($subject, $msgTemplate, $to, $cc = Null)
    {

        $this->load->library('email');

        $config = array(
            'protocol' => 'smtp',
            'smtp_host' => 'ssl://smtp.googlemail.com',
            'smtp_port' => 587,
            'smtp_user' => 'crmsupport@abacusdesk.co.in',
            'smtp_pass' => 'Support@177',
            'mailtype' => 'html',
            'charset' => 'iso-8859-1',
            'wordwrap' => TRUE
        );
        $this->load->library('email', $config);
        $this->email->set_newline("\r\n");
        $this->email->from('noreply@abacusdesk.com', 'Abacusdesk');
        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($msgTemplate);

        if ($this->email->send()) {
            return $this->response_msg(200, '', true);
        } else {
            return $this->response_msg(500, '', false);
        }
    }

    public function send_push_notification_firebase($message, $title, $id, $user_type, $data)
    {
        if ($user_type == 'user') {
            $user  = $this->db->select('*')->from('sfa_user')->where('id', $id)->where('del', 0)->get()->row();
        } elseif ($user_type == 'influencer') {
            $user = $this->db->select('*')->from('influencer_customer')->where('id', $id)->where('del', 0)->get()->row();
        } else {
            $user = $this->db->select('*')->from('sfa_dr')->where('id', $id)->where('del', 0)->get()->row();
        }

        if ($user) {
            if ($user_type == 'user' && $user->user_type == 'Service Engineer') {
                $registrationIds = $user->unique_code;
            } else {
                $registrationIds = $user->user_token;
            }
        } else {
            $registrationIds = '';
        }

        $appId = 'bc55d075-bb2b-44a5-8c93-d579c2b758be';
        $apiKey = 'NGFmYzFlZmQtN2MyMi00ZDM3LTg2MjMtNGVhZmVjY2UxODRl';


        $registrationIds = array($registrationIds);
        $fields = array(
            'app_id' => $appId,
            'contents' => [
                'en' => $message,
            ],
            'headings' => [
                'en' => $title,
            ],
            'include_external_user_ids' => $registrationIds, // Replace with the OneSignal player ID
            'data' => $data
        );
        $headers = array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . $apiKey,
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://onesignal.com/api/v1/notifications');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        if ($result === false) {
            echo 'Curl error: ' . curl_error($ch);
        } else {
        }
        curl_close($ch);
        // print_r($result);exit;
        return json_decode($result);
    }

    public function send_push_notification_in_app($send_to, $subject, $message, $user_type, $created_by, $created_by_name)
    {
        $notification = array(
            'date_created' => date('Y-m-d H:i:s'),
            'created_by' => $created_by,
            'created_by_name' => $created_by_name,
            'subject' => $subject,
            'msg' => $message
        );
        // var_dump($message);die;

        $this->db->insert('sfa_push_notification', $notification);
        $last_id = $this->db->insert_id();

        if ($last_id) {
            $notification_detail = array(
                'date_created' => date('Y-m-d H:i:s'),
                'notice_id' => $last_id,
                'type' => $user_type,
                'send_to' => $send_to,
                'read_status' => 0,
            );
            $this->db->insert('sfa_push_notification_to', $notification_detail);
            $last_id1 = $this->db->insert_id();
            if ($last_id1) {
                return $this->response_msg(200, '');
            } else {
                return $this->response_msg(500, '');
            }
        } else {
            return $this->response_msg(500, '');
        }
    }


    public function send_all_type_of_notification($send_to, $pushMsg, $user_type, $created_by, $created_by_name, $pushTitle, $data)
    {
        if ($user_type == 'user') {
            $notification_flag = $this->db->select('notification_flag')->from('sfa_user')->where('id', $this->payload_val->{'id'})->get()->row_array();
        }

        if (isset($notification_flag['notification_flag']) && $notification_flag['notification_flag'] == 1) {
        } else {
            $inApp_response = $this->send_push_notification_in_app($send_to, $pushTitle, $pushMsg, $user_type, $created_by, $created_by_name);
            $firebase_response = $this->send_push_notification_firebase($pushMsg, $pushTitle, $send_to, $user_type, $data);
        }
    }
    public function get_reporting_manager_id($id)
    {
        $this->db->select("sfa_asm_assign.rsm_id");
        $this->db->from("sfa_asm_assign");
        $this->db->where('sfa_asm_assign.asm_id', $id);
        $this->db->where('sfa_asm_assign.del', 0);
        $reporting_manager_id = $this->db->get()->row('rsm_id');

        return $reporting_manager_id;
    }

    public function genratePdf($html, $pdfName)
    {
        include_once APPPATH . 'third_party/dompdf/dompdf/autoload.inc.php';
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        // $dompdf->stream();
        $dompdf->Output($this->uploadDynamicUrl('Pdf/', $pdfName . '.pdf', 'F'));
    }

    public function getLedgerBalanceInfluencer($id, $type)
    {
        $this->db->select('influencer_ledger.balance');
        $this->db->from('influencer_ledger');
        $this->db->where('influencer_ledger.influencer_id', $id);
        $this->db->where('influencer_ledger.influencer_type', $type);
        $this->db->order_by('influencer_ledger.id', 'DESC');
        $influencer_ledger = $this->db->get()->row_array();

        if (isset($influencer_ledger) && sizeof($influencer_ledger) > 0) {
            return $this->response_msg(200, '', $influencer_ledger);
        } else {
            return  $influencer_ledger['balance'] = 0;
        }
    }

    public function getLedgerBalancseDistributor($id, $type)
    {
        $this->db->select('distributor_ledger.balance');
        $this->db->from('distributor_ledger');
        $this->db->where('distributor_ledger.network_id', $id);
        $this->db->where('distributor_ledger.network_type', $type);
        $this->db->order_by('distributor_ledger.id', 'DESC');
        $distributor_ledger = $this->db->get()->row('balance');
        if (isset($distributor_ledger) && $distributor_ledger != 0) {
            return $distributor_ledger;
        } else {
            return 0;
        }
    }

    public function create_pop_transaction_log($created_by_id, $created_by_type, $created_by_name, $pop_id, $item_name, $transaction_type, $transfer_to_type, $transfer_to_id, $transfer_to_name, $transfer_to_uniq_id, $stock_qty, $remaining_stock, $remarks)
    {

        $transaction_data = array(
            'date_created' => date('Y-m-d H:i:s'),
            'created_by' => $created_by_id,
            'created_by_type' => $created_by_type,
            'created_by_name' => $created_by_name,
            'pop_id' => $pop_id,
            'pop_item_name' => $item_name,
            'transaction_type' => $transaction_type,
            'transfer_to_type' => $transfer_to_type,
            'transfer_to_id' => $transfer_to_id,
            'transfer_to_name' => $transfer_to_name,
            'transfer_to_uniq_id' => $transfer_to_uniq_id,
            'stock_qty' => $stock_qty,
            'remaining_stock' => $remaining_stock,
            'remarks' => $remarks,
        );

        $this->db->insert('sfa_pop_transaction_history', $transaction_data);
        $transaction_id = $this->db->insert_id();

        if (!empty($transaction_id)) {
            return $this->response_msg(200, '', $transaction_id);
        } else {
            return $this->response_msg(500, '');
        }
    }

    public function checkDrMobile()
    {
        $this->db->select('sfa_dr.id , sfa_dr.status ,sfa_dr.type');
        $this->db->from('sfa_dr');
        $this->db->where('sfa_dr.mobile', $_POST['mobile']);
        $this->db->where('sfa_dr.del', 0);
        $mobileExist = $this->db->get()->row_array();

        $this->db->select('influencer_customer.id');
        $this->db->from('influencer_customer');
        $this->db->where('influencer_customer.mobile_no', $_POST['mobile']);
        $this->db->where('influencer_customer.del', 0);
        $already_exist_influencer = $this->db->get()->num_rows();

        if ($mobileExist) {
            return $this->response_msg(403, '', [], 'Mobile number already exist');
        } else if ($already_exist_influencer > 0) {
            return $this->CommonMethod->response_msg(200, '', [], 'Mobile No. Already Exist with influencer');
        } else {
            return $this->response_msg(200, '');
        }
    }
    public function getPostalInfo()
    {
        if (!isset($_POST['pincode']) || empty($_POST['pincode'])) {
            return $this->CommonMethod->response_msg(400, 'pincode');
        }

        $this->db->select('abq_postal_master.state_name,abq_postal_master.district_name,abq_postal_master.city');
        $this->db->where('abq_postal_master.pincode', $_POST['pincode']);
        $this->db->group_by('abq_postal_master.id');
        $this->db->where('abq_postal_master.del', 0);
        $result = $this->db->get('abq_postal_master')->row_array();

        // $result['state_name']=ucwords(strtolower($result['state_name']));
        // $result['district_name']=ucwords(strtolower($result['district_name']));


        if (sizeof($result) > 0) {
            return ['statusCode' => 200, 'statusMsg' => 'Success', 'result' => $result];
        } else {
            return ['statusCode' => 200, 'statusMsg' => 'Success', 'result' => []];
        }
    }

    public function distributorsList()
    {

        if (isset($_POST['type']) && $_POST['type'] == 3 && isset($_POST['dr_id']) && !empty($_POST['dr_id'])) {
            $this->db->select('sfa_dealer_assign.dealer_id');
            $this->db->from('sfa_dealer_assign');
            $this->db->where('sfa_dealer_assign.del', 0);
            $this->db->where('sfa_dealer_assign.distributor_id', $_POST['dr_id']);
            $data = $this->db->get()->result_array();

            $dealer_ids = array_values(array_column($data, 'dealer_id'));

            if (empty($dealer_ids)) {
                return $this->response_msg(200, '', ['distributors' => []], 'No Data Found');
            }
        }

        $this->db->select("sfa_dr.id,sfa_dr.company_name,sfa_dr.dr_code,sfa_dr.mobile");
        $this->db->from("sfa_dr");
        if (isset($_POST['type']) && !empty($_POST['type'])) {
            $this->db->where("sfa_dr.type", $_POST['type']);
        } else {
            $this->db->where("sfa_dr.type", 1);
        }
        if (isset($dealer_ids) && !empty($dealer_ids)) {
            $this->db->where_in("sfa_dr.id", $dealer_ids);
        }
        if (isset($_POST['state']) && $_POST['state']) {
            $this->db->like("sfa_dr.state", $_POST['state']);
        }
        if (isset($_POST['search']) && $_POST['search']) {
            $this->db->group_start();
            $this->db->like("sfa_dr.company_name", $_POST['search']);
            $this->db->or_like("sfa_dr.dr_code", $_POST['search']);
            $this->db->group_end();
        }
        $this->db->where("sfa_dr.del", 0);
        $this->db->order_by('sfa_dr.company_name', 'ASC');
        $result = $this->db->get()->result_array();

        if (isset($result) && sizeof($result) > 0) {
            return $this->response_msg(200, '', ['distributors' => $result]);
        } else {
            return $this->response_msg(200, '', ['distributors' => []], 'No Data Found');
        }
    }

    public function generateCsv($filename, $result_data)
    {
        $uploadPath = $this->uploadDynamicUrl('Download_excel/', $filename);

        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        $fp = fopen($uploadPath, 'w');
        $header = array_keys($result_data[0]);
        fputcsv($fp, $header);
        foreach ($result_data as $key => $row) {
            $array_val = array_values($row);
            $entry_row = array();
            foreach ($array_val as $val) {
                array_push($entry_row, $val);
            }
            fputcsv($fp, $entry_row);
        }
        fclose($fp);
        return true;
    }

    public function get_longitude_latitude_from_adress($address)
    {
        $lat = 0;
        $long = 0;
        $address = str_replace(',,', ',', $address);
        $address = str_replace(', ,', ',', $address);
        $address = str_replace(" ", "+", $address);
        try {
            $json = file_get_contents('https://maps.google.com/maps/api/geocode/json?address=' . $address . '&key=AIzaSyBZ4zXanVSs4A1kSVIDCIzDqtMbk6Tv3bg');
            $json1 = json_decode($json);
            if ($json1->{'status'} == 'ZERO_RESULTS') {
                return [
                    'lat' => 0,
                    'lng' => 0
                ];
            }

            if (isset($json1->results)) {
                $lat = ($json1->{'results'}[0]->{'geometry'}->{'location'}->{'lat'});
                $long = ($json1->{'results'}[0]->{'geometry'}->{'location'}->{'lng'});
            }
        } catch (exception $e) {
        }
        return $this->response_msg(200, '', ['lat' => $lat, 'lng' => $long]);
    }

    // public function sendWhatsAppMsg($data)
    // {
    //     $data = json_encode($data);
    //     $curl = curl_init();
    //     curl_setopt_array($curl, array(
    //         CURLOPT_URL => 'https://api.pinbot.ai/v1/wamessage/sendMessage',
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_ENCODING => '',
    //         CURLOPT_MAXREDIRS => 10,
    //         CURLOPT_TIMEOUT => 0,
    //         CURLOPT_FOLLOWLOCATION => true,
    //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST => 'POST',
    //         CURLOPT_POSTFIELDS => $data,
    //         CURLOPT_HTTPHEADER => array(
    //             'apikey: b80b48aa-7534-11ed-a7c7-9606c7e32d76',
    //             'Content-Type: application/json'
    //         ),
    //     ));

    //     $response = curl_exec($curl);
    //     curl_close($curl);
    //     return $response;
    //     return $this->response_msg(200, '', $response);
    // }
    public function sendWhatsAppMsg($data)
    {
        $curl = curl_init();
        $data = json_encode($data);

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://api.pinbot.ai/v1/wamessage/sendMessage',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_HTTPHEADER => array(
                    'apikey: e8b27ea8-ea3f-11ed-a7c7-9606c7e32d76',
                    'Content-Type: application/json'
                ),
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    public function generateSystemLog($activity_by, $activity_by_name, $module_name, $activity_remark)
    {
        $this->db->insert('all_system_activity_logs', ['date_created' => date('Y-m-d H:i:s'), 'activity_by' => $activity_by, 'activity_by_name' => $activity_by_name, 'module_name' => $module_name, 'activity_remark' => $activity_remark]);
    }
    public function reduceImage($filename)
    {
        $config['image_library'] = 'gd2';
        $config['source_image'] = $filename;
        $config['create_thumb'] = FALSE;
        $config['maintain_ratio'] = TRUE;
        $config['quality'] = '50';
        $config['width'] = '700';
        $config['height'] = '1000';
        $config['new_image'] = $filename;
        $this->load->library('image_lib', $config);
        $resize = $this->image_lib->resize();
    }
    public function resetDeviceId()
    {
        if (!isset($_POST['type']) || empty($_POST['type']) || ($_POST['type'] != 'user' && $_POST['type'] != 'customer')) {
            return $this->response_msg(400, 'type');
        }
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            return $this->response_msg(400, 'type');
        }

        $reset = ['latest_login' => '0000-00-00 00:00:00', 'device_unique_id' => ''];

        if ($_POST['type'] == 'user') {
            $this->db->where('sfa_user.id', $_POST['id']);
            $update = $this->db->update('sfa_user', $reset);
        } elseif ($_POST['type'] == 'customer') {
            $this->db->where('sfa_dr.id', $_POST['id']);
            $update = $this->db->update('sfa_dr', $reset);
        }

        if (isset($update) && $update > 0) {
            return $this->response_msg(200, '');
        } else {
            return $this->response_msg(500, '');
        }
    }
    public function randomCoupon()
    {
        $coupon = rand(100, 999) . uniqid();

        $check = $this->db->select('id')->from('offer_coupon')->where('offer_coupon.coupon_code LIKE', $coupon)->get()->num_rows();

        if ($check > 0) {
            $this->randomCoupon();
        }
        return $coupon;
    }
    public function getJuniorForSystem()
    {
        $ids = [];
        $this->load->model('app/CommonModel', 'common');

        if ($this->payload_val->{'access_level'} == 1) {
            $this->db->select('sfa_system_user_assign.assigned_user');
            $this->db->from('sfa_system_user_assign');
            $this->db->where("sfa_system_user_assign.del", 0);
            $this->db->where("sfa_system_user_assign.system_user_id", $this->payload_val->{'id'});
            $assign_system_user = $this->db->get()->result_array();
            foreach ($assign_system_user as $key => $value) {
                $junior = [];
                $ids[] = $value['assigned_user'];
                $junior = $this->common->getJunior($value['assigned_user']);
                if (!empty($junior)) {
                    foreach ($junior as $jun) {
                        array_push($ids, $jun);
                    }
                }
            }
        } else {
            $junior = [];
            $ids[] = $this->payload_val->{'id'};
            $junior = $this->common->getJunior($this->payload_val->{'id'});
            if (!empty($junior)) {
                foreach ($junior as $jun) {
                    array_push($ids, $jun);
                }
            }
        }
        return $ids;
    }
    public function masterSearch()
    {
        $_POST = json_decode((file_get_contents('php://input')), true);
        $this->db->select('sfa_dr.id,sfa_dr.type,sfa_dr.company_name as name ,sfa_dr.state,sfa_dr.lead_type,sfa_dr.mobile,distribution_network.distribution_type,distribution_network.module_name');
        $this->db->from('sfa_dr');
        $this->db->join('distribution_network', 'sfa_dr.type=distribution_network.type AND distribution_network.del=0', 'left');
        if (isset($_POST['search']) && $_POST['search'] != '') {
            $this->db->like('sfa_dr.company_name', $_POST['search']);
            $this->db->or_like('sfa_dr.name', $_POST['search']);
            $this->db->or_like('sfa_dr.mobile', $_POST['search']);
        }
        $this->db->where('sfa_dr.del', '0');
        $this->db->limit(100);
        $data = $this->db->get()->result_array();

        $this->db->select('influencer_customer.id,influencer_customer.type,influencer_customer.state,influencer_customer.mobile_no as mobile,influencer_customer.name,"" as lead_type,distribution_network.distribution_type,distribution_network.module_name');
        $this->db->from('influencer_customer');
        $this->db->join('distribution_network', 'influencer_customer.type=distribution_network.type AND distribution_network.del=0', 'left');
        if (isset($_POST['search']) && $_POST['search'] != '') {
            $this->db->like('influencer_customer.name', $_POST['search']);
            $this->db->or_like('influencer_customer.mobile_no', $_POST['search']);
        }
        $this->db->where('influencer_customer.del', '0');
        $this->db->limit(100);
        $data1 = $this->db->get()->result_array();

        $this->db->select('sfa_user.id,sfa_user.state_name,sfa_user.contact_01 as mobile,sfa_user.name,"" as lead_type,"" as module_name,"" as type');
        $this->db->from('sfa_user');
        if (isset($_POST['search']) && $_POST['search'] != '') {
            $this->db->like('sfa_user.name', $_POST['search']);
            $this->db->or_like('sfa_user.contact_01', $_POST['search']);
        }
        $this->db->where('sfa_user.del', '0');
        $this->db->limit(100);
        $data2 = $this->db->get()->result_array();

        $data = array_merge($data2, $data1, $data);

        return $this->response_msg(200, '', ['result' => $data]);
    }

    public function get_assign_distributor()
    {

        $Ids = $this->getJuniorForSystem($this->payload_val->{'id'});

        if (isset($Ids) && !empty($Ids)) {
            $assigned_to = $Ids;
            array_push($assigned_to, $this->payload_val->{'id'});
        } else {
            $assigned_to[] = $this->payload_val->{'id'};
        }
        $this->db->select('sfa_dr_assign.dr_id');
        $this->db->from('sfa_dr_assign');
        $this->db->join('sfa_dr', 'sfa_dr.id=sfa_dr_assign.dr_id AND sfa_dr_assign.del=0', 'left');
        $this->db->where_in('sfa_dr_assign.assigned_to', $assigned_to);
        $this->db->where('sfa_dr_assign.del', 0);
        $this->db->where('sfa_dr.type', 1);
        $this->db->group_by('sfa_dr_assign.dr_id');
        $assignedDistributors = $this->db->get()->result_array();
        $distributors = array_column($assignedDistributors, 'dr_id');

        return $distributors;
    }
    public function distributorsOrder()
    {

        $Ids = $this->getJuniorForSystem($this->payload_val->{'id'});

        if (isset($Ids) && !empty($Ids)) {
            $assigned_to = $Ids;
            array_push($assigned_to, $this->payload_val->{'id'});
        } else {
            $assigned_to[] = $this->payload_val->{'id'};
        }
        $this->db->select('sfa_dr_assign.dr_id');
        $this->db->from('sfa_dr_assign');
        $this->db->join('sfa_dr', 'sfa_dr.id=sfa_dr_assign.dr_id AND sfa_dr_assign.del=0', 'left');
        $this->db->where_in('sfa_dr_assign.assigned_to', $assigned_to);
        $this->db->where('sfa_dr_assign.del', 0);
        $this->db->where_in('sfa_dr.type', [1, 7]);
        $this->db->group_by('sfa_dr_assign.dr_id');
        $assignedDistributors = $this->db->get()->result_array();
        $distributors = array_column($assignedDistributors, 'dr_id');

        if (!empty($distributors)) {

            $this->db->select('sfa_primary_order.id');
            $this->db->from('sfa_primary_order');
            $this->db->where_in('sfa_primary_order.created_by', $distributors);
            $this->db->where('sfa_primary_order.del', 0);
            $this->db->group_start();
            $this->db->where('sfa_primary_order.created_by_type', 'distributor');
            $this->db->or_where('sfa_primary_order.created_by_type', 'DMS');
            $this->db->group_end();
            $orders = $this->db->get()->result_array();
            $ordersId = array_column($orders, 'id');
        } else {
            $ordersId = [];
        }

        return $ordersId;
    }

    public function zonalManager($ids)
    {
        $flag = 0;

        for ($i = 1; $i != $flag; $i++) {
            $prev = 0;
            $this->db->select("sfa_asm_assign.rsm_id,sfa_user.name");
            $this->db->from('sfa_asm_assign');
            $this->db->join('sfa_user', 'sfa_user.id = sfa_asm_assign.rsm_id', 'left');
            $this->db->where('sfa_asm_assign.asm_id', $ids);
            $this->db->where('sfa_asm_assign.del', 0);
            $this->db->where('sfa_user.del', 0);
            $rsm_id = $this->db->get()->row_array();

            if (!empty($rsm_id)) {
                $prev = $ids;
                $ids = $rsm_id['rsm_id'];

                if ($ids == 271) {
                    return $prev;
                    exit;
                }
            } else {
                $flag = 1;
                return $ids;
                exit;
            }
        }
    }
    public function get_sales_user_expenseList()
    {
        $junior = $this->getJuniorForSystem();
        $this->db->select('sfa_user.id,sfa_user.employee_id , sfa_user.name,sfa_user.contact_01 as mobile_no , roles.id as role_id , roles.role_name');
        $this->db->from('sfa_user');
        if (isset($this->payload_val->{'id'}) && $this->payload_val->{'id'} != 1) {
            if (isset($junior) && !empty($junior)) {
                $this->db->where_in('sfa_user.id', $junior);
            } else {
                $this->db->where('sfa_user.id', $this->payload_val->{'id'});
            }
        }
        $this->db->join("roles", "roles.id = sfa_user.designation_id", 'left');
        if (isset($_POST['search']) && $_POST['search']) {
            $this->db->like("sfa_user.name", $_POST['search']);
        }
        $this->db->where("sfa_user.del", 0);
        //$this->db->where("sfa_user.status", 1);
        // $this->db->where_not_in("sfa_user.designation_id", [16, 9, 8]);
        $this->db->where("sfa_user.access_level!=", 1);
        if (isset($_POST['id']) && $_POST['id']) {
            $this->db->where("sfa_user.id!=", $_POST['id']);
        }
        $this->db->order_by('sfa_user.name', 'ASC');
        $this->db->limit(20);
        $all_sales_user = $this->db->get()->result_array();

        if (count($all_sales_user) > 0) {
            return $this->response_msg(200, '', ['all_sales_user' => $all_sales_user]);
        } else {
            return $this->response_msg(200, '', [], 'No Data Found');
        }
    }


    public function dr_stock($dr_id, $product_id, $stock_count, $var)
    {
        $this->db->select('dr_stock.dr_id,dr_stock.product_id,dr_stock.current_stock');
        $this->db->from('dr_stock');
        $this->db->where('dr_stock.product_id', $product_id);
        $this->db->where('dr_stock.dr_id', $dr_id);
        $result = $this->db->get()->row_array();

        if (!empty($result)) {
            if ($var == 'increment') {
                $stck = $result['current_stock'] + $stock_count;
            } else {
                $stck = $result['current_stock'] - $stock_count;
            }

            $itemData = [

                'last_updated_on' =>   date('Y-m-d H:i:s'),
                'dr_id'  =>            $dr_id,
                'product_id'  =>       $product_id,
                'current_stock'  =>    $stck
            ];

            $this->db->where('dr_stock.product_id', $product_id);
            $this->db->where('dr_stock.dr_id', $dr_id);
            $this->db->update('dr_stock', $itemData);
        } else {
            $this->db->select('master_product.*');
            $this->db->from('master_product');
            $this->db->where('master_product.id', $product_id);
            $productData = $this->db->get()->row_array();

            if ($dr_id != 0) {
                $this->db->select('sfa_dr.type');
                $this->db->where('id', $dr_id);
                $type = $this->db->get('sfa_dr')->row('type');
            } else {
                $type = 0;
            }

            $itemData = [
                'last_updated_on' =>   date('Y-m-d H:i:s'),
                'dr_id'  =>            $dr_id,
                'product_id'  =>       $product_id,
                'current_stock'  =>     $stock_count,
                'product_detail'  =>  $productData['product_name'] . ' (' . $productData['product_code'] . ')',
                'dr_type' => $type
            ];

            $this->db->insert('dr_stock', $itemData);
        }

        if ($result) {
            return $this->CommonMethod->response_msg(200, '');
        } else {
            return $this->CommonMethod->response_msg(500, '');
        }
    }


    // public function dr_stock($dr_id, $product_id, $stock_count, $var)

    // {
    //     // $dr_id=1;
    //     // $product_id=1;
    //     // $stock_count=50;
    //     // $var='increment';

    //     $this->db->select('dr_stock.dr_id,dr_stock.product_id,dr_stock.current_stock');
    //     $this->db->from('dr_stock');
    //     $this->db->where('dr_stock.product_id', $product_id);
    //     $this->db->where('dr_stock.dr_id', $dr_id);
    //     $result = $this->db->get()->row_array();
    //     // print_r($result);die;


    //     if($var == 'increment')
    //     {
    //         $stck = $result['current_stock']+$stock_count;
    //     }
    //     else{
    //         $stck = $result['current_stock']-$stock_count;

    //     }

    //     if (!empty($result))
    //     {
    //         $this->db->select('dr_stock.dr_id,dr_stock.product_id');
    //         $this->db->from('dr_stock');

    //         $itemData = [

    //             'last_updated_on' =>   date('Y-m-d H:i:s'),
    //             'dr_id'  =>            $dr_id,
    //             'product_id'  =>       $product_id,
    //             'current_stock'  =>    $stck
    //         ];
    //         $this->db->where('dr_stock.product_id', $product_id);
    //         $this->db->where('dr_stock.dr_id', $dr_id);
    //         $this->db->update('dr_stock', $itemData);
    //     }
    //     else
    //     {
    //         $this->db->select("CONCAT(product_code , '-', product_name) AS product_detail");
    //         $this->db->from('master_product');
    //         $this->db->where('master_product.product-id', $product_id);
    //         $coupanData = $this->db->get()->result_array();
    //         $itemData = [
    //             'last_updated_on' =>   date('Y-m-d H:i:s'),
    //             'dr_id'  =>            $dr_id,
    //             'product_id'  =>       $product_id,
    //             'current_stock'  =>    $stck,
    //             'product_detail'  =>   $coupanData,
    //         ];
    //         $this->db->insert('dr_stock', $itemData);

    //     }
    //     if ($result) {
    //         return $this->CommonMethod->response_msg(200, '');
    //     } else {
    //         return $this->CommonMethod->response_msg(500, '');
    //     }
    // }

    public function bank_auth_credentials()
    {
        $credentials = [

            'client_id'  =>           'ee5f9a3fdcd6aa41f0637263bb9e749d',
            'client_secret'  =>       '88b574c56cb08b641cb868d7c90dd3ec',
        ];

        return $credentials;
    }

    public function api_urls()
    {
        $urls = [

            'pay_url'  =>    'https://apps.basiq360.com/payout/api/index.php/CreateTransaction/payout',
            'get_bal'  =>    'https://apps.basiq360.com/payout/api/index.php/GetLedgerDetails/get_ledger',
            'trans_his'  =>  'https://apps.basiq360.com/payout/api/index.php/GetLedgerDetails/ledger_transaction',
            'status_url'  =>  'https://apps.basiq360.com/payout/api/index.php/CreateTransaction/status_api/',
        ];

        return $urls;
    }

    public function pincodeWiseState()

    {

        if (!isset($_POST['pincode']) || empty($_POST['pincode'])) {

            return $this->CommonMethod->response_msg(400, 'pincode ');
        }

        $this->db->select('DISTINCT(abq_postal_master.state_name) as state_name ,(abq_postal_master.district_name) as district_name,(abq_postal_master.city) as city_name');

        $this->db->from('abq_postal_master');

        $this->db->where('abq_postal_master.pincode', $_POST['pincode']);

        $this->db->where('abq_postal_master.del', 0);

        $all_Satte_district = $this->db->get()->row_array();

        // print_r($all_Satte_district); die;



        if (isset($all_Satte_district) && sizeof($all_Satte_district) > 0) {
            return $this->CommonMethod->response_msg(200, '', ['all_State_district' => $all_Satte_district]);
        } else {

            return $this->CommonMethod->response_msg(404, '', ['all_State_district' => []], 'Please Enter valid Pincode');
        }
    }
    public function getJuniorSystemUser()
    {
        $ids = [];
        $this->load->model('app/CommonModel', 'common');
        $this->db->select('sfa_system_user_assign.assigned_user');
        $this->db->from('sfa_system_user_assign');
        $this->db->where("sfa_system_user_assign.del", 0);
        $this->db->where("sfa_system_user_assign.system_user_id", $this->payload_val->{'id'});
        $assign_system_user = $this->db->get()->result_array();




        foreach ($assign_system_user as $key => $value) {
            $junior = [];
            $ids[] = $value['assigned_user'];
            $junior = $this->getJuniorUser($value['assigned_user']);
            // print_r($junior);
            // die();
            if (!empty($junior)) {
                foreach ($junior as $jun) {
                    array_push($ids, $jun);
                }
            }
        }
        $ids = array_unique($ids);
        return $ids;
    }
    public function getJuniorUser($ids)
    {
        $remove = explode(" ", $ids);
        $ids = explode(" ", $ids);
        $flag = 0;
        $tmp_arr = [];
        $result = [];

        for ($i = 1; $i != $flag; $i++) {
            if ($flag == 0) {
                if (!empty($tmp_arr)) {
                    $result = array_diff($ids, $tmp_arr);
                }
                $this->db->select("sfa_system_user_assign.assigned_user");
                $this->db->from('sfa_system_user_assign');
                $this->db->join('sfa_user', 'sfa_user.id = sfa_system_user_assign.assigned_user', 'left');
                if (!empty($tmp_arr)) {
                    $result = (!empty($result)) ? $result : [0];
                    $this->db->where_in('sfa_system_user_assign.system_user_id', $result);
                } else if (empty($tmp_arr)) {
                    // $ids = (!empty($result)) ? $result : [0];
                    $this->db->where_in('sfa_system_user_assign.system_user_id', $ids);
                }
                $this->db->where('sfa_system_user_assign.del', 0);
                $this->db->where('sfa_user.status', 1);
                $this->db->where('sfa_user.del', 0);
                $assigned_user = $this->db->get()->result_array();
                $newIds = array_column($assigned_user, 'assigned_user');

                if (sizeof($newIds) > 0) {
                    $tmp_arr = array_merge($tmp_arr, $ids);
                    $ids = array_merge($ids, $newIds);
                } else {
                    $flag = 1;
                    $ids = array_diff($ids, $remove);
                    return $ids;
                    exit;
                }
            } else {
                $ids = array_diff($ids, $remove);
                return $ids;
                exit;
            }
        }
    }
}
