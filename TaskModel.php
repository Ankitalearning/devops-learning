<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
class  TaskModel extends MY_Model
{
    public function addTask()
    {
        if (!isset($_POST['data']['created_by_id']) || empty($_POST['data']['created_by_id'])) {
            return $this->CommonMethod->response_msg(400, 'created_by_id');
        }
        if (!isset($_POST['data']['created_by_name']) || empty($_POST['data']['created_by_name'])) {
            return $this->CommonMethod->response_msg(400, 'created_by_name');
        }
        if (!isset($_POST['data']['created_by_type']) || empty($_POST['data']['created_by_type'])) {
            return $this->CommonMethod->response_msg(400, 'created_by_type');
        }
        if (!isset($_POST['data']['assign_user']) || empty($_POST['data']['assign_user'])) {
            return $this->CommonMethod->response_msg(400, 'assign_user');
        }
        if (!isset($_POST['data']['name']) || empty($_POST['data']['name'])) {
            return $this->CommonMethod->response_msg(400, 'name');
        }
        if (!isset($_POST['data']['user_type']) || empty($_POST['data']['user_type'])) {
            return $this->CommonMethod->response_msg(400, 'user_type');
        }
        if (!isset($_POST['data']['message']) || empty($_POST['data']['message'])) {
            return $this->CommonMethod->response_msg(400, 'message');
        }
        $taskData = array(
            'date_created' => date('Y-m-d H:i:s'),
            'created_by' => $_POST['data']['created_by_id'],
            'created_by_name' => $_POST['data']['created_by_name'],
            'created_by_type' => $_POST['data']['created_by_type'],
            'assign_to_id' => $_POST['data']['assign_user'],
            'assign_to_name' => $_POST['data']['name'],
            'assign_to_type' => $_POST['data']['user_type'],
            'escalation_description' => $_POST['data']['message'],
            'promise_date' => $_POST['data']['promise_date'],
            'status' => 'promise_done',
            'del' => 0
        );

        $this->db->insert('sfa_task', $taskData);
        $last_id = $this->db->insert_id();
        if ($last_id) {

            if (isset($_POST['data']['attachment']) && $_POST['data']['attachment']) {
                foreach ($_POST['data']['attachment'] as $key => $row) {
                    $img_data = $this->CommonMethod->base64img_to_normal_img($row['path']);
                    $image_name = uniqid() . '.' . $img_data['ext'];
                    $uploadPath = 'uploads/task/' . $image_name;
                    $insertArr = [
                        'task_id' => $last_id,
                        'document_name' => $image_name,
                        'date_created' => date('Y-m-d h:i:s'),
                    ];
                    $this->db->insert('sfa_task_image', $insertArr);
                    file_put_contents($uploadPath, $img_data['data']);
                    $this->CommonMethod->reduceImage($uploadPath);
                }
            }
        }

        if ($last_id) {
            $msg = 'Success';
            $statusCode = 200;
        } else {
            $msg = 'Failed';
            $statusCode = 500;
        }
        return $this->CommonMethod->response_msg($statusCode, '', [], $msg);
    }
    public function closeTask()
    {
        if (!isset($_POST) || empty($_POST)) {
            return $this->CommonMethod->response_msg(400, '');
        }
        if (!isset($_POST['data']['close_remark']) || empty($_POST['data']['close_remark'])) {
            return $this->CommonMethod->response_msg(400, 'close_remark');
        }
        if (!isset($_POST['data']['id']) || empty($_POST['data']['id'])) {
            return $this->CommonMethod->response_msg(400, 'id');
        }
        $taskData = array(
            'close_remark' => $_POST['data']['close_remark'],
            'status' => 'close',
            'close_date' => date('Y-m-d H:i:s'),
        );
        $this->db->where('sfa_task.id', $_POST['data']['id']);
        $update = $this->db->update('sfa_task', $taskData);

        if ($update) {
            $msg = 'Success';
            $statusCode = 200;
        } else {
            $msg = 'Failed';
            $statusCode = 500;
        }

        return $this->CommonMethod->response_msg($statusCode, '', [], $msg);
    }
    public function getTaskList()
    {
        if (!isset($_POST) || empty($_POST)) {
            return $this->CommonMethod->response_msg(400, '');
        }
        if (!isset($_POST['created_by_id']) || empty($_POST['created_by_id'])) {
            return $this->CommonMethod->response_msg(400, 'created_by_id');
        }
        if (!isset($_POST['task_type']) || empty($_POST['task_type']) || ($_POST['task_type'] != 'promise_pending' && $_POST['task_type'] != 'promise_done' && $_POST['task_type'] != 'close')) {
            return $this->CommonMethod->response_msg(400, 'task_type');
        }
        if (isset($_POST['created_by_id']) && $_POST['created_by_id'] != 1) {

            if (!isset($_POST['task_status']) || empty($_POST['task_status']) || ($_POST['task_status'] != 'my_task' && $_POST['task_status'] != 'assign_task')) {
                return $this->CommonMethod->response_msg(400, 'created_by_id');
            }
        }

        $result = [];
        $this->db->select('*');
        $this->db->from('sfa_task');
        $this->db->where('sfa_task.del', 0);
        if (isset($_POST['created_by_id']) && $_POST['created_by_id'] != 1) {
            if (isset($_POST['task_status']) && $_POST['task_status'] == 'my_task') {
                $this->db->where('sfa_task.created_by', $_POST['created_by_id']);
            }
            if (isset($_POST['task_status']) && $_POST['task_status'] == 'assign_task') {
                $this->db->where('sfa_task.assign_to_id', $_POST['created_by_id']);
            }
        }
        $clone = clone $this->db;
        if ($_POST['task_type'] && $_POST['task_type'] == 'promise_pending') {
            $this->db->where('sfa_task.status', 'promise_pending');
        }
        if ($_POST['task_type'] && $_POST['task_type'] == 'promise_done') {
            $this->db->where('sfa_task.status', 'promise_done');
        }
        if ($_POST['task_type'] && $_POST['task_type'] == 'close') {
            $this->db->where('sfa_task.status', 'close');
        }
        if (isset($_POST['filter']['date_created']) && $_POST['filter']['date_created']) {
            $this->db->where('DATE(sfa_task.date_created)', $_POST['filter']['date_created']);
        }
        if (isset($_POST['filter']['assign_to_name']) && $_POST['filter']['assign_to_name']) {
            $this->db->like('sfa_task.assign_to_name', $_POST['filter']['assign_to_name']);
        }
        if (isset($_POST['filter']['assign_to_type']) && $_POST['filter']['assign_to_type']) {
            $this->db->where('sfa_task.assign_to_type', $_POST['filter']['assign_to_type']);
        }
        if (isset($_POST['filter']['promise_date']) && $_POST['filter']['promise_date']) {
            $this->db->where('DATE(sfa_task.promise_date)', $_POST['filter']['promise_date']);
        }
        if (isset($_POST['filter']['close_date']) && $_POST['filter']['close_date']) {
            $this->db->where('DATE(sfa_task.close_date)', $_POST['filter']['close_date']);
        }
        $count = clone $this->db;
        $this->db->order_by('sfa_task.id', 'DESC');
        $result = $this->db->get()->result_array();
        $tab = $clone->get()->result_array();
        $count = $count->get()->num_rows();
        $tabCount = array_count_values(array_column($tab, 'status'));

        if (!isset($tabCount['promise_pending'])) {
            $tabCount['promise_pending'] = 0;
        }
        if (!isset($tabCount['promise_done'])) {
            $tabCount['promise_done'] = 0;
        }
        if (!isset($tabCount['close'])) {
            $tabCount['close'] = 0;
        }

        if (isset($result) && sizeof($result) > 0) {
            return $this->CommonMethod->response_msg(200, '', ['data' => $result, 'tabCount' => $tabCount, 'count' => $count]);
        } else {
            $statusMsg = 'No Data Found';
            return $this->CommonMethod->response_msg(200, '', ['data' => [], 'tabCount' => $tabCount, 'count' => $count], $statusMsg);
        }
    }
    public function getTaskDetail()
    {
        if (!isset($_POST) || empty($_POST)) {
            return $this->CommonMethod->response_msg(400, '');
        }
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            return $this->CommonMethod->response_msg(400, 'id');
        }
        $result = [];
        $this->db->select('*');
        $this->db->from('sfa_task');
        $this->db->where('sfa_task.id', $_POST['id']);
        $this->db->where('sfa_task.del', 0);
        $this->db->order_by('sfa_task.id', 'DESC');
        $result = $this->db->get()->row_array();

