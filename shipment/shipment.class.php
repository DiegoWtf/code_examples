<?php
if (!class_exists('DB')) {
    include_once('project_folder/core/db.class.php');
}

/**
 * Class Shipment
 */
class Shipment extends DB {
    private $db;

    function __construct(){
        $this->db = new DB();
    }

    /**
     * формирование массива на регистрацию отправления
     *
     * @param $order_id
     * @param $delivery_id
     * @param $table
     * @return string
     */
    public function registry_in($order_id, $delivery_id, $table) {
        // prepare data of order
        $order_ar=[];
        if($table === 'orders') {
            $pp_id = $this->db->single('SELECT text FROM some_table1 WHERE uid="19" AND zakaz = :zakaz LIMIT 1',['zakaz'=>$order_id]);
            $data = $this->db->row('SELECT * FROM some_table2 WHERE id = :id LIMIT 1',['id'=>$order_id]);
            $product_title = $this->db->single('SELECT title FROM some_table3 WHERE ref = :ref LIMIT 1',['ref'=>$data['album_ref']]);
            // данные по заказу и доставке
            $order_ar = [
                'id' => $order_id,
                'ship_fio' => $data['ship_fio'],
                'ship_mobile' => $data['PHONE'],
                'ship_email' => $data['EMAIL'],
                'ship_cost' => $data['SHIP_ORDER_PRICE'],
                'ship_post_id' => $data['ship_post_id'],
                'ship_city' => $data['CITY'],
                'ship_address' => $data['dostavka'],
                'pay_status' => $data['OPLATA'],
                'pay_price' => $data['REAL_PRICE'],
                'pp_id' => $pp_id ?? 0
            ];
            $order_ar['items'][] = [
                'id' => $order_id,
                'weight' => $data['weight'],
                'quantity' => $data['COPIES'],
                'cost' => $data['ORDER_PRICE'],
                'title' => $product_title
            ];
        }
        // если есть данные
        if(count($order_ar) > 0 &&  (int) $delivery_id > 0) {
            // pickpoint
            if($delivery_id === 8)
                $data_return = $this->pickpoint_reg($order_ar,$table);
            // boxberry
            elseif($delivery_id === 20)
                $data_return = $this->boxberry_reg($order_ar,$table);
            // spsr
            elseif($delivery_id === 23)
                $data_return = $this->spsr_reg($order_ar,$table);
        }
        if(isset($data_return))
            return $data_return;
        else
            return json_encode([
                'status' => 'error',
                'mess' => 'Ошибка! Нет данных: order_ar '.count($order_ar).', delivery_id '. (int)$delivery_id
            ]);
    }

    /**
     * регистрация отправления в PickPoint
     *
     * @param $data
     * @param $table
     * @return string
     */
    protected function pickpoint_reg($data,$table) {
        include_once('pickpoint_api.php');

        $pp = new PickPoint('api');
        $pp_sess = $pp->login('login','pass');
        $invoice = [
            'SenderCode'     => $data['id'],
            'Description'    => 'descr',
            'RecipientName'  => $data['ship_fio'],
            'PostamatNumber' => $data['pp_id'],
            'MobilePhone'    => $data['ship_mobile'],
            'Email'          => $data['ship_email'],
            'PostageType'    => $data['pay_status'] === '2' ? '10001' : '10003',
            'GettingType'    => '102',
            'PayType'        => '1',
            'Sum'            => $data['pay_status'] === '2' ? '0' : $data['pay_price']
        ];
        $resp = $pp->createsending($pp_sess->SessionId,[['Invoice'=>$invoice]]);
        $pp->logout($pp_sess->SessionId);

        if(isset($resp->CreatedSendings[0]->InvoiceNumber) && strlen($resp->CreatedSendings[0]->InvoiceNumber)>0) {
            $status = 'success';
            $mess = $resp->CreatedSendings[0]->InvoiceNumber;
            $this->update_ship_tracker($resp->CreatedSendings[0]->InvoiceNumber, (isset($data['orig_id']) ? $data['orig_id'] : $data['id']), $table);
        } else {
            $status = 'error';
            $mess = 'Pickpoint говорит: '.$resp->RejectedSendings[0]->ErrorMessage;
        }

        return json_encode([
            'status' => $status,
            'mess' => $mess
        ]);
    }

    /**
     * записываем номер накладной
     *
     * @param $tracker
     * @param $id
     * @param $table
     */
    private function update_ship_tracker($tracker, $id, $table) {
        if($table === 'orders') {
            $this->db->query('UPDATE some_table SET post_id = :ship_tracker WHERE id = :id',['ship_tracker'=>$tracker, 'id'=>$id]);
        }
    }
}