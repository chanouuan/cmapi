<?php

namespace controllers;

use models\XicheModel;

class Xiche extends \ActionPDO {

    public function __init () {

    }

    /**
     * 接收洗车机状态上报
     */
    public function ReportStatus () {
        $model = new XicheModel();
        $ret = $model->ReportStatus();
        if ($ret['errorcode'] !== 0) {
            $this->showMessage($ret['message']);
        }
        $this->showMessage($ret['message'], true, $ret['data']);
    }

    /**
     * 显示洗车机识别的返回格式
     */
    protected function showMessage ($message = '', $result = false, $data = [], $input = true) {
        $code = [
            'result' => boolval($result),
            'data' => $data,
            'messages' => strval($message)
        ];
        if ($input) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_unicode_encode($code);
            exit(0);
        }
        return $code;
    }

}