        $this->db->select('*');
        $this->db->from('sfa_task_image');
        $this->db->where('sfa_task_image.task_id', $_POST['id']);
        $this->db->where('sfa_task_image.del', 0);
        $this->db->order_by('sfa_task_image.id', 'DESC');
        $result['image'] = $this->db->get()->result_array();
        if (isset($result) && sizeof($result) > 0) {
            return $this->CommonMethod->response_msg(200, '', ['data' => $result]);
        } else {
            $statusMsg = 'No Data Found';
            return $this->CommonMethod->response_msg(200, '', ['data' => []], $statusMsg);
        }
    }
    public function getUserList()
    {
        $result = [];
        $this->db->select('name,id,employee_id as emp_code,user_type');
        $this->db->from('sfa_user');
        $this->db->where('sfa_user.del', 0);
        $this->db->where('sfa_user.status', 1);
        if (isset($_POST['search']) && $_POST['search']) {
            $this->db->group_start();
            $this->db->like('sfa_user.name', $_POST['search']);
            $this->db->or_like('sfa_user.employee_id', $_POST['search']);
            $this->db->group_end();
        }
        $this->db->where('sfa_user.id!=', 1);
        $this->db->order_by('sfa_user.id', 'DESC');
        $result = $this->db->get()->result_array();

        if (isset($result) && sizeof($result) > 0) {
            return $this->CommonMethod->response_msg(200, '', ['data' => $result]);
        } else {
            $statusMsg = 'No Data Found';
            return $this->CommonMethod->response_msg(200, '', ['data' => []], $statusMsg);
        }
    }
}
